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
     *
     * @return string[] Associative array of options.
     **/
    public function getOptions()
    {
        $languageId = get_option('beyondwords_project_language_id');
        $voices     = ApiClient::getVoices($languageId);

        if (! $voices) {
            return [];
        }

        $options = array_map(function ($voice) {
            return [
                'value' => $voice['id'],
                'label' => $voice['name'],
            ];
        }, $voices);

        return $options;
    }
}
