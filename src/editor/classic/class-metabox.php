<?php

declare( strict_types = 1 );

/**
 * BeyondWords Post Metabox.
 *
 * @package BeyondWords\Editor\Classic
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 * @since   7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */

namespace BeyondWords\Editor\Classic;

/**
 * PostMetabox
 *
 * @since 3.0.0
 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
 */
defined( 'ABSPATH' ) || exit;

class Metabox {

	/**
	 * Init.
	 *
	 * @since 4.0.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', [ self::class, 'add_meta_box_callback'] );
	}

	/**
	 * Adds the meta box container.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param string $post_type
	 */
	public static function add_meta_box_callback( $post_type ) {
		$post_types = \BeyondWords\Settings\Utils::get_compatible_post_types();

		if ( ! in_array( $post_type, $post_types ) ) {
			return;
		}

		add_meta_box(
			'beyondwords',
			__( 'BeyondWords', 'speechkit' ),
			[ self::class, 'render_meta_box_content'],
			$post_type,
			'side',
			'default',
			[
				'__back_compat_meta_box' => true,
			]
		);
	}

	/**
	 * Render Meta Box content.
	 *
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post $post The WordPress post ID, or post object.
	 *
	 * @since 3.0.0
	 * @since 3.7.0 Show "Pending review" notice for posts with status of "pending"
	 * @since 4.0.0 Content ID is no longer an int
	 * @since 4.1.0 Add "Player style" and update component display conditions
	 * @since 6.0.0 Make static and add Magic Embed support.
	 */
	public static function render_meta_box_content( $post ) {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$has_content = \BeyondWords\Post\Meta::has_content( $post->ID );

		// Single nonce guarding the Content/Format/Player <select> fields.
		\BeyondWords\Editor\Components\SettingsFields::nonce();

		// Player section: player/errors, Embed, Generate audio, Display player.
		self::heading( __( 'Player', 'speechkit' ) );

		self::errors( $post );

		if ( $has_content ) {
			if ( get_post_status( $post ) === 'pending' ) {
				self::pending_review_notice( $post );
			} else {
				self::player_embed( $post );
			}
		}

		\BeyondWords\Editor\Components\SettingsFields::render_player_section( $post );
		( new \BeyondWords\Editor\Components\GenerateAudio() )::element( $post );

		if ( $has_content ) {
			( new \BeyondWords\Editor\Components\DisplayPlayer() )::element( $post );
		}

		// Content section: Source + Script template.
		echo '<hr />';
		self::heading( __( 'Content', 'speechkit' ) );
		\BeyondWords\Editor\Components\SettingsFields::render_content_section( $post );

		// Format section: Output + Video template + Video size.
		echo '<hr />';
		self::heading( __( 'Format', 'speechkit' ) );
		\BeyondWords\Editor\Components\SettingsFields::render_format_section( $post );

		// Voice section: Language + Voice + Model.
		echo '<hr />';
		self::heading( __( 'Voice', 'speechkit' ) );
		( new \BeyondWords\Editor\Components\SelectVoice() )::element( $post );

		// Data section: Content ID + Fetch button.
		echo '<hr />';
		self::heading( __( 'Data', 'speechkit' ) );
		\BeyondWords\Editor\Components\ContentId::element( $post );

		echo '<hr />';
		self::help();
	}

	/**
	 * Print a settings-section heading.
	 *
	 * The sections mirror the block editor's Player/Content/Format/Voice panels.
	 * A heading gives each group a label because the individual field labels
	 * (Source, Output, Embed…) aren't self-explanatory on their own.
	 *
	 * @since 7.0.0
	 *
	 * @param string $title The section heading.
	 */
	public static function heading( $title ) {
		printf(
			'<h4 class="beyondwords-metabox__heading">%s</h4>',
			esc_html( $title )
		);
	}

