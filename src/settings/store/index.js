/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

// Shared editor state. Adding a key here gives you a `getKey` selector for
// free; write a resolver below that returns `set( 'key', value )`.
export const DEFAULT_STATE = {
	settings: {},
	languages: [],
	voices: [],
	scriptTemplates: [],
	videoTemplates: [],
	videoSizes: [],
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	if ( action.type === 'SET' && action.key in state ) {
		const empty = Array.isArray( DEFAULT_STATE[ action.key ] ) ? [] : {};
		return { ...state, [ action.key ]: action.value || empty };
	}
	return state;
};

const selectors = Object.fromEntries(
	Object.keys( DEFAULT_STATE ).map( ( key ) => [
		`get${ key[ 0 ].toUpperCase() }${ key.slice( 1 ) }`,
		( state ) => state[ key ],
	] )
);

const set = ( key, value ) => ( { type: 'SET', key, value } );

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
		return set( 'voices', value );
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
			return set( 'videoSizes', [] );
		}
		const r = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }/video-settings`,
		} );
		return set( 'videoSizes', r?.sizes ?? [] );
	},
};

export default createReduxStore( 'beyondwords/settings', {
	reducer,
	selectors,
	resolvers,
} );
