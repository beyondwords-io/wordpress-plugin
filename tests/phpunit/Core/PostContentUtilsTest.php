<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Post\PostContentUtils;

class PostContentUtilsTest extends WP_UnitTestCase
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
    public function getContentBody()
    {
        $post = self::factory()->post->create_and_get([
            'post_title'   => 'PostContentUtilsTest:getContentBody',
            'post_content' => '<p>Some test HTML.</p>',
        ]);

        $content = PostContentUtils::getContentBody($post);

        $this->assertSame('<p>Some test HTML.</p>', $content);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function getContentBodyWithSummary()
    {
        $post = self::factory()->post->create_and_get([
            'post_title'   => 'PostContentUtilsTest:getContentBodyWithSummary',
            'post_excerpt' => 'The excerpt.',
            'post_content' => '<p>Some test HTML.</p>',
        ]);

        update_option('beyondwords_prepend_excerpt', '1');

        $content = PostContentUtils::getContentBody($post);

        delete_option('beyondwords_prepend_excerpt');

        $this->assertSame('<div data-beyondwords-summary="true"><p>The excerpt.</p></div><p>Some test HTML.</p>', $content);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function getContentBodyWithSummaryVoiceId()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'PostContentUtilsTest:getContentBodyWithSummaryVoiceId',
            'post_excerpt' => 'The excerpt.',
            'post_content' => '<p>Some test HTML.</p>',
            'meta_input'    => [
                'beyondwords_summary_voice_id' => '3555',
            ],
        ]);

        update_option('beyondwords_prepend_excerpt', '1');

        $content = PostContentUtils::getContentBody($post);

        delete_option('beyondwords_prepend_excerpt');

        $this->assertSame('<div data-beyondwords-summary="true" data-beyondwords-voice-id="3555"><p>The excerpt.</p></div><p>Some test HTML.</p>', $content);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function getContentBodyWithInvalidPostId()
    {
        $this->expectException(\Exception::class);

        PostContentUtils::getContentBody(-1);
    }

    /**
     * @test
     */
    public function getPostSummaryWithInvalidPostId()
    {
        $this->expectException(\Exception::class);

        PostContentUtils::getPostSummary(-1);
    }

    /**
     * @test
     */
    public function getPostSummaryWrapperFormatWithInvalidPostId()
    {
        $this->expectException(\Exception::class);

        PostContentUtils::getPostSummaryWrapperFormat(-1);
    }

    /**
     * @test
     * @dataProvider getContentWithoutExcludedBlocksProvider
     */
    public function getContentWithoutExcludedBlocks($content, $expect)
    {
        $post = self::factory()->post->create_and_get([
            'post_title'   => 'PostContentUtilsTest:getContentWithoutExcludedBlocks',
            'post_content' => $content,
        ]);

        $this->assertSame($expect, PostContentUtils::getContentWithoutExcludedBlocks($post));

        wp_delete_post($post->ID, true);
    }

    public function getContentWithoutExcludedBlocksProvider()
    {
        $withBlocks = '<!-- wp:paragraph --><p>No marker.</p><!-- /wp:paragraph -->' .
                      '<!-- wp:paragraph {"beyondwordsMarker":"marker-1"} --><p>Has marker.</p><!-- /wp:paragraph -->' .
                      '<!-- wp:paragraph {"beyondwordsAudio":false} --><p>No audio.</p><!-- /wp:paragraph -->' .
                      '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->' . // Empty paragraph
                      '<!-- wp:paragraph {"beyondwordsMarker":"marker-2"}  --><p></p><!-- /wp:paragraph -->' . // Empty paragraph
                      '<!-- wp:paragraph --><p>Previous two paragraphs were empty.</p><!-- /wp:paragraph -->';

        $withBlocksExpect = '<p>No marker.</p>' .
                            '<p data-beyondwords-marker="marker-1">Has marker.</p>' .
                            '<p></p>' .
                            '<p data-beyondwords-marker="marker-2"></p>' .
                            '<p>Previous two paragraphs were empty.</p>';

        $withoutBlocks = "<p>One</p>\n\n<p></p>\n\n<p data-beyondwords-marker=\"marker-3\">Three</p>\n\n";

        $withoutBlocksExpect = "<p>One</p>\n\n<p></p>\n\n<p data-beyondwords-marker=\"marker-3\">Three</p>";

        return [
            'Content with blocks'    => [ $withBlocks, $withBlocksExpect ],
            'Content without blocks' => [ $withoutBlocks, $withoutBlocksExpect ],
        ];
    }

    /**
     * @test
     */
    public function getPostBodyWithInvalidPostId()
    {
        $this->expectException(\Exception::class);

        PostContentUtils::getPostBody(-1);
    }

    /**
     * @test
     * @dataProvider getPostBodyProvider
     */
    public function getPostBody($postType, $postContent, $expected)
    {
        // A shortcode which tests both attribues and content
        add_shortcode('shortcode_test', function($atts, $content="") {
            return sprintf('<em>%s, %s, %s</em>', strtolower($atts['to_lower']), strtoupper($atts['to_upper']), $content);
        });

        $postId = $this->factory->post->create([
            'post_title' => 'PostContentUtilsTest:getPostBody',
            'post_type' => $postType,
            'post_content' => $postContent,
        ]);

        $content = PostContentUtils::getPostBody($postId);

        $this->assertSame($expected, $content);

        wp_delete_post($postId, true);
    }

    public function getPostBodyProvider()
    {
        return [
            'Post with no body' => [
                'post',
                '',
                '',
            ],
            'Post with simple body' => [
                'post',
                'Procrastination is the Thief of Time',
                '<p>Procrastination is the Thief of Time</p>',
            ],
            'Post with HTML body' => [
                'post',
                '<p>Procrastination is the <b>Thief of Time</b></p>',
                '<p>Procrastination is the <b>Thief of Time</b></p>',
            ],
            'Post with a shortcode' => [
                'post',
                'Shortcode: [shortcode_test to_lower="Foo" to_upper="Bar"]Baz[/shortcode_test]',
                '<p>Shortcode: <em>foo, BAR, Baz</em></p>',
            ],
            'Page' => [
                'page',
                'This is a page.',
                '<p>This is a page.</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPostBodyWithPostExcerptProvider
     */
    public function getPostBodyWithPostExcerpt($prependExcerpt, $postArgs, $expected)
    {
        $postId = self::factory()->post->create($postArgs);

        update_option('beyondwords_prepend_excerpt', $prependExcerpt);

        $content = PostContentUtils::getPostBody($postId);

        delete_option('beyondwords_prepend_excerpt');

        $this->assertSame($expected, $content);

        wp_delete_post($postId, true);
    }

    public function getPostBodyWithPostExcerptProvider()
    {
        $excerpt = "What is an excerpt?\n\nExcerpt is an optional text associated to a Post. Most of the time, it is used as the Post summary.\n\nNot finding the Excerpt editing box? Check your Post’s Screen Options.";
        $content = "In my younger and more vulnerable years my father gave me some advice that I've been turning over in my mind ever since.\n\n“Whenever you feel like criticizing anyone, he told me, just remember that all the people in this world haven't had the advantages that you've had.”";

        $expectExcerpt = "<p>What is an excerpt?</p>\n<p>Excerpt is an optional text associated to a Post. Most of the time, it is used as the Post summary.</p>\n<p>Not finding the Excerpt editing box? Check your Post&rsquo;s Screen Options.</p>";
        $expectContent = "<p>In my younger and more vulnerable years my father gave me some advice that I&#8217;ve been turning over in my mind ever since.</p>\n<p>“Whenever you feel like criticizing anyone, he told me, just remember that all the people in this world haven&#8217;t had the advantages that you&#8217;ve had.”</p>";

        return [
            'Process excerpts Off, Post without excerpt' => [
                'prependExcerpt' => '',
                'postArgs' => [
                    'post_type'    => 'post',
                    'post_title'   => 'Process excerpts Off, Post without excerpt',
                    'post_excerpt' => '',
                    'post_content' => $content,
                ],
                'expect' => $expectContent,
            ],
            'Process excerpts Off, Post with excerpt' => [
                'prependExcerpt' => '',
                'postArgs' => [
                    'post_type'    => 'post',
                    'post_title'   => 'Process excerpts Off, Post with excerpt',
                    'post_excerpt' => $excerpt,
                    'post_content' => $content,
                ],
                'expect' => $expectContent,
            ],
            'Process excerpts On, Post without excerpt' => [
                'prependExcerpt' => '1',
                'postArgs' => [
                    'post_type'    => 'post',
                    'post_title'   => 'Process excerpts On, Post without excerpt',
                    'post_excerpt' => '',
                    'post_content' => $content,
                ],
                'expect' => $expectContent,
            ],
            'Process excerpts On, Post with excerpt' => [
                'prependExcerpt' => '1',
                'postArgs' => [
                    'post_type'    => 'post',
                    'post_title'   => 'Process excerpts On, Post with excerpt',
                    'post_excerpt' => $excerpt,
                    'post_content' => $content,
                ],
                'expect' => $expectContent,
            ],
        ];
    }

    /**
     *
     */
    public function exportedDataHelper($path)
    {
        $handle = fopen($path, 'r');

        $output = [];

        // Ignore first line of CSV
        fgetcsv($handle, 0, ',', '"', "\0");

        // Process remaining lines
        while (($data = fgetcsv($handle, 0, ',', '"', "\0")) !== false) {
            // Only test Posts with a state of "Processed"
            if (strtolower($data[11]) == 'processed') {
                $output['spktdotblog ID ' . $data[0]] = $data;
            }
        }

        return $output;
    }

    /**
     *
     */
    public function hasGenerateAudioProvider()
    {
        return [
            'No BeyondWords metadata'             => [false, []],
            'beyondwords_generate_audio is ""'    => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '']]],
            'beyondwords_generate_audio is "0"'   => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '0']]],
            'beyondwords_generate_audio is "-1"'  => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '-1']]],
            'beyondwords_generate_audio is "1"'   => [true,  ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '1']]],
            'speechkit_generate_audio is ""'      => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '']]],
            'speechkit_generate_audio is "0"'     => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '0']]],
            'speechkit_generate_audio is "-1"'    => [false, ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '-1']]],
            'speechkit_generate_audio is "1"'     => [true,  ['post_title' => 'PostContentUtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '1']]],
        ];
    }

    /**
     * @test
     *
     * @group getContentParams
     **/
    public function getContentParams()
    {
        // Create the user
        $user = self::factory()->user->create_and_get([
            'role' => 'editor',
            'display_name' => 'Jane Smith',
        ]);

        wp_set_current_user($user->ID);

        $args = [
            'post_title'   => 'PostContentUtilsTest::getContentParams',
            'post_excerpt' => 'The excerpt.',
            'post_content' => '<p>Some test HTML.</p>',
            'post_date'    => '2012-12-25T01:02:03Z',
            'meta_input'   => [
                'beyondwords_language_code'    => 'en_US',
                'beyondwords_summary_voice_id' => '3555',
                'beyondwords_title_voice_id'   => '2517',
                'beyondwords_body_voice_id'    => '3558',
            ],
        ];

        $postId = self::factory()->post->create($args);
        $attachmentId = self::factory()->attachment->create_upload_object( __DIR__ . '/../../images/kitten.jpg', $postId );

        set_post_thumbnail($postId, $attachmentId);

        update_option('beyondwords_prepend_excerpt', '1');
        update_option('beyondwords_project_auto_publish_enabled', true);

        $body = PostContentUtils::getContentParams($postId);
        $body = json_decode($body, true);

        delete_option('beyondwords_prepend_excerpt');
        delete_option('beyondwords_project_auto_publish_enabled');

        $this->assertSame($args['post_title'], $body['title']);
        $this->assertSame('<div data-beyondwords-summary="true" data-beyondwords-voice-id="3555"><p>The excerpt.</p></div><p>Some test HTML.</p>', $body['body']);
        $this->assertSame(get_the_permalink($postId), $body['source_url']);
        $this->assertSame(strval($postId), $body['source_id']);
        $this->assertSame('Jane Smith', $body['author']);
        $thumbnailUrl = strval(wp_get_original_image_url(get_post_thumbnail_id($postId)));
        $this->assertNotEmpty($thumbnailUrl);
        $this->assertSame($thumbnailUrl, $body['image_url']);
        $this->assertSame('{"taxonomy":{"category":["Uncategorized"]}}', wp_json_encode($body['metadata']));
        $this->assertSame($args['post_date'], $body['publish_date']);

        // { published: true } should be sent because auto-publish is true
        $this->assertArrayHasKey('published', $body);
        $this->assertTrue($body['published']);

        $this->assertSame('en_US', $body['language']);
        $this->assertSame(3555, $body['summary_voice_id']);
        $this->assertSame(2517, $body['title_voice_id']);
        $this->assertSame(3558, $body['body_voice_id']);

        update_option('beyondwords_project_auto_publish_enabled', false);

        $body = PostContentUtils::getContentParams($postId);
        $body = json_decode($body, true);

        delete_option('beyondwords_project_auto_publish_enabled');

        // { published: false } should not exist because auto-publish is false
        $this->assertArrayNotHasKey('published', $body);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @group getContentParams
     **/
    public function getContentParamsForPendingReviewStatus()
    {
        // Create the user
        $user = self::factory()->user->create_and_get([
            'role' => 'editor',
            'display_name' => 'Jane Smith',
        ]);

        wp_set_current_user($user->ID);

        $args = [
            'post_title'   => 'PostContentUtilsTest::getContentParamsForPendingReviewStatus',
            'post_content' => '<p>Some test HTML.</p>',
            'post_date'    => '2012-12-25T01:02:03Z',
            'post_status'  => 'pending',
        ];

        $postId = self::factory()->post->create($args);

        update_option('beyondwords_project_auto_publish_enabled', true);

        $body = PostContentUtils::getContentParams($postId);
        $body = json_decode($body, true);

        delete_option('beyondwords_project_auto_publish_enabled');

        $this->assertSame($args['post_title'], $body['title']);
        $this->assertSame(PostContentUtils::getPostBody($postId), $body['body']);
        $this->assertSame('Jane Smith', $body['author']);
        $this->assertSame(get_the_permalink($postId), $body['source_url']);

        // { published: false } SHOULD be sent because post_status is "pending"
        $this->assertArrayHasKey('published', $body);
        $this->assertFalse($body['published']);

        /*
         * Posts with "Pending Review" status will not have a `publish_date` date because
         * get_post_time() returns `false` for posts which are "Pending Review".
         */
        $this->assertArrayNotHasKey('publish_date', $body);

        // Set auto-publish to false
        update_option('beyondwords_project_auto_publish_enabled', false);

        $body = PostContentUtils::getContentParams($postId);
        $body = json_decode($body, true);

        delete_option('beyondwords_project_auto_publish_enabled');

        // { published: false } SHOULD be sent because post_status is "pending"
        $this->assertArrayHasKey('published', $body);
        $this->assertFalse($body['published']);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @group getContentParams
     **/
    public function getPostBodyParamsFilterTest()
    {
        $postId = self::factory()->post->create([
            'post_title'   => 'Testing beyondwords_content_params filter',
            'post_content' => 'Baz bar foo.',
            'post_status'  => 'publish'
        ]);

        $filter = function($params, $postId) {
            // Custom body
            $params['body'] = '[POST ID: ' . $postId. ']' . $params['body'] . '[ADDED AFTER]';

            // Custom metadata
            $params['metadata']->custom = $postId;

            return $params;
        };

        add_filter('beyondwords_content_params', $filter, 10, 2);

        $params = PostContentUtils::getContentParams($postId);

        remove_filter('beyondwords_content_params', $filter);

        $params = json_decode($params, true);

        $this->assertSame('[POST ID: ' . $postId. ']<p>Baz bar foo.</p>[ADDED AFTER]', $params['body']);
        $this->assertSame($postId, $params['metadata']['custom']);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     **/
    public function getMetadataTest()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'Testing PostContentUtils::getMetadata()',
            'post_status' => 'publish'
        ]);

        $metadata = PostContentUtils::getMetadata($postId);

        $this->assertIsObject($metadata);

        $this->assertTrue(property_exists($metadata, 'taxonomy'));
        $this->assertIsObject($metadata->taxonomy);

        $this->assertTrue(property_exists($metadata->taxonomy, 'category'));
        $this->assertIsArray($metadata->taxonomy->category);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @group metadata
     **/
    public function getAllTaxonomiesAndTerms()
    {
        $flatTaxonomy = 'flat';
        $hierarchicalTaxonomy = 'hierarchical';

        // Create flat taxonomy & terms (Tag-like)
        register_taxonomy($flatTaxonomy, 'post');
        foreach (['flat1', 'flat2', 'flat3'] as $term) {
            wp_insert_term($term, $flatTaxonomy);
        }

        // Create hierarchical taxonomy & terms (Category-like)
        register_taxonomy($hierarchicalTaxonomy, 'post', ['hierarchical' => true]);
        $hierarchicalTerms = [];
        foreach (['hier1', 'hier2', 'hier3'] as $term) {
            $hierarchicalTerms[] = wp_insert_term($term, $hierarchicalTaxonomy);
        }
        $hierarchicalTermIds = wp_list_pluck($hierarchicalTerms, 'term_id');

        $editor = self::factory()->user->create(['role' => 'editor']);
        $this->assertTrue(user_can($editor, 'edit_posts'));

        wp_set_current_user($editor);

        // Create a post with selected terms
        $postId = self::factory()->post->create([
            'post_title' => 'Testing PostContentUtils::getAllTaxonomiesAndTerms()',
            'post_status' => 'publish',
            'tax_input' => [
                $flatTaxonomy => 'flat1, flat2, flat3',
                $hierarchicalTaxonomy => $hierarchicalTermIds,
            ]
        ]);

        $taxonomies = PostContentUtils::getAllTaxonomiesAndTerms($postId);

        $this->assertIsObject($taxonomies);

        $this->assertTrue(property_exists($taxonomies, $flatTaxonomy));
        $this->assertIsArray($taxonomies->{$flatTaxonomy});
        $this->assertSame('flat1', $taxonomies->{$flatTaxonomy}[0]);
        $this->assertSame('flat2', $taxonomies->{$flatTaxonomy}[1]);
        $this->assertSame('flat3', $taxonomies->{$flatTaxonomy}[2]);

        $this->assertTrue(property_exists($taxonomies, $hierarchicalTaxonomy));
        $this->assertIsArray($taxonomies->{$hierarchicalTaxonomy});
        $this->assertSame('hier1', $taxonomies->{$hierarchicalTaxonomy}[0]);
        $this->assertSame('hier2', $taxonomies->{$hierarchicalTaxonomy}[1]);
        $this->assertSame('hier3', $taxonomies->{$hierarchicalTaxonomy}[2]);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     **/
    public function getAuthorName()
    {
        $name = 'Jane Smith';

        // Create the user
        $user = self::factory()->user->create_and_get([
            'role' => 'editor',
            'display_name' => $name,
        ]);

        $this->assertTrue(user_can($user->ID, 'edit_posts'));
        $this->assertSame($name, $user->data->display_name);

        wp_set_current_user($user->ID);

        // Create the post as the new user
        $post = self::factory()->post->create_and_get([
            'post_title' => 'Testing PostContentUtils::getAuthorName()',
            'post_status' => 'publish',
        ]);

        $this->assertSame($name, PostContentUtils::getAuthorName($post->ID));

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     * @dataProvider addMarkerAttributeWithHTMLTagProcessorProvider
     */
    public function addMarkerAttributeWithHTMLTagProcessor($html, $marker, $expect) {
        $result = PostContentUtils::addMarkerAttributeWithHTMLTagProcessor($html, $marker);

        $this->assertSame($expect, trim($result));
    }

    public function addMarkerAttributeWithHTMLTagProcessorProvider($args) {
        return [
            'No HTML' => [
                'html'   => '',
                'marker' => 'foo',
                'expect' => '',
            ],
            'No marker' => [
                'html'   => '<p>Text</p>',
                'marker' => '',
                'expect' => '<p>Text</p>',
            ],
            'Paragraph' => [
                'html'   => '<p>Text</p>',
                'marker' => 'foo',
                'expect' => '<p data-beyondwords-marker="foo">Text</p>',
            ],
            'Empty paragraph' => [
                'html'   => '<p></p>',
                'marker' => 'foo',
                'expect' => '<p data-beyondwords-marker="foo"></p>',
            ],
            'Existing attributes' => [
                'html'   => '<p class="my-class">Text</p>',
                'marker' => 'foo',
                'expect' => '<p data-beyondwords-marker="foo" class="my-class">Text</p>',
            ],
            'Multiple root elements' => [
                'html'   => "<div>One</div>\n<div>Two</div>",
                'marker' => 'foo',
                'expect' => "<div data-beyondwords-marker=\"foo\">One</div>\n<div>Two</div>",
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addMarkerAttributeWithDOMDocumentProvider
     */
    public function addMarkerAttributeWithDOMDocument($html, $marker, $expect)
    {
        $result = PostContentUtils::addMarkerAttributeWithDOMDocument($html, $marker);

        $this->assertSame($expect, trim($result));
    }

    public function addMarkerAttributeWithDOMDocumentProvider($args) {
        return [
            'No HTML' => [
                'html'   => '',
                'marker' => 'foo',
                'expect' => '',
            ],
            'No marker' => [
                'html'   => '<p>Text</p>',
                'marker' => '',
                'expect' => '<p>Text</p>',
            ],
            'Paragraph' => [
                'html'   => '<p>Text</p>',
                'marker' => 'foo',
                'expect' => '<p data-beyondwords-marker="foo">Text</p>',
            ],
            'Empty paragraph' => [
                'html'   => '<p></p>',
                'marker' => 'foo',
                'expect' => '<p data-beyondwords-marker="foo"></p>',
            ],
            'Existing attributes' => [
                'html'   => '<p class="my-class">Text</p>',
                'marker' => 'foo',
                'expect' => '<p class="my-class" data-beyondwords-marker="foo">Text</p>',
            ],
            'Multiple root elements' => [
                'html'   => "<div>One</div>\n<div>Two</div>",
                'marker' => 'foo',
                'expect' => "<div data-beyondwords-marker=\"foo\">One</div>\n<div>Two</div>",
            ],
        ];
    }
}
