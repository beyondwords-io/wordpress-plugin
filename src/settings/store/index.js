/**
 * WordPress dependencies
 */
import { createReduxStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import actions from './actions';
import controls from './controls';
import reducer from './reducer';
import resolvers from './resolvers';
import selectors from './selectors';

export const DEFAULT_STATE = {
	isRegeneratingAudio: false,
	playerStyles: [],
	languages: [],
	settings: {},
	voices: [],
};

const store = {
	reducer,
	actions,
	selectors,
	controls,
	resolvers,
};

export default createReduxStore( 'beyondwords/settings', store );
