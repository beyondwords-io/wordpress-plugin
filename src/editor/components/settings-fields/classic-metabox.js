/* global jQuery, beyondwordsSettingsFields */

/*
 * Classic-editor dynamic behaviour for the Content/Format/Player settings
 * fields. Mirrors the block editor's settings-panel logic
 * (src/editor/components/settings-panel/helpers.js):
 *
 * - Source toggles the Script template field.
 * - Output toggles the Video template + Video size fields.
 * - Embed options are derived from Source × Output and recomputed live.
 */
( function ( $ ) {
	'use strict';

	const SOURCE_POST = 'post';
	const SOURCE_SCRIPT = 'script';
	const SOURCE_POST_AND_SCRIPT = 'post_and_script';

	const OUTPUT_AUDIO = 'audio';
	const OUTPUT_VIDEO = 'video';
	const OUTPUT_AUDIO_AND_VIDEO = 'audio_and_video';

	const EMBED_NONE = 'none';

	const labels =
		( beyondwordsSettingsFields &&
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
			this.$source = $( '#beyondwords_source' );
			this.$output = $( '#beyondwords_output' );
			this.$embed = $( '#beyondwords_embed' );

			if ( ! this.$source.length && ! this.$output.length ) {
				return;
			}

			this.$source.on( 'change', () => {
				this.toggleScriptTemplate();
				this.recomputeEmbed();
			} );

			this.$output.on( 'change', () => {
				this.toggleVideoFields();
				this.recomputeEmbed();
			} );

			// Sync initial visibility in case meta and markup drift.
			this.toggleScriptTemplate();
			this.toggleVideoFields();
		},

		toggleScriptTemplate() {
			const show = sourceIncludesScript( this.$source.val() );
			$(
				'#beyondwords-metabox-settings--beyondwords-script-template-id'
			).toggle( show );
		},

		toggleVideoFields() {
			const show = outputIncludesVideo( this.$output.val() );
			$(
				'#beyondwords-metabox-settings--beyondwords-video-template-id'
			).toggle( show );
			$( '#beyondwords-metabox-settings--beyondwords-video-size' ).toggle(
				show
			);
		},

		recomputeEmbed() {
			if ( ! this.$embed.length ) {
				return;
			}

			const source = this.$source.val();
			const output = this.$output.val();
			const options = getEmbedOptions( source, output );
			const previous = this.$embed.val();
			const stillValid = options.some( ( o ) => o.value === previous );
			const selected = stillValid ? previous : EMBED_NONE;

			this.$embed.empty().append(
				options.map( ( option ) =>
					$( '<option></option>' )
						.val( option.value )
						.text( option.label )
						.prop( 'selected', option.value === selected )
				)
			);
		},
	};

	$( document ).ready( function () {
		settingsFields.init();
	} );
} )( jQuery );
