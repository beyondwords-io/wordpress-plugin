<?php

declare(strict_types=1);

/**
 * BeyondWords support for Gutenberg blocks.
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.7.0
 * @since   4.0.0 Renamed from BlockAudioAttribute.php to BlockAttributes.php to support multiple attributes
 */

namespace Beyondwords\Wordpress\Component\Post\BlockAttributes;

use Beyondwords\Wordpress\Component\Post\PostContentUtils;
use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Symfony\Component\Uid\Uuid;

/**
 * BlockAttributes
 *
 * @since 3.7.0
 * @since 4.0.0 Renamed from BlockAudioAttribute to BlockAttributes to support multiple attributes
 */
class BlockAttributes
{
    /**
     * Init.
     *
     * @since 4.0.0
     * @since 6.0.0 Make static.
     * @since 6.0.1 Add REST API hooks for marker initialization.
     */
    public static function init()
    {
        add_filter('register_block_type_args', [self::class, 'registerAudioAttribute']);
        add_filter('register_block_type_args', [self::class, 'registerMarkerAttribute']);
        add_filter('render_block', [self::class, 'renderBlock'], 10, 2);

        // Register hooks for marker initialization
        add_filter('wp_insert_post_data', [self::class, 'initializeBlockMarkersBeforeSave'], 10, 2);
    }

    /**
     * Initialize block markers before post data is saved to database.
     *
     * This function runs right before post data is inserted/updated in the database
     * and ensures all blocks have unique markers.
     *
     * @since 6.0.1
     *
     * @param array $data    An array of slashed, sanitized, and processed post data.
     * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
     *
     * @return array Modified post data.
     */
    public static function initializeBlockMarkersBeforeSave($data, $postarr)
    {
        // Skip if no content
        if (empty($data['post_content'])) {
            return $data;
        }

        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $data;
        }

        if (wp_is_post_revision($postarr['ID'] ?? 0)) {
            return $data;
        }

        // Skip if post type doesn't support custom fields
        if (! empty($data['post_type']) && ! post_type_supports($data['post_type'], 'custom-fields')) {
            return $data;
        }

        // Parse blocks
        $blocks = parse_blocks($data['post_content']);

        if (empty($blocks)) {
            return $data;
        }

        // Track existing markers to detect duplicates
        $existingMarkers = [];
        $needsUpdate = false;

        // Process blocks recursively
        $updatedBlocks = self::processBlocksForMarkers($blocks, $existingMarkers, $needsUpdate);

        // Only update content if changes were made
        if ($needsUpdate) {
            // Serialize blocks back to content
            $data['post_content'] = serialize_blocks($updatedBlocks);

            // Debug logging
            error_log(sprintf(
                'BeyondWords: Generated markers for post %d, found %d existing markers',
                $postarr['ID'] ?? 0,
                count($existingMarkers)
            ));
        }

