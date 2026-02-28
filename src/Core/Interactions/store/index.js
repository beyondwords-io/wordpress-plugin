/**
 * WordPress dependencies
 */
import { createReduxStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import actions from './actions';
import reducer from './reducer';
import selectors from './selectors';

export const DEFAULT_STATE = {
	generateAudioEdited: false,
};

const store = {
	reducer,
	actions,
	selectors,
	controls: {},
	resolvers: {},
};

export default createReduxStore( 'beyondwords/interactions', store );
