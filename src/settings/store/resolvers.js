import actions from './actions';

const resolvers = {
	*getSettings() {
		const path = '/beyondwords/v1/settings';
		const settings = yield actions.fetchFromAPI( path );
		return actions.setSettings( settings );
	},
	*getPlayerStyles( projectId ) {
		if ( ! projectId ) {
			return [];
		}
		const path = `/beyondwords/v1/projects/${ projectId }/player-styles`;
		const playerStyles = yield actions.fetchFromAPI( path );
		return actions.setPlayerStyles( playerStyles );
	},
	*getLanguages() {
		const path = '/beyondwords/v1/languages';
		const languages = yield actions.fetchFromAPI( path );
		return actions.setLanguages( languages );
	},
	*getVoices( languageCode ) {
		const path = `/beyondwords/v1/languages/${ languageCode }/voices`;
		const voices = yield actions.fetchFromAPI( path );
		return actions.setVoices( voices );
	},
	*getScriptTemplates( projectId ) {
		if ( ! projectId ) {
			return actions.setScriptTemplates( [] );
		}
		const path = `/beyondwords/v1/projects/${ projectId }/summarization-settings`;
		const response = yield actions.fetchFromAPI( path );
		return actions.setScriptTemplates( response?.template ?? [] );
	},
	*getVideoSizes( projectId ) {
		if ( ! projectId ) {
			return actions.setVideoSizes( [] );
		}
		const path = `/beyondwords/v1/projects/${ projectId }/video-settings`;
		const response = yield actions.fetchFromAPI( path );
		return actions.setVideoSizes( response?.sizes ?? [] );
	},
};

export default resolvers;
