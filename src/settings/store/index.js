/**
 * WordPress dependencies
 */
import { createReduxStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import resolvers from './resolvers';
import selectors from './selectors';

export const DEFAULT_STATE = {
	isRegeneratingAudio: false,
	playerStyles: [],
	languages: [],
	settings: {},
	voices: [],
	scriptTemplates: [],
	videoSizes: [],
};

export default createReduxStore( 'beyondwords/settings', {
	reducer,
	selectors,
	resolvers,
} );
