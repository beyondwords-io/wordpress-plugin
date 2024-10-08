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
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * Column
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
     * @since 4.5.0 Make BeyondWords column sortable via the pre_get_posts query.
     */
    public function init()
    {
        add_action('wp_loaded', function () {
            $postTypes = SettingsUtils::getCompatiblePostTypes();

            if (is_array($postTypes)) {
                foreach ($postTypes as $postType) {
                    add_filter("manage_{$postType}_posts_columns", array($this, 'renderColumnsHead'));
                    add_action("manage_{$postType}_posts_custom_column", array($this, 'renderColumnsContent'), 10, 2);
                    add_filter("manage_edit-{$postType}_sortable_columns", array($this, 'makeColumnSortable'));
                }
            }
        });

        if (CoreUtils::isEditScreen()) {
            add_action('pre_get_posts', array($this, 'setSortQuery'));
        }
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

        $postTypes = SettingsUtils::getCompatiblePostTypes();

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

    /**
     * Make the BeyondWords column sortable.
     *
     * @since 4.5.1
     *
     * @param array $sortableColumns An array of sortable columns.
     *
     * @return array The adjusted array of sortable columns.
     **/
    public function makeColumnSortable($sortableColumns)
    {
        // Make column 'beyondwords' sortable
        $sortableColumns['beyondwords'] = 'beyondwords';

        return $sortableColumns;
    }

    /**
     * Set the query to sort by BeyondWords fields.
     *
     * @since 4.5.1
     *
     * @param WP_Query $query WordPress query.
     *
     * @return $query WP_Query
     */
    public function setSortQuery($query)
    {
        $orderBy = $query->get('orderby');

        if ($orderBy === 'beyondwords' && $query->is_main_query()) {
            $query->set('meta_query', $this->getSortQueryArgs());
            $query->set('orderby', 'meta_value_num date');
        }

        return $query;
    }

    /**
     * Get the sort search query args.
     *
     * @since 4.5.1
     *
     * @param array $sortableColumns An array of sortable columns.
     *
     * @return array
     */
    public function getSortQueryArgs()
    {
        return [
            'relation' => 'OR',
            [
                'key' => 'beyondwords_generate_audio',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => 'beyondwords_generate_audio',
                'compare' => 'EXISTS',
            ],
        ];
    }
}
