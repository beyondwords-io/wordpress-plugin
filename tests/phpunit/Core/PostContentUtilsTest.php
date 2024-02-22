<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Component\Post\PostContentUtils;

class PostContentUtilsTest extends WP_UnitTestCase
{
    /**
     * Sample data from the custom field `speechkit_info`.
     *
     * This was exported from a test site running plugin v2.7.10.
     *
     * @var string
     */
    private $sampleSpeechkitInfo = 'a:16:{s:2:"id";s:2:"49";s:10:"podcast_id";i:9969567;s:3:"url";s:53:"https://speechkit.pressingspace.com/post-from-2-7-10/";s:5:"title";s:16:"Post from 2.7.10";s:6:"author";s:13:"pressingspace";s:7:"summary";s:0:"";s:5:"image";s:1:"f";s:12:"published_at";s:24:"2021-11-17T17:44:58.000Z";s:5:"state";s:9:"processed";s:9:"share_url";s:25:"https://spkt.io/a/9969567";s:13:"share_version";s:2:"v2";s:5:"media";a:2:{i:0;a:10:{s:2:"id";i:11542939;s:4:"role";s:4:"body";s:12:"content_type";s:21:"application/x-mpegURL";s:3:"url";s:118:"https://abcdefghabcdef.cloudfront.net/audio/projects/9969/contents/9969567/media/abcdefghabcdefghabcdefghabcdefgh.m3u8";s:12:"download_url";N;s:10:"created_at";s:24:"2021-11-17T17:45:03.211Z";s:10:"updated_at";s:24:"2021-11-17T17:45:03.211Z";s:5:"state";s:9:"processed";s:8:"duration";i:4;s:5:"voice";N;}i:1;a:10:{s:2:"id";i:11542938;s:4:"role";s:4:"body";s:12:"content_type";s:10:"audio/mpeg";s:3:"url";s:126:"https://abcdefghabcdef.cloudfront.net/audio/projects/9969/contents/9969567/media/abcdefghabcdefghabcdefghabcdefgh_compiled.mp3";s:12:"download_url";N;s:10:"created_at";s:24:"2021-11-17T17:45:02.078Z";s:10:"updated_at";s:24:"2021-11-17T17:45:02.078Z";s:5:"state";s:9:"processed";s:8:"duration";i:4;s:5:"voice";N;}}s:11:"player_type";s:14:"EmbeddedPlayer";s:24:"next_content_external_id";N;s:11:"ad_disabled";b:0;s:10:"project_id";i:9969;}';

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
    public function getContentWithoutExcludedBlocks()
    {
        $post = self::factory()->post->create_and_get([
            'post_title' => 'UtilsTest:getContentWithoutExcludedBlocks',
            'post_content' => '<!-- wp:paragraph --><p>1. Included.</p><!-- /wp:paragraph --><!-- wp:paragraph {"beyondwordsAudio":false} --><p>2. Excluded.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>3. Included.</p><!-- /wp:paragraph -->',
        ]);

        $content = PostContentUtils::getContentWithoutExcludedBlocks($post);

        $this->assertSame('<p>1. Included.</p><p>3. Included.</p>', $content);

        wp_delete_post($post->ID, true);
    }

