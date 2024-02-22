import actions from './actions';

const resolvers = {
	*getSettings() {
		const path = '/beyondwords/v1/settings';
		const settings = yield actions.fetchFromAPI( path );
		return actions.setSettings( settings );
	},
	*getPlayerStyles( projectId ) {
		if (! projectId) {
			return [];
		}
		const path = `/beyondwords/v1/projects/${projectId}/player-styles`;
		const playerStyles = yield actions.fetchFromAPI( path );
		return actions.setPlayerStyles( playerStyles );
	},
	*getLanguages() {
		const path = '/beyondwords/v1/languages';
		const languages = yield actions.fetchFromAPI( path );
		return actions.setLanguages( languages );
	},
	*getVoices( languageId ) {
		const path = `/beyondwords/v1/languages/${languageId}/voices`;
		const voices = yield actions.fetchFromAPI( path );
		return actions.setVoices( voices );
	},
};

export default resolvers;
