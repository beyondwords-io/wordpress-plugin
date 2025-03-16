const selectors = {
	getIsRegeneratingAudio( state ) {
		return state.isRegeneratingAudio;
	},
	getLanguages( state ) {
		return state.languages;
	},
	getPlayerStyles( state ) {
		return state.playerStyles;
	},
	getSettings( state ) {
		return state.settings;
	},
	getVoices( state ) {
		return state.voices;
	},
};

export default selectors;
