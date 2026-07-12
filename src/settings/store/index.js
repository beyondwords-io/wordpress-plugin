/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

// Shared editor state. Two shapes of key live here:
//
//   * Plain keys hold one session-wide value. Adding one gives you a `getKey`
//     selector for free; its resolver returns `set( 'key', value )`.
//   * Per-argument keys (listed in `KEYED_BY_ARG`) hold a map from a resolver
//     argument to its result — e.g. voices per language code. @wordpress/data
//     memoises resolution per-args, so the *stored* value has to be per-args
//     too. A single shared slot would let a later fetch (another language or
//     project) overwrite it while the already-"resolved" selector keeps serving
//     the wrong list. Their resolvers return `setBy( 'key', arg, value )` and
//     they get a hand-written, arg-reading selector below.
export const DEFAULT_STATE = {
	settings: {},
	languages: [],
	voices: {},
	scriptTemplates: [],
	videoTemplates: [],
	videoSizes: {},
	project: {},
};

// Per-argument keys mapped to the value their selector returns for an argument
// that has not been fetched yet (voices/videoSizes are lists, project a record).
const KEYED_BY_ARG = {
	voices: [],
	videoSizes: [],
	project: {},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	if ( action.type === 'SET' && action.key in state ) {
		const empty = Array.isArray( DEFAULT_STATE[ action.key ] ) ? [] : {};
		return { ...state, [ action.key ]: action.value || empty };
	}
	// Merge a per-argument result into its key's map, leaving other arguments'
	// entries untouched so each language/project keeps its own value.
	if ( action.type === 'SET_BY' && action.key in KEYED_BY_ARG ) {
		return {
			...state,
			[ action.key ]: {
				...state[ action.key ],
				[ action.arg ]: action.value || KEYED_BY_ARG[ action.key ],
			},
		};
	}
	return state;
};

const getterName = ( key ) =>
	`get${ key[ 0 ].toUpperCase() }${ key.slice( 1 ) }`;

const selectors = {
	// Plain keys read the whole slot: `getKey( state ) => state.key`.
	...Object.fromEntries(
		Object.keys( DEFAULT_STATE )
			.filter( ( key ) => ! ( key in KEYED_BY_ARG ) )
			.map( ( key ) => [ getterName( key ), ( state ) => state[ key ] ] )
	),
	// Per-argument keys read only their own entry: `getKey( state, arg ) =>
	// state.key[ arg ] ?? empty`. Reading by the argument keeps the selector in
	// step with @wordpress/data's per-args resolution cache, so a value fetched
	// for one argument is never served for another.
	...Object.fromEntries(
		Object.entries( KEYED_BY_ARG ).map( ( [ key, empty ] ) => [
			getterName( key ),
			( state, arg ) => state[ key ][ arg ] ?? empty,
		] )
	),
};

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
		return setBy( 'voices', languageCode, value );
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
		return setBy( 'project', projectId, value );
	},
};

export default createReduxStore( 'beyondwords/settings', {
	reducer,
	selectors,
	resolvers,
} );
