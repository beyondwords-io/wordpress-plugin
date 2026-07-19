/* global HTMLScriptElement */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	pollContentStatus,
	PROCESSED_STATUS,
} from '../../lib/poll-content-status';

const PLAYER_SCRIPT_SRC =
	'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';

export function useBeyondWordsNamespace() {
	const [ value, setValue ] = useState( () => {
		return window?.BeyondWords ?? null;
	} );

	useEffect( () => {
		// The script can finish loading between the useState initializer and this
		// effect; re-read now so we don't listen on an already-loaded script.
		if ( window?.BeyondWords ) {
			setValue( window.BeyondWords );
			return;
		}

		const onLoad = () => {
			setValue( window?.BeyondWords ?? null );
		};

		const existingScript = document.head.querySelector(
			`script[src="${ PLAYER_SCRIPT_SRC }"]`
		);

		if ( existingScript instanceof HTMLScriptElement ) {
			existingScript.addEventListener( 'load', onLoad );
			return () => {
				existingScript.removeEventListener( 'load', onLoad );
			};
		}

		const newScript = document.createElement( 'script' );

		newScript.src = PLAYER_SCRIPT_SRC;
		newScript.async = true;
		newScript.defer = true;
		newScript.addEventListener( 'load', onLoad );

		document.head.appendChild( newScript );

		return () => {
			newScript.removeEventListener( 'load', onLoad );
		};
	}, [] );
	return value;
}

/**
 * Create a (preview) BeyondWords player once its content is ready.
 *
 * A contentId that appears during this session may still be processing, so it
 * polls until `processed` before embedding (see lib/poll-content-status.js).
 *
 * @param {Object}      options              Options.
 * @param {HTMLElement} options.target       Player mount node.
 * @param {number}      options.projectId    BeyondWords project ID.
 * @param {number}      options.sourceId     Post ID (client-side integration).
 * @param {string}      options.contentId    BeyondWords content ID, if any.
 * @param {string}      options.previewToken Preview token, if any.
 *
 * @return {{player: (Object|null), status: (string|undefined), isPolling: boolean, timedOut: boolean}}
 *         The player instance and the current polling state.
 */
export function useBeyondWordsPlayer( {
	target,
	projectId,
	sourceId,
	contentId,
	previewToken,
} ) {
	const BeyondWords = useBeyondWordsNamespace();

	const [ player, setPlayer ] = useState( null );
	const [ pollState, setPollState ] = useState( {
		status: undefined,
		isPolling: false,
		timedOut: false,
	} );

	// Content that existed at mount finished processing long ago and embeds
	// immediately; only a contentId appearing during this session polls first.
	const mountContentIdRef = useRef( contentId );

	useEffect( () => {
		if ( ! BeyondWords?.Player || ! target ) {
			setPlayer( null );
			setPollState( {
				status: undefined,
				isPolling: false,
				timedOut: false,
			} );
			return;
		}

		let newPlayer;
		let cancelled = false;
		const controller = new AbortController();

		const initPlayer = () => {
			try {
				const params = {
					target,
					projectId,
					sourceId,
					contentId,
					loadContentAs: [ 'article' ],
					previewToken: previewToken || '',
					analyticsConsent: 'none',
					playerStyle: 'small',
					widgetStyle: 'none',
					introsOutros: [],
					adverts: [],
				};

				if ( contentId ) {
					delete params.sourceId;
				}

				newPlayer = new BeyondWords.Player( params );
			} catch {
				setPlayer( null );

				// @todo display error notice in Wordpress admin.

				return;
			}

			setPlayer( newPlayer );
		};

		if ( contentId && contentId !== mountContentIdRef.current ) {
			// Session-fresh content: poll until processed, then embed, so a 404
			// is never CDN-cached for still-processing content.
			setPollState( {
				status: undefined,
				isPolling: true,
				timedOut: false,
			} );

			pollContentStatus( {
				fetchStatus: async () => {
					const data = await apiFetch( {
						path: `/beyondwords/v1/projects/${ projectId }/content/${ contentId }`,
						signal: controller.signal,
					} );
					return { status: data?.status };
				},
				onTick: ( status ) => {
					if ( ! cancelled ) {
						setPollState( {
							status,
							isPolling: true,
							timedOut: false,
						} );
					}
				},
				isHidden: () => document.hidden,
				signal: controller.signal,
			} )
				.then( ( { status, timedOut } ) => {
					if ( cancelled ) {
						return;
					}
					setPollState( { status, isPolling: false, timedOut } );
					if ( ! timedOut && status === PROCESSED_STATUS ) {
						initPlayer();
					}
				} )
				.catch( () => {
					// Aborted (unmount or dependency change) — nothing to do.
				} );
		} else {
			// Already-processed content (present at mount) or client-side
			// integration (keyed on sourceId): nothing to poll, embed now.
			setPollState( {
				status: undefined,
				isPolling: false,
				timedOut: false,
			} );
			initPlayer();
		}

		return () => {
			cancelled = true;
			controller.abort();
			setPlayer( null );
			if ( newPlayer ) {
				newPlayer.destroy();
			}
		};
	}, [
		BeyondWords?.Player,
		target,
		projectId,
		sourceId,
		contentId,
		previewToken,
	] );

	return {
		player,
		status: pollState.status,
		isPolling: pollState.isPolling,
		timedOut: pollState.timedOut,
	};
}

/**
 * Whether the post has everything the BeyondWords player needs to load a preview.
 *
 * Pure selector (usable in `useSelect`/`withSelect`, unit-testable). Legacy
 * `podcast_id` keys are recognised so upgraded posts still preview.
 *
 * @param {Function} select Redux-style select() from `@wordpress/data`.
 *
 * @return {boolean} True when the player can load.
 */
export function selectHasPlayAudioAction( select ) {
	const { getEditedPostAttribute } = select( 'core/editor' );

	const status = getEditedPostAttribute( 'status' );
	const projectId = getEditedPostAttribute( 'meta' ).beyondwords_project_id;
	const integrationMethod =
		getEditedPostAttribute( 'meta' ).beyondwords_integration_method;

	const beyondwordsContentId =
		getEditedPostAttribute( 'meta' ).beyondwords_content_id;
	const beyondwordsPodcastId =
		getEditedPostAttribute( 'meta' ).beyondwords_podcast_id;
	const speechkitPodcastId =
		getEditedPostAttribute( 'meta' ).speechkit_podcast_id;

	const contentId =
		beyondwordsContentId || beyondwordsPodcastId || speechkitPodcastId;

	const isClientSide = integrationMethod === 'client-side';

	const hasClientSideContent = isClientSide && projectId;

	const hasRestApiContent = ! isClientSide && projectId && contentId;

	return Boolean(
		status !== 'pending' && ( hasClientSideContent || hasRestApiContent )
	);
}

/**
 * Hook wrapper around {@link selectHasPlayAudioAction} for function components.
 *
 * @return {boolean} True when the player can load.
 */
export function useHasPlayAudioAction() {
	return useSelect( selectHasPlayAudioAction, [] );
}
