/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './';

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_IS_REGENERATING_AUDIO':
			return {
				...state,
				isRegeneratingAudio: action.value,
			};
		case 'SET_LANGUAGES':
			return {
				...state,
				languages: action.value || [],
			};
		case 'SET_PLAYER_STYLES':
			return {
				...state,
				playerStyles: action.value || [],
			};
		case 'SET_SETTINGS':
			return {
				...state,
				settings: action.value || {},
			};
		case 'SET_VOICES':
			return {
				...state,
				voices: action.value || [],
			};
		case 'SET_SCRIPT_TEMPLATES':
			return {
				...state,
				scriptTemplates: action.value || [],
			};
		case 'SET_VIDEO_SIZES':
			return {
				...state,
				videoSizes: action.value || [],
			};
	}

	return state;
};

export default reducer;
