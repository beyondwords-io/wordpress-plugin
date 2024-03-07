<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Compatibility\Elementor;

use Elementor\Controls_Manager;
use Elementor\Utils as ElementorUtils;
use Elementor\Core\DocumentTypes\PageBase as PageBase;
use Elementor\Modules\Library\Documents\Page as LibraryPageDocument;
use WP_Post;
use WP_Screen;
use Beyondwords\Wordpress\Compatibility\Elementor\Controls\InspectText;
use Beyondwords\Wordpress\Compatibility\Elementor\Controls\InspectTextarea;
use Beyondwords\Wordpress\Compatibility\Elementor\Controls\Player;
use Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections\Beyondwords as BeyondwordsControlsSection;
use Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections\Help as HelpControlsSection;
use Beyondwords\Wordpress\Compatibility\Elementor\ControlsSections\Inspect as InspectControlsSection;
use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

/**
 * Integrates the BeyondWords Sidebar in the Elementor editor.
 *
 * @codeCoverageIgnore
 *
 * @since 3.10.0
 */
class Elementor
{
    /**
     * The identifier for the Elementor tab.
     *
     * @var string
     */
    public const BEYONDWORDS_TAB = 'beyondwords-tab';

    /**
     * Represents the post.
     *
     * @var WP_Post|null
     */
    protected $post;

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        $this->registerHooks();
    }

    /**
     * Initializes the integration.
     *
     * This is the place to register hooks and filters.
     *
     * @return void
     */
    public function registerHooks()
    {
        if (\did_action('elementor/init')) {
            $this->addTab();
        } else {
            \add_action('elementor/init', [ $this, 'addTab' ]);
        }

        \add_action('elementor/ajax/register_actions', [$this, 'registerAjaxActions']);

        \add_action('elementor/controls/register', [$this, 'registerControls']);
        \add_action('elementor/documents/register_controls', [ $this, 'registerDocumentControls' ]);
        \add_action('elementor/document/before_save', [$this, 'beforeDocumentSave'], 10, 2);

        \add_action('elementor/editor/before_enqueue_styles', [$this, 'beforeEnqueueStyles']);
        \add_action('elementor/editor/after_enqueue_styles', [$this, 'afterEnqueueStyles']);

        \add_action('elementor/editor/before_enqueue_scripts', [$this, 'beforeEnqueueScripts' ]);
        \add_action('elementor/editor/after_enqueue_scripts', [$this, 'afterEnqueueScripts' ]);
    }

    /**
     * Register ajax actions.
     *
     * Add new actions to handle data after an ajax requests returned.
     *
     * Fired by `elementor/ajax/register_actions` action.
     *
     * @since 3.10.0
     *
     * @param Ajax $ajax_manager
     */
    public function registerAjaxActions($ajaxManager)
    {
        $ajaxManager->register_ajax_action('get_beyondwords_data', [$this, 'ajaxGetBeyondwordsData']);
    }

    /**
     * Ajax request to get BeyondWords data from post metadata.
     *
     * Get BeyondWords data using an ajax request.
     *
     * @since 3.10.0
     *
     * @param array $request Ajax request.
     *
     * @return array Ajax response data.
     */
    public function ajaxGetBeyondwordsData($request)
    {
        if (empty($request) || empty($request['editor_post_id'])) {
            return;
        }

        $editor_post_id = absint($request['editor_post_id']);

        if (! get_post($editor_post_id)) {
            throw new \Exception(esc_html__('Post not found.', 'speechkit'));
        }

        return [
            'post_id'                => $editor_post_id,
            'beyondwords_project_id' => PostMetaUtils::getProjectId($editor_post_id),
            'beyondwords_content_id' => PostMetaUtils::getContentId($editor_post_id),
            'beyondwords_disabled'   => PostMetaUtils::getDisabled($editor_post_id),
        ];
    }

    /**
     * Registers our Elementor controls.
     */
    public function registerControls($controlsManager)
    {
        $controlsManager->register(new Player());
    }

    /**
     * Before enqueue scripts.
     *
     * @since 3.10.0
     * @access public
     */
    public function beforeEnqueueScripts()
    {
        $assetFile = include BEYONDWORDS__PLUGIN_DIR . 'build/elementor.asset.php';

        wp_register_script(
            'beyondwords-elementor',
            BEYONDWORDS__PLUGIN_URI . 'build/elementor.js',
            $assetFile['dependencies'],
            $assetFile['version'],
            true
        );
    }

    /**
     * After enqueue scripts.
     *
     * @since 3.10.0
     * @access public
     */
    public function afterEnqueueScripts()
    {
        wp_enqueue_script('beyondwords-elementor');
    }

    /**
     * Before enqueue styles.
     */
    public function beforeEnqueueStyles()
    {
        wp_register_style(
            'beyondwords-elementor',
            plugins_url('css/beyondwords-tab.css', __FILE__),
            array(),
            BEYONDWORDS__PLUGIN_VERSION
        );
    }

    /**
     * After enqueue styles.
     */
    public function afterEnqueueStyles()
    {
        wp_enqueue_style('beyondwords-elementor');
    }

    /**
     * Register a panel tab slug, in order to allow adding controls to this tab.
     */
    public function addTab()
    {
        Controls_Manager::add_tab($this::BEYONDWORDS_TAB, __('BeyondWords', 'speechkit'));
    }

    /**
     * Register additional document controls.
     *
     * @param PageBase $document The PageBase document.
     */
    public function registerDocumentControls($document)
    {
        BeyondwordsControlsSection::registerControls($document, self::BEYONDWORDS_TAB);
        HelpControlsSection::registerControls($document, self::BEYONDWORDS_TAB);
    }

    /**
     * Before document save.
     *
     * elementor/document/after_save was not working as expected, so we use the
     * Elementors elementor/document/before_save hook for our checks.
     *
     * Fires when document save starts on Elementor.
     *
     * @param \Elementor\Core\Base\Document $document The current document.
     * @param array $data.
     **/
    public function beforeDocumentSave($document, $data)
    {
        $postId = $document->get_post()->ID;

        if (! empty($data['settings'])) {
            $postId = $document->get_post()->ID;
            $settings = $data['settings'];

            if (isset($settings['control_beyondwords_generate_audio'])) {
                switch ($settings['control_beyondwords_generate_audio']) {
                    case 'yes':
                        update_post_meta($postId, 'beyondwords_generate_audio', '1');
                        break;
                    case '':
                        update_post_meta($postId, 'beyondwords_generate_audio', '0');
                        break;
                }
            }

            if (isset($settings['control_beyondwords_display_player'])) {
                switch ($settings['control_beyondwords_display_player']) {
                    case 'yes':
                        delete_post_meta($postId, 'beyondwords_disabled');
                        break;
                    case '':
                        update_post_meta($postId, 'beyondwords_disabled', '1');
                        break;
                }
            }
        }
    }

    /**
     * Check whether the Elementor plugin has been installed & activated.
     *
     * If it has then we impose certain conditions to ensure compatibility.
     *
     * @since 4.0.0
     *
     * @param int|WP_Post $post The WordPress post ID, or post object.
     *
     * @return boolean
     **/
    public static function isElementorActivated()
    {
        return is_admin() && (
            in_array(
                'elementor/elementor.php',
                apply_filters('active_plugins', get_option('active_plugins'))
            )
        );
    }
}
