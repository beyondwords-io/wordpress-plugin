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
}
