/* global jQuery, $e, elementor */

// UI hook, fired after the command runs.
// Important: Available to run in the console but depends on $e.components example#1.

export class InitPlayersAfterDocumentSave extends $e.modules.hookData.After {
	getCommand() {
		// Command to hook.
		return 'document/save/save';
	}

	getId() {
		// Unique id for the hook.
		return 'beyondwords-init-players-after-document-save';
	}

	/*
	 * The actual hook logic.
	 */
	apply( args ) {
		const { document = elementor.documents.getCurrent(), status } = args;

		if ( status === 'autosave' ) {
			return;
		}

		window.elementorCommon.ajax
			.addRequest( 'get_beyondwords_data', {
				error: ( data ) => this.onRequestError( data, document ),
			} )
			.then( ( data ) => this.onRequestSuccess( data, document ) );
	}

	onRequestSuccess( data, document ) {
		const { beyondwords_project_id, beyondwords_content_id } = data;

		// Remove document cache.
		elementor.documents.invalidateCache( document.id );

		// Update Elementor controls
		$e.run( 'document/elements/settings', {
			container: elementor.settings.page.getEditedView().getContainer(),
			settings: {
				control_beyondwords_project_id: beyondwords_project_id,
				control_beyondwords_content_id: beyondwords_content_id,
			},
			options: {
				external: true,
			},
		} );

		jQuery( '#beyondwords-elementor-editor-player' ).attr(
			'data-beyondwords-project-id',
			beyondwords_project_id
		);

		jQuery( '#beyondwords-elementor-editor-player' ).attr(
			'data-beyondwords-content-id',
			beyondwords_content_id
		);

		setTimeout( function () {
			window.beyondwordsElementorCompatibility.initPlayer();
		}, 250 );

		return {
			data,
		};
	}

	onRequestError() {
		elementor.notifications.showToast( {
			message:
				'Unable to retrieve BeyondWords data. Please refresh the page to see the player.',
		} );
	}
}
