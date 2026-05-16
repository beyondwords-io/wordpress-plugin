const actions = {
	fetchFromAPI( path ) {
		return {
			type: 'FETCH_FROM_API',
			path,
		};
	},
	setIsRegeneratingAudio( value ) {
		return {
			type: 'SET_IS_REGENERATING_AUDIO',
			value,
		};
	},
	setSettings( value ) {
		return {
			type: 'SET_SETTINGS',
			value,
		};
	},
	setPlayerStyles( value ) {
		return {
			type: 'SET_PLAYER_STYLES',
			value,
		};
	},
	setLanguages( value ) {
		return {
			type: 'SET_LANGUAGES',
			value,
		};
	},
	setVoices( value ) {
		return {
			type: 'SET_VOICES',
			value,
		};
	},
	setScriptTemplates( value ) {
		return {
			type: 'SET_SCRIPT_TEMPLATES',
			value,
		};
	},
	setVideoSizes( value ) {
		return {
			type: 'SET_VIDEO_SIZES',
			value,
		};
	},
};

export default actions;
