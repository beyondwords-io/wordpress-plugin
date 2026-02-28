/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './';

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_GENERATE_AUDIO_EDITED':
			return {
				...state,
				generateAudioEdited: action.value,
			};
	}

	return state;
};

export default reducer;
