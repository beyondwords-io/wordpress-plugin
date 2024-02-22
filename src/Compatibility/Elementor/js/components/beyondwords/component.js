/* global $e, elementor */

import * as hooks from './hooks/';
import * as commands from './commands';

export class BeyondwordsComponent extends $e.modules.ComponentBase {
	constructor() {
		super();

		this.bindEvents();
	}

	/**
	 * Listen to click events.
	 *
	 * Inspired by https://github.com/elementor/elementor/blob/9aadba35a9ad52cd5e9cbef55a5761f47403491c/modules/container-converter/assets/js/editor/component.js#L10
	 *
	 * @return {void}
	 */
	bindEvents() {
		// Copy "Inspect" data
		elementor.channels.editor.on( 'beyondwords:copy-inspect-data', () => {
			// @todo Copy data
			elementor.notifications.showToast( {
				// message: __( 'The data has been copied.', 'elementor' ),
				message: 'The data has been copied.',
			} );
		} );

		// Email support
		elementor.channels.editor.on( 'beyondwords:email-support', () => {
			window.location.href = 'mailto:support@beyondwords.io';
		} );

		// Open guide
		elementor.channels.editor.on( 'beyondwords:open-guide', () => {
			window.open(
				'https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/install?utm_source=wordpress&utm_medium=referral&utm_campaign=&utm_content=plugin'
			);
		} );
	}

	getNamespace() {
		return 'beyondwords';
	}

	defaultCommands() {
		return this.importCommands( commands );
	}

	// https://github.com/elementor/elementor/blob/9aadba35a9ad52cd5e9cbef55a5761f47403491c/docs/modules/web-cli/assets/js/core/hooks.md
	defaultHooks() {
		return this.importHooks( hooks );
	}

	// Redux state for this JS component
	defaultStates() {
		return {
			// A correspnding `Slice` instance will be available under `$e.store.get( 'beyondwords' )`.
			'': {
				initialState: {
					projectId: null,
					contentId: null,
				},
				reducers: {
					setProjectId: ( prev, { payload } ) => {
						return {
							...prev,
							projectId: payload,
						};
					},
					setContentId: ( prev, { payload } ) => {
						return {
							...prev,
							contentId: payload,
						};
					},
				},
			},
		};
	}
}
