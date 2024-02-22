<?php

declare(strict_types=1);

/**
 * BeyondWords Posts Column.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Posts\Column;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;
use Beyondwords\Wordpress\Component\Settings\SettingsUtils;

/**
 * Column setup
 *
 * @since 3.0.0
 */
class Column
{
    public const OUTPUT_YES = '<span class="dashicons dashicons-yes"></span> ';

    public const OUTPUT_NO = '—';

    public const OUTPUT_DISABLED = ' <span class="beyondwords--disabled">Disabled</span>';

    public const OUTPUT_ERROR_PREFIX = '<span class="dashicons dashicons-warning"></span> ';

    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getSupportedPostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_filter("manage_{$postType}_posts_columns", array($this, 'renderColumnsHead'));
                    add_action("manage_{$postType}_posts_custom_column", array($this, 'renderColumnsContent'), 10, 2);
                }
            }
        });
    }

    /**
     * Add a custom column with player status.
     *
     * @since 3.0.0
     *
     * @param array $columns Array of <td> headers
     *
     * @return array
     **/
    public function renderColumnsHead($columns)
    {
        return array_merge($columns, array(
            'beyondwords' => __('BeyondWords', 'speechkit'),
        ));
    }

    /**
     * Render ✗|✓ in Posts list, under the BeyondWords column.
     *
     * @since 3.0.0
     *
     * @param string $columnName Column name
     * @param int    $postId     Post ID
     *
     * @return void
     **/
    public function renderColumnsContent($columnName, $postId)
    {
        if ($columnName !== 'beyondwords') {
            return;
        }

        $postTypes = SettingsUtils::getSupportedPostTypes();

        if (empty($postTypes)) {
            return;
        }

        $errorMessage = PostMetaUtils::getErrorMessage($postId);
        $contentId    = PostMetaUtils::getContentId($postId);
        $disabled     = PostMetaUtils::getDisabled($postId);

        $allowedTags = array(
            'span' => array(
                'class'   => array(),
            ),
        );

        if (! empty($errorMessage)) {
            echo wp_kses(self::OUTPUT_ERROR_PREFIX . $errorMessage, $allowedTags);
        } elseif (empty($contentId)) {
            echo wp_kses(self::OUTPUT_NO, $allowedTags);
        } else {
            echo wp_kses(self::OUTPUT_YES, $allowedTags);
        }

        if (! empty($disabled)) {
            echo wp_kses(self::OUTPUT_DISABLED, $allowedTags);
        }
    }
}
