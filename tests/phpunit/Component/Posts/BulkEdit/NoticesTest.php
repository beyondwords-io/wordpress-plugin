<?php

use Beyondwords\Wordpress\Component\Posts\BulkEdit\Notices;
use \Symfony\Component\DomCrawler\Crawler;

final class BulkEditNoticesTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Posts\BulkEdit\Notices
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
        Notices::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('admin_notices', array(Notices::class, 'generatedNotice')));
        $this->assertEquals(10, has_action('admin_notices', array(Notices::class, 'deletedNotice')));
        $this->assertEquals(10, has_action('admin_notices', array(Notices::class, 'failedNotice')));
        $this->assertEquals(10, has_action('admin_notices', array(Notices::class, 'errorNotice')));
    }

    /**
     * @test
     */
    public function generatedNoticeWithoutNonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_generated'] = '1';

        $html = $this->captureOutput(function () {
            Notices::generatedNotice();
        });

        $this->assertEmpty($html);
    }

    /**
     * @test
     */
    public function generatedNoticeWithoutGeneratedCount()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');

        $html = $this->captureOutput(function () {
            Notices::generatedNotice();
        });

        $this->assertEmpty($html);
    }

    /**
     * @test
     */
    public function deletedNoticeWithInvalidNonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_deleted'] = '42';

        $this->expectException(\WPDieException::class);

        Notices::deletedNotice();
    }

    /**
     * @test
     */
    public function generatedNoticeWithInvalidNonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_generated'] = '42';

        $this->expectException(\WPDieException::class);

        Notices::generatedNotice();
    }

    /**
     * @test
     */
    public function failedNoticeWithInvalidNonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_failed'] = '42';

        $this->expectException(\WPDieException::class);

        Notices::failedNotice();
    }

    /**
     * @test
     */
    public function errorNoticeWithInvalidNonce()
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = 'foo';
        $_GET['beyondwords_bulk_error'] = 'Error message';

        $this->expectException(\WPDieException::class);

        Notices::errorNotice();
    }

    /**
     * @test
     *
     * @dataProvider generatedNoticeProvider
     */
    public function generatedNotice($numGenerated, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_generated'] = $numGenerated;

        $html = $this->captureOutput(function () {
            Notices::generatedNotice();
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
    public function generatedNoticeProvider() {
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
     * @dataProvider deletedNoticeProvider
     */
    public function deletedNotice($numDeleted, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_deleted'] = $numDeleted;

        $html = $this->captureOutput(function () {
            Notices::deletedNotice();
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
    public function deletedNoticeProvider() {
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
     * @dataProvider failedNoticeProvider
     */
    public function failedNotice($numFailed, $expectMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_failed'] = $numFailed;

        $html = $this->captureOutput(function () {
            Notices::failedNotice();
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
    public function failedNoticeProvider() {
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
     * @dataProvider errorNoticeProvider
     */
    public function errorNotice($errorMessage)
    {
        set_current_screen('edit-post');

        $_GET['beyondwords_bulk_edit_result_nonce'] = wp_create_nonce('beyondwords_bulk_edit_result');
        $_GET['beyondwords_bulk_error'] = $errorMessage;

        $html = $this->captureOutput(function () {
            Notices::errorNotice();
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
    public function errorNoticeProvider() {
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
