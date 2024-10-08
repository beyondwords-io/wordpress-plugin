<?php
// phpcs:disable Generic.Files.LineLength.TooLong

declare(strict_types=1);

namespace Beyondwords\Wordpress\Component\Settings;

/**
 * BeyondWords Settings Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
class SettingsUtils
{
    /**
     * Get the post types BeyondWords will consider for compatibility.
     *
     * We don't consider many of the core built-in post types for compatibity
     * because they don't support the features we need such as titles, body,
     * custom fields, etc.
     *
     * @since 3.7.0
     * @since 4.5.0 Renamed from getAllowedPostTypes to getConsideredPostTypes.
     * @since 4.6.2 Exclude wp_font_face and wp_font_family from considered post types.
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getConsideredPostTypes()
    {
        $postTypes = get_post_types();

        $skip = [
            'attachment',
            'custom_css',
            'customize_changeset',
            'nav_menu_item',
            'oembed_cache',
            'revision',
            'user_request',
            'wp_block',
            'wp_font_face',
            'wp_font_family',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
        ];

        // Remove the skipped post types
        $postTypes = array_diff($postTypes, $skip);

        return array_values($postTypes);
    }

    /**
     * Get the post types that are compatible with BeyondWords.
     *
     * - Start with the considered post types
     * - Allow publishers to filter the list
     * - Filter again, removing any that are incompatible
     *
     * @since 3.0.0
     * @since 3.2.0 Removed $output parameter to always output names, never objects.
     * @since 3.2.0 Added `beyondwords_post_types` filter.
     * @since 3.5.0 Moved from Core\Utils to Component\Settings\SettingsUtils.
     * @since 3.7.0 Refactored forbidden/allowed/supported post type methods to improve site health debugging info.
     * @since 4.5.0 Renamed from getSupportedPostTypes to getCompatiblePostTypes.
     * @since 5.0.0 Remove beyondwords_post_types filter.
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getCompatiblePostTypes()
    {
        $postTypes = SettingsUtils::getConsideredPostTypes();

        /**
         * Filters the post types supported by BeyondWords.
         *
         * This defaults to all registered post types with 'custom-fields' in their 'supports' array.
         *
         * If any of the supplied post types do not support custom fields then they will be removed
         * from the array.
         *
         * @since 3.3.3 Introduced as beyondwords_post_types
         * @since 4.3.0 Renamed from beyondwords_post_types to beyondwords_settings_post_types.
         *
         * @param string[] The post types supported by BeyondWords.
         */
        $postTypes = apply_filters('beyondwords_settings_post_types', $postTypes);

        // Remove incompatible post types
        $postTypes = array_diff($postTypes, SettingsUtils::getIncompatiblePostTypes());

        return array_values($postTypes);
    }

    /**
     * Get the post types that are incompatible with BeyondWords.
     *
     * The requirements are:
     * - Must support Custom Fields.
     *
     * @since 4.5.0
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getIncompatiblePostTypes()
    {
        $postTypes = SettingsUtils::getConsideredPostTypes();

        // Filter the array, removing unsupported post types
        $postTypes = array_filter($postTypes, function ($postType) {
            if (post_type_supports($postType, 'custom-fields')) {
                return false;
            }

            return true;
        });

        return array_values($postTypes);
    }

    /**
     * Do we have a valid API connection to call the BeyondWords REST API?
     *
     * @since  3.0.0
     * @since  4.0.0 Moved from Settings to SettingsUtils
     * @static
     *
     * @return boolean
     */
    public static function hasApiSettings()
    {
        return boolval(get_option('beyondwords_valid_api_connection'));
    }

    /**
     * A color input.
     *
     * @since  5.0.0
     * @static
     *
     * @param string $label Content for the `<label>`
     * @param string $name  `name` attribute for the `<input />`
     * @param string $value `value` attribute for the `<input />`
     *
     * @return string
     */
    public static function colorInput($label, $name, $value)
    {
        ?>
        <div class="color-input">
            <label>
                    <?php echo esc_attr($label); ?>
            </label>
            <output
                for="<?php echo esc_attr($name); ?>"
                style="background: <?php echo esc_attr($value); ?>; margin-right: 0.25rem;"
            ></output>
            <input
                name="<?php echo esc_attr($name); ?>"
                type="text"
                value="<?php echo esc_attr($value); ?>"
                class="small-text"
                oninput="this.previousElementSibling.style.background = 'transparent'; this.previousElementSibling.style.background = `${this.value}`"
            />
        </div>
        <?php
    }
}
