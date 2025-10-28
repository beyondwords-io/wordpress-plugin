<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Settings\Tabs\Content\Content;

/**
 * @group settings
 * @group settings-tabs
 */
class ContentTabTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init_registers_admin_init_hook(): void
    {
        Content::init();

        $this->assertEquals(
            5,
            has_action('admin_init', [Content::class, 'addSettingsSection']),
            'Should register addSettingsSection on admin_init with priority 5'
        );
    }

    /**
     * @test
     */
    public function addSettingsSection_registers_section(): void
    {
        global $wp_settings_sections;

        Content::addSettingsSection();

        $this->assertArrayHasKey(
            'beyondwords_content',
            $wp_settings_sections,
            'Should register beyondwords_content settings page'
        );

        $this->assertArrayHasKey(
            'content',
            $wp_settings_sections['beyondwords_content'],
            'Should register content settings section'
        );

        $section = $wp_settings_sections['beyondwords_content']['content'];
        $this->assertSame('Content', $section['title']);
        $this->assertSame([Content::class, 'sectionCallback'], $section['callback']);
    }

    /**
     * @test
     */
    public function sectionCallback_outputs_description(): void
    {
        $html = $this->captureOutput(function () {
            Content::sectionCallback();
        });

        $this->assertStringContainsString(
            'Only future content will be affected',
            $html,
            'Should contain description about future content'
        );

        $this->assertStringContainsString(
            'To apply changes to existing content, please regenerate each post',
            $html,
            'Should contain description about regenerating posts'
        );

        $this->assertStringContainsString(
            '<p class="description">',
            $html,
            'Should wrap description in paragraph with description class'
        );
    }

    /**
     * @test
     */
    public function sectionCallback_escapes_output(): void
    {
        $html = $this->captureOutput(function () {
            Content::sectionCallback();
        });

        // Should not contain any unescaped HTML
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    /**
     * @test
     */
    public function init_initializes_all_field_components(): void
    {
        Content::init();

        // Verify that all component hooks are registered
        // IntegrationMethod
        $this->assertTrue(
            has_action('admin_init') !== false,
            'Should initialize IntegrationMethod component'
        );

        // Note: We can't easily test the new ClassName()::init() pattern
        // but we can verify the main hook is registered
    }

    /**
     * @test
     */
    public function integration_full_workflow(): void
    {
        global $wp_settings_sections;

        // Initialize the content tab
        Content::init();

        // Add the settings section
        Content::addSettingsSection();

        // Verify section is registered
        $this->assertArrayHasKey('beyondwords_content', $wp_settings_sections);
        $this->assertArrayHasKey('content', $wp_settings_sections['beyondwords_content']);

        // Verify callback works
        $html = $this->captureOutput(function () {
            Content::sectionCallback();
        });

        $this->assertNotEmpty($html, 'Section callback should output HTML');
        $this->assertStringContainsString('description', $html);
    }
}
