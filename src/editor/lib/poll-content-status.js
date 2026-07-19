/**
 * Poll a BeyondWords content object until it reaches a terminal status.
 *
 * Embedding the player before the backend finishes processing 404s, and the CDN
 * caches that 404 — so callers wait for `processed` before embedding.
 *
 * No React or `@wordpress/*` imports: the classic editor script is served raw
 * and cannot import a built module.
 */

export const NON_TERMINAL_STATUSES = [ 'draft', 'queued', 'processing' ];
export const PROCESSED_STATUS = 'processed';
export const DEFAULT_INTERVAL_MS = 3000;
export const DEFAULT_TIMEOUT_MS = 120000;

/**
 * @return {Error} An error whose `name` is `'AbortError'`.
 */
function abortError() {
	const error = new Error( 'Aborted' );
	error.name = 'AbortError';
	return error;
}

/**
 * Resolve after `ms`, or reject with an `AbortError` if `signal` aborts first.
 *
 * @param {number}       ms     Delay in milliseconds.
 * @param {AbortSignal=} signal Optional abort signal.
 *
 * @return {Promise<void>} Resolves after the delay.
 */
function sleep( ms, signal ) {
	return new Promise( ( resolve, reject ) => {
		if ( signal?.aborted ) {
			reject( abortError() );
			return;
		}

		const onAbort = () => {
			clearTimeout( timeoutId );
			reject( abortError() );
		};

		const timeoutId = setTimeout( () => {
			signal?.removeEventListener( 'abort', onAbort );
			resolve();
		}, ms );

		signal?.addEventListener( 'abort', onAbort, { once: true } );
	} );
}

/**
 * Poll `fetchStatus` until the content reaches a terminal status.
 *
 * @param {Object}       options             Options.
 * @param {Function}     options.fetchStatus `() => Promise<{ status: string }>`.
 * @param {Function=}    options.onTick      Called on each non-terminal poll.
 * @param {Function=}    options.isHidden    Skips the request while it returns true.
 * @param {AbortSignal=} options.signal      Abort signal to stop polling.
 * @param {number=}      options.intervalMs  Delay between polls.
 * @param {number=}      options.timeoutMs   Overall time budget.
 *
 * @return {Promise<{status: (string|undefined), timedOut: boolean}>} Result.
 */
export async function pollContentStatus( {
	fetchStatus,
	onTick,
	isHidden,
	signal,
	intervalMs = DEFAULT_INTERVAL_MS,
	timeoutMs = DEFAULT_TIMEOUT_MS,
} ) {
	const start = Date.now();
	let lastStatus;
	let hiddenMs = 0;

	while ( true ) {
		if ( signal?.aborted ) {
			throw abortError();
		}

		// Hidden cycles never fetch, so they must not spend the budget.
		if ( Date.now() - start - hiddenMs >= timeoutMs ) {
			return { status: lastStatus, timedOut: true };
		}

		if ( typeof isHidden === 'function' && isHidden() ) {
			const hiddenAt = Date.now();
			await sleep( intervalMs, signal );
			hiddenMs += Date.now() - hiddenAt;
			continue;
		}

		let status;
		try {
			const response = await fetchStatus();
			status = response?.status;
		} catch {
			// One blip shouldn't abandon the wait; retry until the budget runs out.
			await sleep( intervalMs, signal );
			continue;
		}

		lastStatus = status;

		// Unknown statuses count as terminal so the loop always ends.
		if ( ! NON_TERMINAL_STATUSES.includes( status ) ) {
			return { status, timedOut: false };
		}

		if ( typeof onTick === 'function' ) {
			onTick( status );
		}

		await sleep( intervalMs, signal );
	}
}

export default pollContentStatus;
