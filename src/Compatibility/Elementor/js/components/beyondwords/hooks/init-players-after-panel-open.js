/* global $e */

// UI hook, fired after the command runs.
// Important: Available to run in the console but depends on $e.components example#1.

export class InitPlayersAfterPanelOpen extends $e.modules.hookUI.After {
	getCommand() {
		// Command to hook.
		// This is a custom command which we fire when our panel is opened.
		return 'beyondwords/panel-open';
	}

	getId() {
		// Unique id for the hook.
		return 'beyondwords-init-players-after-panel-open';
	}

	/*
	 * The actual hook logic.
	 */
	apply() {
		window.beyondwordsElementorCompatibility
			.initPlayer()
			.catch( ( err ) => {
				console.error( err );
			} );
	}
}