    /**
     * @test
     */
    public function getBodyWithInvalidPostId()
    {
        $postId = $this->factory->post->create([
            'post_title' => 'UtilsTest:getBodyWithInvalidPostId',
        ]);

        $this->expectException(\Exception::class);

        $content = PostContentUtils::getBody(-1);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     * @dataProvider getBodyProvider
     */
    public function getBody($postType, $postContent, $expected)
    {
        // A shortcode which tests both attribues and content
        add_shortcode('shortcode_test', function($atts, $content="") {
            return sprintf('<em>%s, %s, %s</em>', strtolower($atts['to_lower']), strtoupper($atts['to_upper']), $content);
        });

        $postId = $this->factory->post->create([
            'post_title' => 'UtilsTest:getBody',
            'post_type' => $postType,
            'post_content' => $postContent,
        ]);

        $content = PostContentUtils::getBody($postId);

        $this->assertSame($expected, $content);

        wp_delete_post($postId, true);
    }

    public function getBodyProvider()
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
            'Post with deprecated Start/Stop shortcode' => [
                'post',
                'Foo [SpeechKit-Start]Bar[SpeechKit-Stop] Baz',
                '<p>Bar</p>',
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
     * @dataProvider getBodyWithPostExcerptProvider
     */
    public function getBodyWithPostExcerpt($prependExcerpt, $postArgs, $expected)
    {
        $postId = self::factory()->post->create($postArgs);

        update_option('beyondwords_prepend_excerpt', $prependExcerpt);

        $content = PostContentUtils::getBody($postId);

        delete_option('beyondwords_prepend_excerpt');

        $this->assertSame($expected, $content);

        wp_delete_post($postId, true);
    }

    public function getBodyWithPostExcerptProvider()
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
            'beyondwords_generate_audio is ""'    => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '']]],
            'beyondwords_generate_audio is "0"'   => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '0']]],
            'beyondwords_generate_audio is "-1"'  => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '-1']]],
            'beyondwords_generate_audio is "1"'   => [true,  ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['beyondwords_generate_audio' => '1']]],
            'speechkit_generate_audio is ""'      => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '']]],
            'speechkit_generate_audio is "0"'     => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '0']]],
            'speechkit_generate_audio is "-1"'    => [false, ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '-1']]],
            'speechkit_generate_audio is "1"'     => [true,  ['post_title' => 'UtilsTest:hasGenerateAudio', 'meta_input' => ['speechkit_generate_audio' => '1']]],
        ];
    }

    /**
     * @test
     *
     * @group bodyJson
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
            'post_title'   => 'UtilsTest::getContentParams',
            'post_excerpt' => 'The excerpt.',
            'post_content' => '<p>Some test HTML.</p>',
            'post_date'    => '2012-12-25T01:02:03Z',
        ];

        $postId = self::factory()->post->create($args);
        $attachmentId = self::factory()->attachment->create_upload_object( __DIR__ . '/../../images/kitten.jpg', $postId );

        set_post_thumbnail($postId, $attachmentId);

        update_option('beyondwords_prepend_excerpt', '1');

        $body = PostContentUtils::getContentParams($postId);
        $body = json_decode($body);

        delete_option('beyondwords_prepend_excerpt');

        $this->assertSame($args['post_title'], $body->title);
        $this->assertSame('<p>The excerpt.</p>', $body->summary);
        $this->assertSame('<p>Some test HTML.</p>', $body->body);
        $this->assertSame(get_the_permalink($postId), $body->source_url);
        $this->assertSame(strval($postId), $body->source_id);
        $this->assertSame('Jane Smith', $body->author);
        $thumbnailUrl = strval(wp_get_original_image_url(get_post_thumbnail_id($postId)));
        $this->assertNotEmpty($thumbnailUrl);
        $this->assertSame($thumbnailUrl, $body->image_url);
        $this->assertSame('{"taxonomy":{"category":["Uncategorized"]}}', json_encode($body->metadata));
        $this->assertSame(true, $body->published);
        $this->assertSame($args['post_date'], $body->publish_date);

        // { published: true } should be sent because post_status is NOT "pending"
        $this->assertTrue(property_exists($body, 'published'));
        $this->assertTrue($body->published);

        // { external_id } has been removed
        $this->assertFalse(property_exists($body, 'external_id'));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     *
     * @group bodyJson
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
            'post_title'   => 'UtilsTest::getContentParamsForPendingReviewStatus',
            'post_content' => '<p>Some test HTML.</p>',
            'post_date'    => '2012-12-25T01:02:03Z',
            'post_status'  => 'pending',
        ];

        $postId = self::factory()->post->create($args);

        $body = PostContentUtils::getContentParams($postId);

        $body = json_decode($body);

        $this->assertSame($args['post_title'], $body->title);
        $this->assertSame(PostContentUtils::getBody($postId), $body->body);
        $this->assertSame('Jane Smith', $body->author);
        $this->assertSame(get_the_permalink($postId), $body->source_url);

        // { published: false } SHOULD be sent because post_status is "pending"
        $this->assertTrue(property_exists($body, 'published'));
        $this->assertFalse($body->published);

        /*
         * Posts with "Pending Review" status will not have a `publish_date` date because
         * get_post_time() returns `false` for posts which are "Pending Review".
         */
        $this->assertFalse(property_exists($body, 'publish_date'));

        // { external_id } has been removed
        $this->assertFalse(property_exists($body, 'external_id'));

        wp_delete_post($postId, true);
    }

    /**
     * @test
     **/
    public function getBodyParamsFilterTest()
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

        $params = json_decode($params);

        $this->assertSame('[POST ID: ' . $postId. ']<p>Baz bar foo.</p>[ADDED AFTER]', $params->body);
        $this->assertSame($postId, $params->metadata->custom);

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
            'One simple root element' => [
                'html'   => '<div>Text</div>',
                'marker' => 'foo',
                'expect' => '<div data-beyondwords-marker="foo">Text</div>',
            ],
            'Existing attributes' => [
                'html'   => '<div class="my-class"><p>Text</p></div>',
                'marker' => 'foo',
                'expect' => '<div data-beyondwords-marker="foo" class="my-class"><p>Text</p></div>',
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
            'One simple root element' => [
                'html'   => '<div>Text</div>',
                'marker' => 'foo',
                'expect' => '<div data-beyondwords-marker="foo">Text</div>',
            ],
            'Existing attributes' => [
                'html'   => '<div class="my-class"><p>Text</p></div>',
                'marker' => 'foo',
                'expect' => '<div class="my-class" data-beyondwords-marker="foo"><p>Text</p></div>',
            ],
            'Multiple root elements' => [
                'html'   => "<div>One</div>\n<div>Two</div>",
                'marker' => 'foo',
                'expect' => "<div data-beyondwords-marker=\"foo\">One</div>\n<div>Two</div>",
            ],
        ];
    }
}
