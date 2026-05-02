<?php

declare(strict_types=1);

use BeyondWords\Settings\Utils;

class SettingsUtilsTest extends TestCase
{
    public function setUp(): void
    {
        // Before...
        parent::setUp();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getCompatiblePostTypesFilter()
    {
        $postTypes = array_values(get_post_types());

        $this->assertContains('post', $postTypes);
        $this->assertContains('page', $postTypes);
        $this->assertContains('attachment', $postTypes);
        $this->assertContains('revision', $postTypes);

        // Set the filter
        $filter = function($supportedPostTypes) {
            return [
                $supportedPostTypes[1],
                $supportedPostTypes[0],
                'another-post-type',
            ];
        };

        add_filter('beyondwords_settings_post_types', $filter);

        $postTypes = Utils::get_compatible_post_types();

        remove_filter('beyondwords_settings_post_types', $filter);

        $this->assertSame(['page', 'post', 'another-post-type'], $postTypes);
    }
}
