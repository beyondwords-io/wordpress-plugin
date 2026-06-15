/* global HTMLScriptElement */

/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { canPlayAudio } from './helpers';

const PLAYER_SCRIPT_SRC =
	'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';

// Delay first player load after a contentId appears so the CDN doesn't cache a
// 404 for content it hasn't published yet. Matches the implicit delay the
// Classic Editor gets from its post-save page reload.
const NEW_CONTENT_DELAY_MS = 2000;

export function useBeyondWordsNamespace() {
	const [ value, setValue ] = useState( () => {
		return window?.BeyondWords ?? null;
	} );

	useEffect( () => {
		// The script can finish loading in the gap between the render-phase
		// useState initializer (which may have seen no namespace) and this
		// post-paint effect. Re-read the namespace synchronously so we don't
		// attach a 'load' listener to an already-loaded script — that listener
		// would never fire again, leaving value null and the player uncreated.
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

export function useBeyondWordsPlayer( {
	target,
	projectId,
	sourceId,
	contentId,
	previewToken,
} ) {
	const BeyondWords = useBeyondWordsNamespace();

	const [ player, setPlayer ] = useState( null );

	// Initialized from the mount-time contentId so existing posts skip the delay.
	const hasSeenContentIdRef = useRef( !! contentId );

	useEffect( () => {
		if ( ! BeyondWords?.Player || ! target ) {
			setPlayer( null );
			return;
		}

		let newPlayer;
		let timeoutId;

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

			if ( contentId ) {
				hasSeenContentIdRef.current = true;
			}

			setPlayer( newPlayer );
		};

		if ( contentId && ! hasSeenContentIdRef.current ) {
			timeoutId = setTimeout( initPlayer, NEW_CONTENT_DELAY_MS );
		} else {
			initPlayer();
		}

		return () => {
			if ( timeoutId ) {
				clearTimeout( timeoutId );
			}
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

	return player;
}

/**
 * Whether PlayAudio will render a player for the current post.
 *
 * Reactive wrapper over `canPlayAudio` for function components: it re-renders
 * the caller when the post status or BeyondWords meta change. PlayAudioCheck
 * gates the player on it; PreviewPanel uses it to hide the panel when the player
 * would render nothing.
 *
 * @return {boolean} True when PlayAudio has a player to render.
 */
export function useCanPlayAudio() {
	return useSelect( canPlayAudio, [] );
}
