/* global HTMLScriptElement */

/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

const PLAYER_SCRIPT_SRC =
	'https://proxy.beyondwords.io/npm/@beyondwords/player@latest/dist/umd.js';

export function useBeyondWordsNamespace() {
	const [ value, setValue ] = useState( () => {
		return window?.BeyondWords ?? null;
	} );

	useEffect( () => {
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
	contentId,
	loadContentAs,
	previewToken,
} ) {
	const BeyondWords = useBeyondWordsNamespace();

	const [ { player, loaded }, setPlayer ] = useState( {
		player: null,
		loaded: false,
	} );

	useEffect( () => {
		if ( ! BeyondWords?.Player || ! target ) {
			setPlayer( { player: null, loaded: false } );
			return;
		}

		let newPlayer;

		try {
			newPlayer = new BeyondWords.Player( {
				target,
				projectId,
				contentId,
				loadContentAs: loadContentAs ?? [ 'article' ],
				previewToken: previewToken || '',
				analyticsConsent: 'none',
				playerStyle: 'small',
				widgetStyle: 'none',
				introsOutros: [],
				adverts: [],
			} );
		} catch ( error ) {
			setPlayer( { player: null, loaded: false } );

			// @todo display error notice in Wordpress admin.

			return;
		}

		setPlayer( { player: newPlayer, loaded: false } );

		return () => {
			setPlayer( { player: null, loaded: false } );
			newPlayer.destroy();
		};
	}, [
		BeyondWords?.Player,
		target,
		projectId,
		contentId,
		loadContentAs,
		previewToken,
	] );

	return { player, loaded };
}
