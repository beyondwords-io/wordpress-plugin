<?php

declare(strict_types=1);

/**
 * BeyondWords Post Metabox.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Post\Metabox;

use Beyondwords\Wordpress\Component\Post\GenerateAudio\GenerateAudio;
use Beyondwords\Wordpress\Component\Post\DisplayPlayer\DisplayPlayer;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Post\SelectVoice\SelectVoice;
use Beyondwords\Wordpress\Component\Post\PlayerContent\PlayerContent;
use Beyondwords\Wordpress\Component\Post\PlayerStyle\PlayerStyle;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;
use Beyondwords\Wordpress\Core\Environment;

/**
 * PostMetabox
 *
 * @since 3.0.0
 */
class Metabox
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        add_action("add_meta_boxes", array($this, 'addMetaBox'));
    }

    /**
     * Enque JS for Bulk Edit feature.
     */
    public function adminEnqueueScripts($hook)
    {
        // Only enqueue for Post screens
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            // Register the Classic Editor "Metabox" CSS
            wp_enqueue_style(
                'beyondwords-Metabox',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Post/Metabox/Metabox.css',
                false,
                BEYONDWORDS__PLUGIN_VERSION
            );
        }
    }

    /**
     * Adds the meta box container.
     *
     * @param string $postType
     */
    public function addMetaBox($postType)
    {
        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (is_array($postTypes) && ! in_array($postType, $postTypes)) {
            return;
        }

        add_meta_box(
            'beyondwords',
            __('BeyondWords', 'speechkit'),
            array($this, 'renderMetaBoxContent'),
            $postType,
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
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @since 3.0.0
     * @since 3.7.0 Show "Pending review" notice for posts with status of "pending"
     * @since 4.0.0 Content ID is no longer an int
     * @since 4.1.0 Add "Player style" and update component display conditions
     */
    public function renderMetaBoxContent($post)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            return;
        }

        // Show errors for posts with/without audio
        $this->errors($post);

        $contentId = PostMetaUtils::getContentId($post->ID);

        if ($contentId) {
            // Enable these components for posts with audio
            if (get_post_status($post) === 'pending') {
                $this->pendingReviewNotice($post);
            } else {
                $this->playerEmbed($post);
            }
            echo '<hr />';
            (new DisplayPlayer())->element($post);
        } else {
            $this->errors($post);
            // Enable these components for posts without audio
            (new GenerateAudio())->element($post);
        }

        // Enable these components for posts with/without audio
        (new SelectVoice())->element($post);
        (new PlayerStyle())->element($post);
        (new PlayerContent())->element($post);

        echo '<hr />';
        $this->help();
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
     *
     * @var \WP_Post $post Post.
     */
    public function pendingReviewNotice($post)
    {
        $projectUrl = sprintf(
            '%s/dashboard/project/%d/content',
            Environment::getDashboardUrl(),
            PostMetaUtils::getProjectId($post)
        );

        ?>
        <div id="beyondwords-pending-review-message">
            <?php
            printf(
                /* translators: %s is replaced with the link to the BeyondWords dashboard */
                esc_html__('Listen to content saved as “Pending” in the %s.', 'speechkit'),
                sprintf(
                    '<a href="%s" target="_blank" rel="nofollow">%s</a>',
                    esc_url($projectUrl),
                    esc_html__('BeyondWords dashboard', 'speechkit')
                )
            );
            ?>
        </div>
        <?php
    }

    /**
     * Embed a player for a WordPress post.
     *
     * @param int|WP_Post (Optional) Post ID or WP_Post object. Default is global $post.
     *
     * @since 3.x   Introduced
     * @since 4.0.1 Admin player init is now all in this one function.
     */
    public function playerEmbed($post = null)
    {
        $post = get_post($post);

        if (!($post instanceof \WP_Post)) {
            return;
        }

        $projectId    = PostMetaUtils::getProjectId($post->ID);
        $contentId    = PostMetaUtils::getContentId($post->ID);
        $previewToken = PostMetaUtils::getPreviewToken($post->ID);

        if (! $projectId || ! $contentId) {
            return;
        }

        ?>
        <script async defer
            src='<?php echo esc_url(Environment::getJsSdkUrl()); ?>'
            onload='const player = new BeyondWords.Player({
                target: this,
                projectId: <?php echo esc_attr($projectId); ?>,
                contentId: "<?php echo esc_attr($contentId); ?>",
                previewToken: "<?php echo esc_attr($previewToken); ?>",
                adverts: [],
                analyticsConsent: "none",
                introsOutros: [],
                playerStyle: "small",
                widgetStyle: "none",
            });'
        >
        </script>
        <?php
    }

    public function errors($post)
    {
        $error = PostMetaUtils::getErrorMessage($post->ID);

        if ($error) :
            ?>
            <div id="beyondwords-metabox-errors">
                <div class="beyondwords-error">
                    <p>
                        <?php echo esc_html($error); ?>
                    </p>
                </div>
                <?php $this->regenerateInstructions(); ?>
            </div>
            <?php
        endif;
    }

    public function help()
    {
        ?>
        <p id="beyondwords-metabox-help">
            <?php
            printf(
                /* translators: %s is replaced with the link to the support email address */
                esc_html__('Need help? Email our support team on %s', 'speechkit'),
                sprintf('<a href="%s">%s</a>', 'mailto:support@beyondwords.io', 'support@beyondwords.io')
            );
            ?>
        </p>
        <?php
    }

    public function regenerateInstructions()
    {
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
