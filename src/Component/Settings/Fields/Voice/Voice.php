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

/**
 * Voice
 *
 * @since 5.0.0
 */
abstract class Voice
{
    /**
     * Language code.
     *
     * @since 5.0.0
     */
    public $languageId;

    /**
     * Language code.
     *
     * @since 5.0.0
     */
    public $languageCode;

    /**
     * API Client.
     *
     * @since 3.0.0
     */
    private $apiClient;

    /**
     * Constructor.
     *
     * @since 3.0.0
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;

        $this->languageId = get_option('beyondwords_project_language_id');
        $this->languageCode = get_option('beyondwords_project_language_code');
    }

    /**
     * Get all options for the current component.
     *
     * @since 5.0.0
     *
     * @return string[] Associative array of options.
     **/
    public function getOptions()
    {
        $voices = $this->apiClient->getVoices($this->languageId);

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
