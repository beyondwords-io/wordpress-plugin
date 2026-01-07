<?php

declare(strict_types=1);

/**
 * Setting: PreselectGenerateAudio
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\PreselectGenerateAudio;

use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * PreselectGenerateAudio
 *
 * @since 3.0.0
 */
defined('ABSPATH') || exit;

class PreselectGenerateAudio
{
    /**
     * Option name.
     *
     * @since 5.0.0
     */
    public const OPTION_NAME = 'beyondwords_preselect';

    public const DEFAULT_PRESELECT = [
        'post' => '1',
        'page' => '1',
    ];

    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     */
    public static function init()
    {
        add_action('admin_init', [self::class, 'addSetting']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    /**
     * Init setting.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     */
    public static function addSetting()
    {
        register_setting(
            'beyondwords_content_settings',
            self::OPTION_NAME,
            [
                'default' => self::DEFAULT_PRESELECT,
            ]
        );

        add_settings_field(
            'beyondwords-preselect',
            __('Preselect ‘Generate audio’', 'speechkit'),
            [self::class, 'render'],
            'beyondwords_content',
            'content'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function render()
    {
        $postTypes = SettingsUtils::getCompatiblePostTypes();

        if (count($postTypes) === 0) :
            ?>
            <p class="description">
                <?php
                esc_html_e(
                    'No compatible post types found. This plugin will only work with post types that support custom fields.', // phpcs:ignore Generic.Files.LineLength.TooLong
                    'speechkit'
                );
                ?>
            </p>
            <?php
            return;
        endif;

        foreach ($postTypes as $name) :
            $postType = get_post_type_object($name);
            ?>
            <div class="beyondwords-setting__preselect--post-type">
                <label>
                    <input
                        type="checkbox"
                        name="<?php echo esc_attr(self::OPTION_NAME); ?>[<?php echo esc_attr($postType->name); ?>]"
                        value="1"
                        <?php checked(self::postTypeIsSelected($postType)); ?>
                    />
                    <?php echo esc_html($postType->label); ?>
                </label>
                <?php self::renderTaxonomyFields($postType); ?>
            </div>
            <?php
        endforeach;
    }

    /**
     * Get the taxonomy fields, as a hierarchical list of nested checkboxes.
     *
     * @since 3.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function renderTaxonomyFields($postType)
    {
        $taxonomies = get_object_taxonomies($postType->name, 'objects');

        if ($taxonomies) {
            ?>
            <div class="beyondwords-setting__preselect--taxonomy" style="margin: 0.5rem 0;">
                <?php
                foreach ($taxonomies as $taxonomy) {
                    // Ignore the "Format" taxonomy (aside, etc)
                    if ($taxonomy->name === 'post_format') {
                        continue;
                    }
                    // todo enable for custom taxonomies, and add tests for them
                    if ($taxonomy->name !== 'category') {
                        continue;
                    }
                    ?>
                    <h4 style="margin: 0.5rem 0 0.5rem 1.5rem;"><?php echo esc_html($taxonomy->label); ?></h4>
                    <?php
                    self::renderTaxonomyTerms($postType, $taxonomy);
                }
                ?>
            </div>
            <?php
        }
    }

    /**
     * Get the taxonomy terms, as a hierarchical list of nested checkboxes.
     *
     * @since 3.0.0
     * @since 6.0.0 Make static.
     *
     * @return void
     **/
    public static function renderTaxonomyTerms($postType, $taxonomy, $parent = 0)
    {
        $terms = get_terms([
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => false,
            'parent'     => $parent,
        ]);

        if ($terms) {
            ?>
            <ul style="margin: 0; padding: 0; list-style:none;">
                <?php
                foreach ($terms as $term) :
                    $inputName = sprintf(
                        "%s[%s][%s][]",
                        self::OPTION_NAME,
                        $postType->name,
                        $taxonomy->name
                    );
                    ?>
                    <li class="beyondwords-setting__preselect--term" style="margin: 0.5rem 0 0 1.5rem;">
                        <label>
                            <input
                                type="checkbox"
                                name="<?php echo esc_attr($inputName); ?>"
                                value="<?php echo esc_attr($term->term_id); ?>"
                                <?php checked(self::termIsSelected($postType, $taxonomy, $term)) ?>
                            />
                            <?php echo esc_html($term->name); ?>
                        </label>
                        <?php self::renderTaxonomyTerms($postType, $taxonomy, $term->term_id); ?>
                    </li>
                    <?php
                endforeach;
                ?>
                </ul>
            <?php
        }
    }

    /**
     * Check whether a post type should be preselected.
     *
     * @since 6.0.0 Make static.
     */
    public static function postTypeIsSelected($postType)
    {
        $preselect = get_option(self::OPTION_NAME);

        if (! is_array($preselect)) {
            return false;
        }

        return array_key_exists($postType->name, $preselect) && $preselect[$postType->name] === '1';
    }

    /**
     * Check whether a taxonomy should be preselected for a post type.
     *
     * @since 6.0.0 Make static.
     */
    public static function taxonomyIsSelected($postType, $taxonomy)
    {
        $preselect = get_option(self::OPTION_NAME);

        if (! is_array($preselect)) {
            return false;
        }

        if (! isset($preselect[$postType->name]) || ! is_array($preselect[$postType->name])) {
            return false;
        }

        return in_array($taxonomy->name, $preselect[$postType->name]);
    }

    /**
     * Check whether a term is selected for a post type and taxonomy.
     *
     * @since 6.0.0 Make static.
     */
    public static function termIsSelected($postType, $taxonomy, $term)
    {
        $preselect = get_option(self::OPTION_NAME);

        if (! is_array($preselect)) {
            return false;
        }

        if (! isset($preselect[$postType->name]) || ! is_array($preselect[$postType->name])) {
            return false;
        }

        if (! isset($preselect[$postType->name][$taxonomy->name]) || ! is_array($preselect[$postType->name][$taxonomy->name])) { // phpcs:ignore Generic.Files.LineLength.TooLong
            return false;
        }

        return in_array($term->term_id, $preselect[$postType->name][$taxonomy->name]);
    }

    /**
     * Register the component scripts.
     *
     * @since 5.0.0
     * @since 6.0.0 Make static.
     *
     * @param string $hook Page hook
     *
     * @return void
     */
    public static function enqueueScripts($hook)
    {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_register_script(
                'beyondwords-settings--preselect-post',
                BEYONDWORDS__PLUGIN_URI . 'src/Component/Settings/Fields/PreselectGenerateAudio/post.js',
                ['jquery', 'underscore'],
                BEYONDWORDS__PLUGIN_VERSION,
                true
            );

            // Localize the script with new data
            $data = [
                'postType'  => get_post_type(),
                'preselect' => get_option(self::OPTION_NAME),
            ];

            wp_localize_script('beyondwords-settings--preselect-post', 'beyondwords', $data);

            wp_enqueue_script('beyondwords-settings--preselect-post');
        }
    }
}
