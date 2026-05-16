/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

const resolvers = {
	async getSettings() {
		const settings = await apiFetch( {
			path: '/beyondwords/v1/settings',
		} );
		return { type: 'SET_SETTINGS', value: settings };
	},
	async getPlayerStyles( projectId ) {
		if ( ! projectId ) {
			return { type: 'SET_PLAYER_STYLES', value: [] };
		}
		const playerStyles = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }/player-styles`,
		} );
		return { type: 'SET_PLAYER_STYLES', value: playerStyles };
	},
	async getLanguages() {
		const languages = await apiFetch( {
			path: '/beyondwords/v1/languages',
		} );
		return { type: 'SET_LANGUAGES', value: languages };
	},
	async getVoices( languageCode ) {
		const voices = await apiFetch( {
			path: `/beyondwords/v1/languages/${ languageCode }/voices`,
		} );
		return { type: 'SET_VOICES', value: voices };
	},
	async getScriptTemplates( projectId ) {
		if ( ! projectId ) {
			return { type: 'SET_SCRIPT_TEMPLATES', value: [] };
		}
		const response = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }/summarization-settings`,
		} );
		return {
			type: 'SET_SCRIPT_TEMPLATES',
			value: response?.template ?? [],
		};
	},
	async getVideoSizes( projectId ) {
		if ( ! projectId ) {
			return { type: 'SET_VIDEO_SIZES', value: [] };
		}
		const response = await apiFetch( {
			path: `/beyondwords/v1/projects/${ projectId }/video-settings`,
		} );
		return { type: 'SET_VIDEO_SIZES', value: response?.sizes ?? [] };
	},
};

export default resolvers;
