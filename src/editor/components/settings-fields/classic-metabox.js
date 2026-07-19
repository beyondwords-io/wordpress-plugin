/* global beyondwordsSettingsFields */

/*
 * Classic-editor dynamic behaviour for the Content/Format/Player settings
 * fields (show/hide + Embed recompute). Mirrors settings-panel/helpers.js.
 */
( function () {
	'use strict';

	const SOURCE_POST = 'post';
	const SOURCE_SCRIPT = 'script';
	const SOURCE_POST_AND_SCRIPT = 'post_and_script';

	const OUTPUT_AUDIO = 'audio';
	const OUTPUT_VIDEO = 'video';
	const OUTPUT_AUDIO_AND_VIDEO = 'audio_and_video';

	const EMBED_NONE = 'none';

	const labels =
		( typeof beyondwordsSettingsFields !== 'undefined' &&
			beyondwordsSettingsFields.embedLabels ) ||
		{};

	const sourceIncludesPost = ( source ) =>
		source === SOURCE_POST || source === SOURCE_POST_AND_SCRIPT;

	const sourceIncludesScript = ( source ) =>
		source === SOURCE_SCRIPT || source === SOURCE_POST_AND_SCRIPT;

	const outputIncludesAudio = ( output ) =>
		output === OUTPUT_AUDIO || output === OUTPUT_AUDIO_AND_VIDEO;

	const outputIncludesVideo = ( output ) =>
		output === OUTPUT_VIDEO || output === OUTPUT_AUDIO_AND_VIDEO;

	/**
	 * Toggle an element's visibility by id.
	 *
	 * @param {string}  id   The element id.
	 * @param {boolean} show Whether the element should be visible.
	 */
	const toggle = ( id, show ) => {
		const el = document.getElementById( id );
		if ( el ) {
			el.style.display = show ? '' : 'none';
		}
	};

	/**
	 * Derive the valid Embed options from the current Source × Output.
	 *
	 * @param {string} source The current source value.
	 * @param {string} output The current output value.
	 *
	 * @return {Array<{label: string, value: string}>} Embed options.
	 */
	const getEmbedOptions = ( source, output ) => {
		const options = [ { label: labels.none || 'None', value: EMBED_NONE } ];

		if ( outputIncludesAudio( output ) ) {
			if ( sourceIncludesPost( source ) ) {
				options.push( {
					label: labels.audio_post || 'Audio (post)',
					value: 'audio_post',
				} );
			}
			if ( sourceIncludesScript( source ) ) {
				options.push( {
					label: labels.audio_script || 'Audio (script)',
					value: 'audio_script',
				} );
			}
		}

		if ( outputIncludesVideo( output ) ) {
			if ( sourceIncludesPost( source ) ) {
				options.push( {
					label: labels.video_post || 'Video (post)',
					value: 'video_post',
				} );
			}
			if ( sourceIncludesScript( source ) ) {
				options.push( {
					label: labels.video_script || 'Video (script)',
					value: 'video_script',
				} );
			}
		}

		return options;
	};

	const settingsFields = {
		init() {
			this.source = document.getElementById( 'beyondwords_source' );
			this.output = document.getElementById( 'beyondwords_output' );
			this.embed = document.getElementById( 'beyondwords_embed' );

			if ( ! this.source && ! this.output ) {
				return;
			}

			if ( this.source ) {
				this.source.addEventListener( 'change', () => {
					this.toggleScriptTemplate();
					this.recomputeEmbed();
				} );
			}

			if ( this.output ) {
				this.output.addEventListener( 'change', () => {
					this.toggleVideoFields();
					this.recomputeEmbed();
				} );
			}

			// Sync initial visibility in case meta and markup drift.
			this.toggleScriptTemplate();
			this.toggleVideoFields();
		},

		toggleScriptTemplate() {
			const show = this.source
				? sourceIncludesScript( this.source.value )
				: false;
			toggle(
				'beyondwords-metabox-settings--beyondwords-script-template-id',
				show
			);
		},

		toggleVideoFields() {
			const show = this.output
				? outputIncludesVideo( this.output.value )
				: false;
			toggle(
				'beyondwords-metabox-settings--beyondwords-video-template-id',
				show
			);
			toggle(
				'beyondwords-metabox-settings--beyondwords-video-size',
				show
			);
		},

		recomputeEmbed() {
			if ( ! this.embed ) {
				return;
			}

			const source = this.source ? this.source.value : SOURCE_POST;
			const output = this.output ? this.output.value : OUTPUT_AUDIO;
			const options = getEmbedOptions( source, output );
			const previous = this.embed.value;
			const stillValid = options.some( ( o ) => o.value === previous );
			const selected = stillValid ? previous : EMBED_NONE;

			this.embed.replaceChildren(
				...options.map( ( option ) => {
					const el = document.createElement( 'option' );
					el.value = option.value;
					el.textContent = option.label;
					el.selected = option.value === selected;
					return el;
				} )
			);
		},
	};

	if ( document.readyState !== 'loading' ) {
		settingsFields.init();
	} else {
		document.addEventListener( 'DOMContentLoaded', () =>
			settingsFields.init()
		);
	}
} )();