	/**
	 * The "Pending review" message, shown instead of the audio player
	 * if the post status in WordPress is "pending".
	 *
	 * This message is displayed instead of the player because the player
	 * cannot be rendered for audio which has been created
	 * with { published: false }.
	 *
	 * @since 3.7.0
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @var int|\WP_Post $post The WordPress post ID, or post object.
	 */
	public static function pending_review_notice( $post ) {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$project_url = sprintf(
			'%s/dashboard/project/%d/content',
			\BeyondWords\Core\Urls::get_dashboard_url(),
			\BeyondWords\Post\Meta::get_project_id( $post->ID )
		);

		?>
		<div id="beyondwords-pending-review-message">
			<?php
			printf(
				/* translators: %s is replaced with the link to the BeyondWords dashboard */
				esc_html__( 'Listen to content saved as “Pending” in the %s.', 'speechkit' ),
				sprintf(
					'<a href="%s" target="_blank" rel="nofollow">%s</a>',
					esc_url( $project_url ),
					esc_html__( 'BeyondWords dashboard', 'speechkit' )
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Embed a player for a WordPress post.
	 *
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 *
	 * @param int|\WP_Post|null $post (Optional) Post ID, or WP_Post object, or null.
	 *
	 * @since 3.x   Introduced
	 * @since 4.0.1 Admin player init is now all in this one function.
	 * @since 6.0.0 Make static and add Magic Embed support.
	 */
	public static function player_embed( $post = null ) {
		$post = get_post( $post );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$project_id  = \BeyondWords\Post\Meta::get_project_id( $post->ID );
		$has_content = \BeyondWords\Post\Meta::has_content( $post->ID );

		if ( ! $project_id || ! $has_content ) {
			return;
		}

		$content_id    = \BeyondWords\Post\Meta::get_content_id( $post->ID );
		$preview_token = \BeyondWords\Post\Meta::get_preview_token( $post->ID );

        // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		?>
		<div id="beyondwords-metabox-player" style="margin: 13px 0;">
		<script defer
			src='<?php echo esc_url( \BeyondWords\Core\Urls::get_js_sdk_url() ); ?>'
			onload='const player = new BeyondWords.Player({
				target: this.parentElement,
				projectId: <?php echo esc_attr( $project_id ); ?>,
				<?php if ( ! empty( $content_id ) ) : ?>
				contentId: "<?php echo esc_attr( $content_id ); ?>",
				<?php else : ?>
				sourceId: "<?php echo esc_attr( $post->ID ); ?>",
				<?php endif; ?>
				previewToken: "<?php echo esc_attr( $preview_token ); ?>",
				adverts: [],
				analyticsConsent: "none",
				introsOutros: [],
				playerStyle: "small",
				widgetStyle: "none",
			});'
		>
		</script>
		</div>
		<?php
        // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	/**
	 * Display errors for the post.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function errors( $post ) {
		$error = \BeyondWords\Post\Meta::get_error_message( $post->ID );

		if ( $error ) :
			?>
			<div id="beyondwords-metabox-errors">
				<div class="beyondwords-error">
					<p>
						<?php echo esc_html( $error ); ?>
					</p>
				</div>
				<?php self::regenerate_instructions(); ?>
			</div>
			<?php
		endif;
	}

	/**
	 * Display help text for the metabox.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function help() {
		?>
		<p id="beyondwords-metabox-help">
			<?php
			printf(
				/* translators: %s is replaced with the link to the support email address */
				esc_html__( 'Need help? Email our support team on %s', 'speechkit' ),
				sprintf( '<a href="%s">%s</a>', 'mailto:support@beyondwords.io', 'support@beyondwords.io' )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Display instructions for regenerating audio.
	 *
	 * @since 6.0.0 Make static.
	 * @since 7.0.0 Refactored to BeyondWords namespace with snake_case methods.
	 */
	public static function regenerate_instructions() {
		?>
		<!-- Update/regenerate -->
		<p>
			<?php
			esc_html_e(
				'To create audio, resolve the error above then select ‘Update’ with ‘Generate audio’ checked.', // phpcs:ignore Generic.Files.LineLength.TooLong
				'speechkit'
			);
			?>
		</p>
		<?php
	}
}