        return $data;
    }

    /**
     * Register "Audio" attribute for Gutenberg blocks.
     *
     * @since 6.0.0 Make static.
     */
    public static function registerAudioAttribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = [];
        }

        if (! array_key_exists('beyondwordsAudio', $args['attributes'])) {
            $args['attributes']['beyondwordsAudio'] = [
                'type' => 'boolean',
                'default' => true,
            ];
        }

        return $args;
    }

    /**
     * Register "Segment marker" attribute for Gutenberg blocks.
     *
     * @since 6.0.0 Make static.
     */
    public static function registerMarkerAttribute($args)
    {
        // Setup attributes if needed.
        if (! isset($args['attributes'])) {
            $args['attributes'] = [];
        }

        if (! array_key_exists('beyondwordsMarker', $args['attributes'])) {
            $args['attributes']['beyondwordsMarker'] = [
                'type' => 'string',
            ];
        }

        return $args;
    }

    /**
     * Render block as HTML.
     *
     * Performs some checks and then attempts to add data-beyondwords-marker
     * attribute to the root element of Gutenberg block.
     *
     * @since 4.0.0
     * @since 4.2.2 Rename method to renderBlock.
     * @since 6.0.0 Make static and update for Magic Embed.
     *
     * @param string $blockContent The block content (HTML).
     * @param string $block        The full block, including name and attributes.
     *
     * @return string Block Content (HTML).
     */
    public static function renderBlock($blockContent, $block)
    {
        // Skip adding marker if player UI is disabled
        if (get_option(PlayerUI::OPTION_NAME) === PlayerUI::DISABLED) {
            return $blockContent;
        }

        $postId = get_the_ID();

        if (! $postId) {
            return $blockContent;
        }

        $marker = $block['attrs']['beyondwordsMarker'] ?? '';

        return PostContentUtils::addMarkerAttribute(
            $blockContent,
            $marker
        );
    }

    /**
     * Recursively process blocks to initialize and deduplicate markers.
     *
     * @since 6.0.1
     *
     * @param array $blocks           Array of block arrays.
     * @param array $existingMarkers  Reference to array tracking existing markers.
     * @param bool  $needsUpdate      Reference to flag indicating if update is needed.
     *
     * @return array Updated blocks.
     */
    private static function processBlocksForMarkers($blocks, &$existingMarkers, &$needsUpdate)
    {
        $updatedBlocks = [];

        foreach ($blocks as $block) {
            // Skip blocks that shouldn't have markers
            if (! self::shouldHaveBeyondWordsMarker($block['blockName'])) {
                $updatedBlocks[] = $block;
                continue;
            }

            // Check if block has beyondwordsAudio attribute
            $hasAudio = $block['attrs']['beyondwordsAudio'] ?? true;

            // Only process blocks with audio enabled
            if ($hasAudio) {
                $currentMarker = $block['attrs']['beyondwordsMarker'] ?? '';

                // Check if marker is missing or duplicate
                if (empty($currentMarker) || in_array($currentMarker, $existingMarkers, true)) {
                    // Generate new unique marker
                    $newMarker = self::generateUniqueUuid($existingMarkers);

                    error_log(sprintf(
                        'BeyondWords: Generating marker for block %s (had: %s, generated: %s)',
                        $block['blockName'] ?? 'unknown',
                        $currentMarker ?: 'none',
                        $newMarker
                    ));

                    $block['attrs']['beyondwordsMarker'] = $newMarker;
                    $existingMarkers[] = $newMarker;
                    $needsUpdate = true;
                } else {
                    // Track existing marker
                    $existingMarkers[] = $currentMarker;
                }
            }

            // Process inner blocks recursively
            if (! empty($block['innerBlocks'])) {
                $block['innerBlocks'] = self::processBlocksForMarkers(
                    $block['innerBlocks'],
                    $existingMarkers,
                    $needsUpdate
                );
            }

            $updatedBlocks[] = $block;
        }

        return $updatedBlocks;
    }

    /**
     * Check if a block should have BeyondWords marker.
     *
     * @since 6.0.1
     *
     * @param string $blockName Block name.
     *
     * @return bool Whether the block should have a marker.
     */
    private static function shouldHaveBeyondWordsMarker($blockName)
    {
        // Skip blocks without a name
        if (empty($blockName)) {
            return false;
        }

        // Skip internal/UI blocks
        if (strpos($blockName, '__') === 0) {
            return false;
        }

        // Skip reusable blocks and template parts (these are containers)
        if (
            strpos($blockName, 'core/block') === 0 ||
            strpos($blockName, 'core/template') === 0
        ) {
            return false;
        }

        // Skip editor UI blocks
        $excludedBlocks = [
            'core/freeform', // Classic editor
            'core/legacy-widget',
            'core/widget-area',
            'core/navigation',
            'core/navigation-link',
            'core/navigation-submenu',
            'core/site-logo',
            'core/site-title',
            'core/site-tagline',
        ];

        if (in_array($blockName, $excludedBlocks, true)) {
            return false;
        }

        return true;
    }

    /**
     * Generate a unique UUID v4 that doesn't exist in the given array.
     *
     * @since 6.0.1
     *
     * @param array $existingMarkers Array of existing markers to check against.
     *
     * @return string UUID v4.
     */
    private static function generateUniqueUuid($existingMarkers)
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $uuid = Uuid::v4()->toRfc4122();
            $attempts++;

            // Ensure uniqueness
            if (! in_array($uuid, $existingMarkers, true)) {
                return $uuid;
            }
        } while ($attempts < $maxAttempts);

        // Fallback: append timestamp if somehow we can't generate unique UUID
        // This should never happen with proper UUIDs but provides safety
        return Uuid::v4()->toRfc4122() . '-' . time();
    }
}
