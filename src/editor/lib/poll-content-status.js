/**
 * Poll a BeyondWords content object until it reaches a terminal status.
 *
 * Embedding a player while the backend is still processing CDN-caches a 404 for
 * its first asset request, so callers poll and embed only once `processed`.
 * No React/@wordpress imports so the block editor, classic IIFE and jest share it.
 */

/**
 * Statuses that mean "not finished yet" — keep polling.
 *
 * Everything else (`processed`, `error`, `skipped`, or an unknown/absent value)
 * is terminal so the loop always ends.
 *
 * @type {string[]}
 */
export const NON_TERMINAL_STATUSES = [ 'draft', 'queued', 'processing' ];

/**
 * The success status — the only one for which a player should be embedded.
 *
 * @type {string}
 */
export const PROCESSED_STATUS = 'processed';

/**
 * Default poll interval, in milliseconds.
 *
 * @type {number}
 */
export const DEFAULT_INTERVAL_MS = 3000;

/**
 * Default overall time budget, in milliseconds, before giving up.
 *
 * @type {number}
 */
export const DEFAULT_TIMEOUT_MS = 120000;

/**
 * Build an `AbortError` that callers can recognise and swallow.
 *
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
 * Abort-aware so unmount/cleanup doesn't have to wait out the interval.
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
 * Chained `setTimeout` (not `setInterval`) so a slow request never overlaps the
 * next tick; one transient fetch failure counts as a non-terminal tick.
 *
 * @param {Object}       options             Options.
 * @param {Function}     options.fetchStatus `() => Promise<{ status: string }>`.
 *                                           Fetches the content object.
 * @param {Function=}    options.onTick      Called with the current status on
 *                                           each non-terminal poll.
 * @param {Function=}    options.isHidden    `() => boolean`. When it returns
 *                                           true the upstream call is skipped
 *                                           for that cycle (throttle background
 *                                           tabs — each poll is an uncached
 *                                           upstream API call).
 * @param {AbortSignal=} options.signal      Abort signal to stop polling.
 * @param {number=}      options.intervalMs  Delay between polls.
 * @param {number=}      options.timeoutMs   Overall time budget.
 *
 * @return {Promise<{status: (string|undefined), timedOut: boolean}>} Last-seen
 *         status; `timedOut` is true when the budget elapsed while non-terminal.
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

	// Loop terminates via: terminal status (return), budget elapsed (return),
	// or abort (throw). The `while ( true )` body always awaits, so it can't spin.
	while ( true ) {
		if ( signal?.aborted ) {
			throw abortError();
		}

		// The budget measures *visible* time: a backgrounded tab must resume
		// polling on return rather than time out having never checked.
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
			// Transient failure — keep polling.
			await sleep( intervalMs, signal );
			continue;
		}

		lastStatus = status;

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
