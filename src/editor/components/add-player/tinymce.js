/* global tinymce */

( function () {
	tinymce.PluginManager.add( 'beyondwords_player', function ( editor, url ) {
		// Command for Button
		editor.addCommand( 'beyondwords_insert_player', function () {
			const playerElement =
				'<div data-beyondwords-player="true" contenteditable="false"></div>\uFEFF';

			editor.execCommand( 'mceInsertContent', false, playerElement );
		} );

		const image = url + '/tinymce-button.png';

		// Add Button to Visual Editor Toolbar
		editor.addButton( 'beyondwords_player', {
			title: 'Insert BeyondWords player',
			cmd: 'beyondwords_insert_player',
			image,
		} );
	} );
} )();
