/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

// Shared editor state, fetched lazily by the resolvers below; each key has a
// matching selector. Most keys hold a single session-wide value. `voices`,
// `videoSizes` and `project` are maps keyed by their resolver's argument:
// @wordpress/data marks resolution finished per selector-args, so each
// argument's result needs its own slot — with one shared slot, a later fetch
// (say, another language's voices) would overwrite it while the resolution
// cache keeps reporting the overwritten entry as fresh.
export const DEFAULT_STATE = {
	settings: {},
	languages: [],
	voices: {}, // languageCode → voice list
	scriptTemplates: [],
	videoTemplates: [],
	videoSizes: {}, // projectId → size list
	project: {}, // projectId → project record
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	// Replace a session-wide key.
	if ( action.type === 'SET' && action.key in state ) {
		const empty = Array.isArray( DEFAULT_STATE[ action.key ] ) ? [] : {};
		return { ...state, [ action.key ]: action.value || empty };
	}
	// Merge one argument's result into a per-argument key, leaving the other
	// arguments' entries untouched.
	if ( action.type === 'SET_BY' && action.key in state ) {
		return {
			...state,
			[ action.key ]: {
				...state[ action.key ],
				[ action.arg ]: action.value,
			},
		};
	}
	return state;
};

// The per-argument selectors (voices, video sizes, project) read the entry for
// the requested argument only, with an empty value while it is unfetched.
const selectors = {
	getSettings: ( state ) => state.settings,
	getLanguages: ( state ) => state.languages,
	getVoices: ( state, languageCode ) => state.voices[ languageCode ] ?? [],
	getScriptTemplates: ( state ) => state.scriptTemplates,
	getVideoTemplates: ( state ) => state.videoTemplates,
	getVideoSizes: ( state, projectId ) => state.videoSizes[ projectId ] ?? [],
	getProject: ( state, projectId ) => state.project[ projectId ] ?? {},
};

// Both action shapes are private to this store — it registers no `actions`,
// so the resolvers below are the only dispatchers.
const set = ( key, value ) => ( { type: 'SET', key, value } );
const setBy = ( key, arg, value ) => ( { type: 'SET_BY', key, arg, value } );

const resolvers = {
	async getSettings() {
		const value = await apiFetch( { path: '/beyondwords/v1/settings' } );
		return set( 'settings', value );
	},
	async getLanguages() {
		const value = await apiFetch( { path: '/beyondwords/v1/languages' } );
		return set( 'languages', value );
	},
	async getVoices( languageCode ) {
		const value = await apiFetch( {
			path: `/beyondwords/v1/languages/${ languageCode }/voices`,
		} );
		return setBy( 'voices', languageCode, value || [] );
	},
	async getScriptTemplates() {
		const value = await apiFetch( {
			path: '/beyondwords/v1/summarization-settings-templates',
		} );
		return set( 'scriptTemplates', value );
	},
	async getVideoTemplates() {
		const value = await apiFetch( {
			path: '/beyondwords/v1/video-settings-templates',
		} );
		return set( 'videoTemplates', value );
	},
	async getVideoSizes( projectId ) {
		if ( ! projectId ) {
			return setBy( 'videoSizes', projectId, [] );
		}
		const r = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }/video-settings`,
		} );
		return setBy( 'videoSizes', projectId, r?.sizes ?? [] );
	},
	// The project carries the default `language` used to pre-select the Language
	// dropdown when Customize is enabled. Fetched on demand (behind the toggle).
	async getProject( projectId ) {
		if ( ! projectId ) {
			return setBy( 'project', projectId, {} );
		}
		const value = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }`,
		} );
		return setBy( 'project', projectId, value || {} );
	},
};

export default createReduxStore( 'beyondwords/settings', {
	reducer,
	selectors,
	resolvers,
} );
