<?php

declare(strict_types=1);

/**
 * Setting: Project ID
 *
 * @package Beyondwords\Wordpress
 * @author  Stuart McAlpine <stu@beyondwords.io>
 * @since   3.0.0
 */

namespace Beyondwords\Wordpress\Component\Settings\Fields\ProjectId;

/**
 * ProjectId setup
 *
 * @since 3.0.0
 */
class ProjectId
{
    /**
     * Init.
     *
     * @since 4.0.0
     */
    public function init()
    {
        add_action('admin_init', array($this, 'addSetting'));
        add_action('update_option_beyondwords_project_id', function () {
            add_filter('beyondwords_sync_to_wordpress', '__return_true');
        });
    }

    /**
     * Init setting.
     *
     * @since  3.0.0
     *
     * @return void
     */
    public function addSetting()
    {
        register_setting(
            'beyondwords_credentials_settings',
            'beyondwords_project_id',
            [
                'default'           => '',
                'sanitize_callback' => array($this, 'sanitize'),
            ]
        );

        add_settings_field(
            'beyondwords-project-id',
            __('Project ID', 'speechkit'),
            array($this, 'render'),
            'beyondwords_credentials',
            'credentials'
        );
    }

    /**
     * Render setting field.
     *
     * @since 3.0.0
     *
     * @return void
     **/
    public function render()
    {
        $project_id = get_option('beyondwords_project_id');
        ?>
        <input
            type="text"
            name="beyondwords_project_id"
            value="<?php echo esc_attr($project_id); ?>"
            size="10"
        />
        <?php
    }

    /**
     * Sanitise the setting value.
     *
     * @since  3.0.0
     * @param  array $value The submitted value.
     *
     * @return void
     **/
    public function sanitize($value)
    {
        $errors = get_transient('beyondwords_settings_errors', []);

        if (empty($value)) {
            $errors['Settings/ProjectId'] = __(
                'Please enter your BeyondWords project ID. This can be found in your project settings.',
                'speechkit'
            );
            set_transient('beyondwords_settings_errors', $errors);
        }

        return $value;
    }
}
