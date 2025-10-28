<?php

declare(strict_types=1);

/**
 * Setting: Voice
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   5.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\Voice;

use Beyondwords\Wordpress\Core\ApiClient;

/**
 * Voice
 *
 * @since 5.0.0
 */
abstract class Voice
{
    /**
     * Get all options for the current component.
     *
     * @since 5.0.0
     * @since 5.4.0
     * @since 6.0.0 Make static, handle API errors, and return language_code in each option.
     *
     * @return string[] Associative array of options.
     **/
    public static function getOptions()
    {
        $voices = null;
        $languageCode = get_option('beyondwords_project_language_code');
        if ($languageCode) {
            $voices = ApiClient::getVoices($languageCode);
        }

        if (! $voices || ! is_array($voices) || empty($voices)) {
            return [];
        }

        // Filter out any non-array elements (in case of API errors)
        $voices = array_filter($voices, 'is_array');

        if (empty($voices)) {
            return [];
        }

        return array_map(fn($voice) => [
            'value' => $voice['id'],
            'label' => $voice['name'],
            'language_code' => $voice['language']['code'] ?? '',
        ], $voices);
    }
}
