<?php

declare(strict_types=1);

/**
 * Setting: SpeakingRate
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   4.8.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\SpeakingRate;

/**
 * SpeakingRate setup
 *
 * @since 4.8.0
 */
abstract class SpeakingRate
{
    /**
     * Constructor
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
    }

    /**
     * Get all options for the current component.
     *
     * @since 4.8.0
     *
     * @return string[] Associative array of options.
     **/
    public function getOptions()
    {
        $options = [];

        for ($i = 5; $i <= 200; $i += 5) {
            $options[] = [
                'value' => "$i",
                'label' => "$i%",
            ];
        }

        return $options;
    }
}
