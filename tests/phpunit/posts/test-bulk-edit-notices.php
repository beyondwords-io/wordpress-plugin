<?php

use BeyondWords\Posts\BulkEditNotices;
use \Symfony\Component\DomCrawler\Crawler;

final class BulkEditNoticesTest extends TestCase
{
    /**
     * @var \BeyondWords\Posts\BulkEditNotices
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        unset($_POST, $_GET);
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        unset($_POST, $_GET);

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        BulkEditNotices::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_notices', array(BulkEditNotices::class, 'generated_notice')));
        $this->assertEquals(10, has_action('admin_notices', array(BulkEditNotices::class, 'deleted_notice')));
        $this->assertEquals(10, has_action('admin_notices', array(BulkEditNotices::class, 'failed_notice')));
        $this->assertEquals(10, has_action('admin_notices', array(BulkEditNotices::class, 'error_notice')));
    }

    /**
     * @test
     */
    public function generated_notice_without_nonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_generated'] = '1';

        $html = $this->capture_output(function () {
            BulkEditNotices::generated_notice();
        });

        $this->assertEmpty($html);
    }

    /**
     * @test
     */
    public function generated_notice_without_generated_count()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');

        $html = $this->capture_output(function () {
            BulkEditNotices::generated_notice();
        });

        $this->assertEmpty($html);
    }

    /**
     * @test
     */
    public function deleted_notice_with_invalid_nonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_deleted'] = '42';

        $this->expectException(\WPDieException::class);

        BulkEditNotices::deleted_notice();
    }

    /**
     * @test
     */
    public function generated_notice_with_invalid_nonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_generated'] = '42';

        $this->expectException(\WPDieException::class);

        BulkEditNotices::generated_notice();
    }

    /**
     * @test
     */
    public function failed_notice_with_invalid_nonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_failed'] = '42';

        $this->expectException(\WPDieException::class);

        BulkEditNotices::failed_notice();
    }

    /**
     * @test
     */
    public function error_notice_with_invalid_nonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_error'] = 'Error message';

        $this->expectException(\WPDieException::class);

        BulkEditNotices::error_notice();
    }

    /**
     * @test
     *
     * @dataProvider generated_notice_provider
     */
    public function generated_notice($numGenerated, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_generated'] = $numGenerated;

        $html = $this->capture_output(function () {
            BulkEditNotices::generated_notice();
        });

        $crawler = new Crawler($html);

        $notice = $crawler->filter('div');

        $this->assertEquals('beyondwords-bulk-edit-notice-generated', $notice->getNode(0)->getAttribute('id'));
        $this->assertEquals('notice notice-info is-dismissible', $notice->getNode(0)->getAttribute('class'));

        $this->assertStringContainsString($expectMessage, $notice->filter('p:first-of-type')->text());
    }

    /**
     *
     */
    public function generated_notice_provider() {
        return [
            '1 post' => [
                'numGenerated' => 1,
                'expectMessage' => 'Audio was requested for 1 post.',
            ],
            '42 posts' => [
                'numGenerated' => 42,
                'expectMessage' => 'Audio was requested for 42 posts.',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider deleted_notice_provider
     */
    public function deleted_notice($numDeleted, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_deleted'] = $numDeleted;

        $html = $this->capture_output(function () {
            BulkEditNotices::deleted_notice();
        });

        $crawler = new Crawler($html);

        $notice = $crawler->filter('div');

        $this->assertEquals('beyondwords-bulk-edit-notice-deleted', $notice->getNode(0)->getAttribute('id'));
        $this->assertEquals('notice notice-info is-dismissible', $notice->getNode(0)->getAttribute('class'));

        $this->assertStringContainsString($expectMessage, $notice->filter('p:first-of-type')->text());
    }

    /**
     *
     */
    public function deleted_notice_provider() {
        return [
            '1 post' => [
                'numDeleted' => 1,
                'expectMessage' => 'Audio was deleted for 1 post.',
            ],
            '42 posts' => [
                'numDeleted' => 42,
                'expectMessage' => 'Audio was deleted for 42 posts.',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider failed_notice_provider
     */
    public function failed_notice($numFailed, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_failed'] = $numFailed;

        $html = $this->capture_output(function () {
            BulkEditNotices::failed_notice();
        });

        $crawler = new Crawler($html);

        $notice = $crawler->filter('div');

        $this->assertEquals('beyondwords-bulk-edit-notice-failed', $notice->getNode(0)->getAttribute('id'));
        $this->assertEquals('notice notice-error is-dismissible', $notice->getNode(0)->getAttribute('class'));

        $this->assertStringContainsString($expectMessage, $notice->filter('p:first-of-type')->text());
    }

    /**
     *
     */
    public function failed_notice_provider() {
        return [
            '1 post' => [
                'numFailed' => 1,
                'expectMessage' => '1 post failed, check for errors in the BeyondWords column below.',
            ],
            '42 posts' => [
                'numFailed' => 42,
                'expectMessage' => '42 posts failed, check for errors in the BeyondWords column below.',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider error_notice_provider
     */
    public function error_notice($errorMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_error'] = $errorMessage;

        $html = $this->capture_output(function () {
            BulkEditNotices::error_notice();
        });

        $crawler = new Crawler($html);

        $notice = $crawler->filter('div');

        $this->assertEquals('beyondwords-bulk-edit-notice-error', $notice->getNode(0)->getAttribute('id'));
        $this->assertEquals('notice notice-error is-dismissible', $notice->getNode(0)->getAttribute('class'));

        $this->assertStringContainsString($errorMessage, $notice->filter('p:first-of-type')->text());

        unset($_GET['beyondwords_bulk_edit_result_nonce']);
        unset($_GET['beyondwords_bulk_error']);
    }

    /**
     *
     */
    public function error_notice_provider() {
        return [
            'Unknown error' => [
                'errorMessage' => 'Unknown error.',
            ],
            'Another error message string' => [
                'errorMessage' => 'Another error message string.',
            ],
        ];
    }
}
