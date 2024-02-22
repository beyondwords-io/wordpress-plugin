<?php

use Beyondwords\Wordpress\Component\Posts\Column\Column;

class ColumnTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Posts\Column\Column
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new Column();
    }

    public function tearDown(): void
    {
        // Your tear down methods here.
        $this->_instance = null;

        // Then...
        parent::tearDown();
    }

    /**
     * @test
     */
    public function init()
    {
        $column = new Column();
        $column->init();

        do_action('wp_loaded');

        // Post type: post
        $this->assertEquals(10, has_filter('manage_post_posts_columns', array($column, 'renderColumnsHead')));
        $this->assertEquals(10, has_filter('manage_post_posts_custom_column', array($column, 'renderColumnsContent')));

        // Post type: page
        $this->assertEquals(10, has_filter('manage_page_posts_columns', array($column, 'renderColumnsHead')));
        $this->assertEquals(10, has_filter('manage_page_posts_custom_column', array($column, 'renderColumnsContent')));

        // todo test custom post types
    }

    public function testRenderColumnsHead()
    {
        $defaults = ['foo' => 'Bar'];

        $columns = $this->_instance->renderColumnsHead($defaults);

        $this->assertSame(['foo' => 'Bar', 'beyondwords' => 'BeyondWords'], $columns);
    }

    /**
     * @dataProvider renderColumnsContentProvider
     */
    public function testRenderColumnsContent(string $expect, array $postArgs)
    {
        $this->expectOutputString($expect);

        $post = self::factory()->post->create_and_get($postArgs);

        $this->_instance->renderColumnsContent('beyondwords', $post->ID);

        wp_delete_post($post->ID, true);
    }

    public function renderColumnsContentProvider()
    {
        return [
            'Post with BEYONDWORDS_TESTS_CONTENT_ID' => [
                'expect' => Column::OUTPUT_YES,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::1',
                    'post_type' => 'post',
                    'meta_input' => [
                        'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
            ],
            'Post with BEYONDWORDS_TESTS_CONTENT_ID, Disabled' => [
                'expect' => Column::OUTPUT_YES . Column::OUTPUT_DISABLED,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::3',
                    'post_type' => 'post',
                    'meta_input' => [
                        'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                        'beyondwords_disabled' => 1,
                    ],
                ],
            ],
            'Post with a different custom field' => [
                'expect' => Column::OUTPUT_NO,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::4',
                    'post_type' => 'post',
                    'meta_input' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            'Page with BEYONDWORDS_TESTS_CONTENT_ID' => [
                'expect' => Column::OUTPUT_YES,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::5',
                    'post_type' => 'page',
                    'meta_input' => [
                        'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
            ],
            'Page with BEYONDWORDS_TESTS_CONTENT_ID, Disabled' => [
                'expect' => Column::OUTPUT_YES . Column::OUTPUT_DISABLED,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::7',
                    'post_type' => 'page',
                    'meta_input' => [
                        'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                        'beyondwords_disabled' => 1,
                    ],
                ],
            ],
            'Page with a different custom field' => [
                'expect' => Column::OUTPUT_NO,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::8',
                    'post_type' => 'page',
                    'meta_input' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            'Custom with BEYONDWORDS_TESTS_CONTENT_ID' => [
                'expect' => Column::OUTPUT_YES,
                'postArgs' => [
                    'post_title' => 'ColumnTest::renderColumnsContentProvider::9',
                    'post_type' => 'my_custom_post_type',
                    'meta_input' => [
                        'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
                    ],
                ],
            ],
        ];
    }
}
