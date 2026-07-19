/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

// `voices`/`videoSizes`/`project` are keyed by resolver argument: resolution is
// cached per selector-args, so a shared slot would go stale yet report fresh.
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
	// Merge one argument's result, leaving other arguments' entries untouched.
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

const selectors = {
	getSettings: ( state ) => state.settings,
	getLanguages: ( state ) => state.languages,
	getVoices: ( state, languageCode ) => state.voices[ languageCode ] ?? [],
	getScriptTemplates: ( state ) => state.scriptTemplates,
	getVideoTemplates: ( state ) => state.videoTemplates,
	getVideoSizes: ( state, projectId ) => state.videoSizes[ projectId ] ?? [],
	getProject: ( state, projectId ) => state.project[ projectId ] ?? {},
};

// Private to this store — no registered `actions`; only resolvers dispatch.
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
	// The project's default `language` pre-selects the Language dropdown.
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
