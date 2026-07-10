<?php

declare( strict_types = 1 );

/**
 * BeyondWords Component: Select Voice
 *
 * @package BeyondWords\Editor\Components
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Editor\Components;

/**
 * SelectVoice
 *
 * @since 4.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class SelectVoice {

	/**
	 * Voice "service" that exposes selectable models. Only ElevenLabs voices
	 * carry a `model_id`; every (name, model_id) pair is a distinct voice id.
	 *
	 * @since 7.0.0
	 */
	public const ELEVENLABS_SERVICE = 'ElevenLabs';

	/**
	 * The model listed first in the Model dropdown.
	 *
	 * @since 7.0.0
	 */
	public const DEFAULT_ELEVENLABS_VOICE_MODEL_ID = 'eleven_multilingual_v2';

	/**
	 * Bucket key for voices without an ElevenLabs model_id (e.g. standard voices).
	 *
	 * @since 7.0.0
	 */
	public const STANDARD_MODEL_KEY = 'standard';

	/**
	 * Init.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ self::class, 'rest_api_init_callback'] );

		add_action(
			'wp_loaded',
			function (): void {
				$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

				if ( is_array( $post_types ) ) {
					foreach ( $post_types as $post_type ) {
						add_action( "save_post_{$post_type}", [ self::class, 'save'], 10 );
					}
				}
			}
		);
	}

	/**
	 * HTML output for this component.
	 *
	 * @since 4.0.0
	 * @since 4.5.1 Hide element if no language data exists.
	 * @since 5.4.0 Always display all languages and associated voices.
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return string|null
	 */
	public static function element( $post ) {
		$language_code = self::get_language_code( $post->ID );
		$voice_id      = self::get_voice_id( $post->ID );
		$languages     = self::get_languages();
		$voices        = self::get_voices_for_language( $language_code );

		// The Native filter defaults to native-only, but opens on "All" when the
		// saved voice is not native to the language so it stays visible. Model +
		// Voice are then rendered from the native-scoped set.
		$native_filter   = self::default_native_filter( $voices, $language_code, $voice_id );
		$filtered_voices = self::filter_voices_by_native( $voices, $language_code, $native_filter, $voice_id );

		// "Customize" is opt-in: a post is customised once it has an explicit
		// language or voice. When off we hide the fields and store nothing, so the
		// BeyondWords project defaults apply.
		$customize    = '' !== (string) $language_code || '' !== (string) $voice_id;
		$fields_style = $customize ? '' : 'display: none;';

		wp_nonce_field( 'beyondwords_select_voice', 'beyondwords_select_voice_nonce' );

		self::render_customize_toggle( $customize );
		?>
		<div id="beyondwords-metabox-select-voice--fields" style="<?php echo esc_attr( $fields_style ); ?>">
		<?php
		self::render_language_name_select( $languages, $language_code );
		self::render_accent_select( $languages, $language_code );
		self::render_native_select( $native_filter );
		self::render_model_select( $filtered_voices, $voice_id );
		self::render_voice_select( $filtered_voices, $voice_id );
		self::render_loading_spinner();
		?>
		</div>
		<?php
	}

	/**
	 * Render the "Customize" toggle.
	 *
	 * When unchecked the post uses the project default language and voice and the
	 * language/voice fields are hidden. classic-metabox.js mirrors the visibility
	 * and clears the selects when it is unchecked so save() removes the meta.
	 *
	 * @since 7.0.0
	 *
	 * @param bool $customize Whether Customize is currently enabled.
	 */
	private static function render_customize_toggle( bool $customize ): void {
		?>
		<p
			id="beyondwords-metabox-select-voice--customize"
			class="post-attributes-label-wrapper page-template-label-wrapper"
		>
			<label class="post-attributes-label" for="beyondwords_customize">
				<?php esc_html_e( 'Customize', 'speechkit' ); ?>
			</label>
		</p>
		<input
			type="checkbox"
			id="beyondwords_customize"
			name="beyondwords_customize"
			value="1"
			<?php checked( $customize ); ?>
		/>
		<?php
	}

	/**
	 * Get the language code for a post.
	 *
	 * @since 6.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id The post ID.
	 * @return string|false The language code or false if not set.
	 */
	private static function get_language_code( int $post_id ) {
		$post_language_code = get_post_meta( $post_id, 'beyondwords_language_code', true );
		return $post_language_code ?: '';
	}

	/**
	 * Get the voice ID for a post.
	 *
	 * @since 6.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id The post ID.
	 * @return string|false The voice ID or false if not set.
	 */
	private static function get_voice_id( int $post_id ) {
		$post_voice_id = get_post_meta( $post_id, 'beyondwords_body_voice_id', true );
		return $post_voice_id ?: '';
	}

	/**
	 * Get all available languages.
	 *
	 * Coerces the API result to an array so the language dropdown degrades to
	 * empty when the languages API call fails (network error, WP_Error, non-2xx
	 * status, empty body or invalid JSON). Client::get_languages() is declared
	 * array|null|false, so an unguarded null/false would throw a TypeError
	 * against render_language_select()'s array-typed parameter under
	 * strict_types. Mirrors get_voices_for_language().
	 *
	 * @since 7.0.0
	 *
	 * @return array The languages array, or an empty array on API failure.
	 */
	private static function get_languages(): array {
		$languages = \BeyondWords\Api\Client::get_languages();
		return is_array( $languages ) ? $languages : [];
	}

	/**
	 * Get voices for a language code.
	 *
	 * @since 6.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param string|false $language_code The language code.
	 * @return array The voices array.
	 */
	private static function get_voices_for_language( $language_code ): array {
		if ( $language_code === false || $language_code === '' ) {
			return [];
		}

		$voices = \BeyondWords\Api\Client::get_voices( $language_code );
		return is_array( $voices ) ? $voices : [];
	}

	/**
	 * Whether a language row carries the fields the pickers need.
	 *
	 * @since 7.0.0
	 *
	 * @param mixed $language A language record.
	 *
	 * @return bool
	 */
	private static function is_valid_language( $language ): bool {
		return is_array( $language )
			&& ! empty( $language['code'] )
			&& ! empty( $language['name'] )
			&& ! empty( $language['accent'] );
	}

	/**
	 * The distinct language names across the language rows, in API order.
	 * Mirrors `getLanguageNames()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array $languages The languages array.
	 *
	 * @return string[] The language names.
	 */
	public static function language_names( array $languages ): array {
		$names = [];

		foreach ( $languages as $language ) {
			if ( ! self::is_valid_language( $language ) ) {
				continue;
			}
			if ( ! in_array( $language['name'], $names, true ) ) {
				$names[] = $language['name'];
			}
		}

		return $names;
	}

	/**
	 * The language rows (accents) for a language name, in API order. Mirrors
	 * `getAccentsForName()` in src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array  $languages The languages array.
	 * @param string $name      The language name.
	 *
	 * @return array The matching language rows.
	 */
	public static function accents_for_name( array $languages, string $name ): array {
		if ( '' === $name ) {
			return [];
		}

		return array_values(
			array_filter(
				$languages,
				static function ( $language ) use ( $name ) {
					return self::is_valid_language( $language ) && $language['name'] === $name;
				}
			)
		);
	}

	/**
	 * Find a language row by its code. Mirrors `findLanguageByCode()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array        $languages The languages array.
	 * @param string|false $code      The language code.
	 *
	 * @return array|null The matching language row, or null.
	 */
	public static function find_language_by_code( array $languages, $code ): ?array {
		foreach ( $languages as $language ) {
			if ( self::is_valid_language( $language ) && strval( $language['code'] ) === strval( $code ) ) {
				return $language;
			}
		}

		return null;
	}

	/**
	 * Slim language rows for the classic-editor script: enough to rebuild the
	 * Accent dropdown (and seed the default body voice) when the user picks a
	 * language name, without another API round-trip.
	 *
	 * @since 7.0.0
	 *
	 * @return array The slim language rows.
	 */
	public static function languages_for_script(): array {
		$rows = [];

		foreach ( self::get_languages() as $language ) {
			if ( ! self::is_valid_language( $language ) ) {
				continue;
			}

			$rows[] = [
				'code'           => strval( $language['code'] ),
				'name'           => strval( $language['name'] ),
				'accent'         => strval( $language['accent'] ),
				'defaultVoiceId' => strval( $language['default_voices']['body']['id'] ?? '' ),
			];
		}

		return $rows;
	}

	/**
	 * Render the Language select: one entry per language NAME (e.g. "English").
	 *
	 * The name select is a client-side control only — it carries no `name`
	 * attribute and is not submitted. Its companion Accent select (which keeps
	 * the `beyondwords_language_code` field) resolves the (name, accent) pair
	 * to the stored language code.
	 *
	 * @since 6.0.0 As render_language_select, combining name + accent.
	 * @since 7.0.0 Split into Language (name) + Accent selects.
	 *
	 * @param array        $languages The languages array.
	 * @param string|false $selected_lang_code The selected language code.
	 */
	private static function render_language_name_select( array $languages, $selected_lang_code ): void {
		$selected_language = self::find_language_by_code( $languages, $selected_lang_code );
		$selected_name     = $selected_language['name'] ?? '';
		?>
		<p
			id="beyondwords-metabox-select-voice--language-name"
			class="post-attributes-label-wrapper page-template-label-wrapper"
		>
			<label class="post-attributes-label" for="beyondwords_language_name">
				<?php esc_html_e( 'Language', 'speechkit' ); ?>
			</label>
		</p>
		<select id="beyondwords_language_name" style="width: 100%;">
			<?php
			printf(
				'<option value="" %s>%s</option>',
				selected( '', strval( $selected_name ), false ),
				esc_html__( 'Select a language…', 'speechkit' )
			);
			foreach ( self::language_names( $languages ) as $name ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $name ),
					selected( $name, strval( $selected_name ), false ),
					esc_html( $name )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Render the Accent select: the accents for the selected language name.
	 *
	 * This is the submitted field (`beyondwords_language_code`) — a (name,
	 * accent) pair maps to exactly one language code, so the Accent select
	 * carries the code. Hidden when the language offers a single accent
	 * (nothing to choose), while still submitting that accent's code. Always
	 * renders at least one option so the field posts (save() requires the key
	 * to be present; an empty value means "no language chosen").
	 *
	 * @since 7.0.0
	 *
	 * @param array        $languages The languages array.
	 * @param string|false $selected_lang_code The selected language code.
	 */
	private static function render_accent_select( array $languages, $selected_lang_code ): void {
		$selected_language = self::find_language_by_code( $languages, $selected_lang_code );
		$selected_name     = $selected_language['name'] ?? '';
		$accents           = self::accents_for_name( $languages, strval( $selected_name ) );

		$show_accent  = count( $accents ) > 1;
		$accent_style = $show_accent ? '' : 'display: none;';
		?>
		<div
			id="beyondwords-metabox-select-voice--accent"
			class="beyondwords-metabox-settings__field"
			style="<?php echo esc_attr( $accent_style ); ?>"
		>
			<p class="post-attributes-label-wrapper page-template-label-wrapper">
				<label class="post-attributes-label" for="beyondwords_language_code">
					<?php esc_html_e( 'Accent', 'speechkit' ); ?>
				</label>
			</p>
			<select id="beyondwords_language_code" name="beyondwords_language_code" style="width: 100%;">
				<?php
				if ( empty( $accents ) ) {
					echo '<option value=""></option>';
				}
				foreach ( $accents as $language ) {
					printf(
						'<option value="%s" data-default-voice-id="%s" %s>%s</option>',
						esc_attr( $language['code'] ),
						esc_attr( $language['default_voices']['body']['id'] ?? '' ),
						selected( strval( $language['code'] ), strval( $selected_lang_code ), false ),
						esc_html( $language['accent'] )
					);
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render the Native select: filter the Voice list to voices native to the
	 * language, or all (multilingual) voices.
	 *
	 * A client-side filter only — it carries no `name` and is not submitted. The
	 * persisted value is the voice id from the Voice select. Mirrors the
	 * `beyondwords--native` control in
	 * src/editor/components/settings-panel/voice-section.js.
	 *
	 * @since 7.0.0
	 *
	 * @param string $native_filter The selected filter ("native" or "all").
	 */
	private static function render_native_select( string $native_filter ): void {
		$options = [
			'native' => __( 'Native', 'speechkit' ),
			'all'    => __( 'All', 'speechkit' ),
		];
		?>
		<div
			id="beyondwords-metabox-select-voice--native"
			class="beyondwords-metabox-settings__field"
		>
			<p class="post-attributes-label-wrapper page-template-label-wrapper">
				<label class="post-attributes-label" for="beyondwords_native">
					<?php esc_html_e( 'Native', 'speechkit' ); ?>
				</label>
			</p>
			<select id="beyondwords_native" style="width: 100%;">
				<?php
				foreach ( $options as $value => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $value ),
						selected( $value, $native_filter, false ),
						esc_html( $label )
					);
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render the Model select: a language-level filter over the voices.
	 *
	 * Each ElevenLabs model_id is a bucket, plus a single "Standard" bucket for
	 * non-ElevenLabs voices. Picking a model narrows the Voice dropdown to the
	 * voices that offer it. The Model select carries no `name` — it is a
	 * client-side filter and is not submitted; the persisted value is the voice
	 * id from the Voice select. The dropdown is hidden when a language offers a
	 * single bucket (there is nothing to narrow by).
	 *
	 * @since 7.0.0
	 *
	 * @param array        $voices The voices array.
	 * @param string|false $selected_voice_id The selected voice ID.
	 */
	private static function render_model_select( array $voices, $selected_voice_id ): void {
		$selected_voice = self::find_voice( $voices, $selected_voice_id );
		$selected_key   = $selected_voice ? self::voice_model_key( $selected_voice ) : '';

		$models     = self::language_models( $voices );
		$show_model = count( $models ) > 1;
		?>
		<div
			id="beyondwords-metabox-select-voice--model"
			class="beyondwords-metabox-settings__field"
			<?php echo $show_model ? '' : 'style="display: none;"'; ?>
		>
			<p class="post-attributes-label-wrapper page-template-label-wrapper">
				<label class="post-attributes-label" for="beyondwords_model">
					<?php esc_html_e( 'Model', 'speechkit' ); ?>
				</label>
			</p>
			<select id="beyondwords_model" style="width: 100%;">
				<?php
				printf(
					'<option value="" %s>%s</option>',
					selected( '', $show_model ? strval( $selected_key ) : '', false ),
					esc_html__( 'Select a model', 'speechkit' )
				);
				foreach ( $models as $model ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $model['key'] ),
						selected( strval( $model['key'] ), $show_model ? strval( $selected_key ) : '', false ),
						esc_html( $model['label'] )
					);
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render the Voice select: the voices in the currently selected model bucket.
	 *
	 * This is the saved field (`beyondwords_voice_id`) — its value is the voice
	 * id, which carries the model. With a single bucket every voice is listed;
	 * with several the list is scoped to the selected model and the field is
	 * hidden until a model is chosen.
	 *
	 * @since 6.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 * @since 7.0.0 Model-first: scope the Voice list to the selected model.
	 *
	 * @param array        $voices The voices array.
	 * @param string|false $selected_voice_id The selected voice ID.
	 */
	private static function render_voice_select( array $voices, $selected_voice_id ): void {
		$selected_voice = self::find_voice( $voices, $selected_voice_id );
		$selected_key   = $selected_voice ? self::voice_model_key( $selected_voice ) : '';

		$models     = self::language_models( $voices );
		$show_model = count( $models ) > 1;

		// Single bucket → list every voice; several → scope to the chosen model.
		if ( $show_model ) {
			$bucket_voices = array_values(
				array_filter(
					$voices,
					static function ( $voice ) use ( $selected_key ) {
						return self::voice_model_key( $voice ) === $selected_key;
					}
				)
			);
		} else {
			$bucket_voices = array_values( $voices );
		}

		// Model gates the Voice list: hide it until a model is chosen. With a
		// single bucket there is no Model dropdown, so the Voice list shows now.
		$show_voice  = count( $voices ) > 0 && ( ! $show_model || '' !== strval( $selected_key ) );
		$voice_style = $show_voice ? '' : 'display: none;';
		?>
		<div
			id="beyondwords-metabox-select-voice--voice-id"
			class="beyondwords-metabox-settings__field"
			style="<?php echo esc_attr( $voice_style ); ?>"
		>
			<p class="post-attributes-label-wrapper page-template-label-wrapper">
				<label class="post-attributes-label" for="beyondwords_voice_id">
					<?php esc_html_e( 'Voice', 'speechkit' ); ?>
				</label>
			</p>
			<select id="beyondwords_voice_id" name="beyondwords_voice_id" style="width: 100%;">
				<?php
				printf(
					'<option value="" %s>%s</option>',
					selected( '', strval( $selected_voice_id ), false ),
					esc_html__( 'Select a voice', 'speechkit' )
				);
				foreach ( $bucket_voices as $voice ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $voice['id'] ?? '' ),
						selected( strval( $voice['id'] ?? '' ), strval( $selected_voice_id ), false ),
						esc_html( $voice['name'] ?? '' )
					);
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Find a voice record by id.
	 *
	 * @since 7.0.0
	 *
	 * @param array        $voices   The voices array.
	 * @param string|false $voice_id The voice ID to find.
	 *
	 * @return array|null The matching voice record, or null.
	 */
	private static function find_voice( array $voices, $voice_id ): ?array {
		foreach ( $voices as $voice ) {
			if ( strval( $voice['id'] ?? '' ) === strval( $voice_id ) ) {
				return $voice;
			}
		}
		return null;
	}

	/**
	 * A voice's primary (native) language code. Handles the API `language`
	 * string, the `{ code }` object form, and falls back to the first entry of
	 * `languages[]`. Mirrors `voicePrimaryCode()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array $voice A voice record.
	 *
	 * @return string The primary language code, or '' when unknown.
	 */
	public static function voice_primary_code( array $voice ): string {
		$language = $voice['language'] ?? null;

		if ( is_string( $language ) ) {
			return $language;
		}
		if ( is_array( $language ) && ! empty( $language['code'] ) ) {
			return (string) $language['code'];
		}
		return (string) ( $voice['languages'][0]['code'] ?? '' );
	}

	/**
	 * Whether a voice is native to a language code — that code is its primary
	 * language. A voice with no determinable primary language is treated as
	 * native (never hidden). Mirrors `voiceIsNative()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array  $voice A voice record.
	 * @param string $code  The language code.
	 *
	 * @return bool
	 */
	public static function voice_is_native( array $voice, string $code ): bool {
		$primary = self::voice_primary_code( $voice );
		if ( '' === $primary ) {
			return true;
		}
		return $primary === $code;
	}

	/**
	 * Apply the Native filter to a language's voices — native-only, or all. The
	 * voice identified by `$keep_id` (the current selection) is always kept, so
	 * the saved voice is never dropped. Mirrors `filterVoicesByNative()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array        $voices        The voices array.
	 * @param string       $code          The language code.
	 * @param string       $native_filter "native" or "all".
	 * @param string|false $keep_id       The voice id to always keep.
	 *
	 * @return array The filtered voices.
	 */
	public static function filter_voices_by_native( array $voices, string $code, string $native_filter, $keep_id ): array {
		if ( 'all' === $native_filter ) {
			$result = $voices;
		} else {
			$result = array_values(
				array_filter(
					$voices,
					static function ( $voice ) use ( $code ) {
						return self::voice_is_native( $voice, $code );
					}
				)
			);
		}

		$keep_id = strval( $keep_id );
		if ( '' === $keep_id ) {
			return $result;
		}

		foreach ( $result as $voice ) {
			if ( strval( $voice['id'] ?? '' ) === $keep_id ) {
				return $result;
			}
		}

		$saved = self::find_voice( $voices, $keep_id );
		if ( $saved ) {
			$result[] = $saved;
		}

		return $result;
	}

	/**
	 * The Native filter to open with: "all" when the saved voice is not native
	 * to the language (so it stays visible), otherwise "native".
	 *
	 * @since 7.0.0
	 *
	 * @param array        $voices        The voices array.
	 * @param string|false $language_code The language code.
	 * @param string|false $voice_id      The saved voice id.
	 *
	 * @return string "native" or "all".
	 */
	public static function default_native_filter( array $voices, $language_code, $voice_id ): string {
		$saved = self::find_voice( $voices, $voice_id );

		if ( $saved && '' !== strval( $language_code ) && ! self::voice_is_native( $saved, strval( $language_code ) ) ) {
			return 'all';
		}

		return 'native';
	}

	/**
	 * The model bucket key for a voice: its ElevenLabs model_id, or the shared
	 * Standard bucket for any other service. Mirrors `voiceModelKey()` in
	 * src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array $voice A voice record.
	 *
	 * @return string The model bucket key.
	 */
	public static function voice_model_key( array $voice ): string {
		if (
			( $voice['service'] ?? '' ) === self::ELEVENLABS_SERVICE &&
			isset( $voice['model_id'] ) &&
			is_string( $voice['model_id'] )
		) {
			return $voice['model_id'];
		}
		return self::STANDARD_MODEL_KEY;
	}

	/**
	 * The distinct model buckets across a language's voices, as `[key, label]`
	 * pairs for the Model dropdown — ElevenLabs models first (the default
	 * leading), then a single Standard bucket if present. Mirrors
	 * `getLanguageModels()` in src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param array $voices All voices for the current language.
	 *
	 * @return array The Model dropdown options.
	 */
	public static function language_models( array $voices ): array {
		$model_ids    = [];
		$has_standard = false;

		foreach ( $voices as $voice ) {
			$key = self::voice_model_key( $voice );
			if ( self::STANDARD_MODEL_KEY === $key ) {
				$has_standard = true;
			} elseif ( ! in_array( $key, $model_ids, true ) ) {
				$model_ids[] = $key;
			}
		}

		// Stable sort (PHP 8+): the default model leads, the rest keep API order.
		usort(
			$model_ids,
			static function ( $a, $b ) {
				if ( $a === self::DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
					return -1;
				}
				if ( $b === self::DEFAULT_ELEVENLABS_VOICE_MODEL_ID ) {
					return 1;
				}
				return 0;
			}
		);

		$models = array_map(
			static function ( $key ) {
				return [
					'key'   => $key,
					'label' => self::voice_model_label( $key ),
				];
			},
			$model_ids
		);

		if ( $has_standard ) {
			$models[] = [
				'key'   => self::STANDARD_MODEL_KEY,
				'label' => __( 'Legacy', 'speechkit' ),
			];
		}

		return $models;
	}

	/**
	 * Human label for a voice model_id slug. Unknown slugs fall back to a
	 * title-cased version of the slug minus the `eleven_` prefix. Mirrors
	 * `voiceModelLabel()` in src/editor/components/settings-panel/helpers.js.
	 *
	 * @since 7.0.0
	 *
	 * @param string $model_id The model_id slug (e.g. `eleven_flash_v2_5`).
	 *
	 * @return string A display label.
	 */
	public static function voice_model_label( string $model_id ): string {
		$labels = [
			'eleven_v3'              => __( 'v3', 'speechkit' ),
			'eleven_multilingual_v2' => __( 'Multilingual v2', 'speechkit' ),
			'eleven_flash_v2_5'      => __( 'Flash v2.5', 'speechkit' ),
			'eleven_turbo_v2_5'      => __( 'Turbo v2.5', 'speechkit' ),
		];

		if ( isset( $labels[ $model_id ] ) ) {
			return $labels[ $model_id ];
		}

		$slug = preg_replace( '/^eleven_/', '', $model_id );
		$slug = str_replace( '_', ' ', (string) $slug );

		return ucwords( $slug );
	}

	/**
	 * Render the loading spinner.
	 *
	 * @since 6.0.0
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	private static function render_loading_spinner(): void {
		?>
		<img
			src="/wp-admin/images/spinner.gif"
			class="beyondwords-settings__loader"
			style="display:none; padding: 3px 0;"
		/>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// "save_post" can be triggered at other times, so verify this request came from the our component
		if (
			! isset( $_POST['beyondwords_language_code'] ) ||
			! isset( $_POST['beyondwords_voice_id'] ) ||
			! isset( $_POST['beyondwords_select_voice_nonce'] )
		) {
			return $post_id;
		}

		// "save_post" can be triggered at other times, so verify this request came from the our component
		if (
			! wp_verify_nonce(
				sanitize_key( $_POST['beyondwords_select_voice_nonce'] ),
				'beyondwords_select_voice'
			)
		) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$language_code = sanitize_text_field( wp_unslash( $_POST['beyondwords_language_code'] ) );

		if ( ! empty( $language_code ) ) {
			update_post_meta( $post_id, 'beyondwords_language_code', $language_code );
		} else {
			delete_post_meta( $post_id, 'beyondwords_language_code' );
		}

		$voice_id = sanitize_text_field( wp_unslash( $_POST['beyondwords_voice_id'] ) );

		if ( ! empty( $voice_id ) ) {
			update_post_meta( $post_id, 'beyondwords_body_voice_id', $voice_id );
		} else {
			delete_post_meta( $post_id, 'beyondwords_body_voice_id' );
		}

		return $post_id;
	}

	/**
	 * Register WP REST API route
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @return void
	 */
	public static function rest_api_init_callback() {
		// Languages endpoint
		register_rest_route(
			'beyondwords/v1',
			'/languages',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'languages_rest_api_response'],
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			]
		);

		// Voices endpoint
		register_rest_route(
			'beyondwords/v1',
			'/languages/(?P<languageCode>[a-zA-Z0-9-_]+)/voices',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'voices_rest_api_response'],
				'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			]
		);
	}

	/**
	 * "Languages" WP REST API response (required for the Gutenberg editor).
	 *
	 * @since 4.0.0
	 * @since 5.4.0 No longer filter by "Languages" plugin setting.
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @return \WP_REST_Response
	 */
	public static function languages_rest_api_response() {
		$languages = \BeyondWords\Api\Client::get_languages();

		return new \WP_REST_Response( $languages );
	}

	/**
	 * "Voices" WP REST API response (required for the Gutenberg editor
	 * and Block Editor).
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @return \WP_REST_Response
	 */
	public static function voices_rest_api_response( \WP_REST_Request $data ) {
		$params = $data->get_url_params();

		$voices = \BeyondWords\Api\Client::get_voices( $params['languageCode'] );

		return new \WP_REST_Response( $voices );
	}
}
