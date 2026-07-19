<?php

use BeyondWords\Editor\Classic\Metabox;
use \Symfony\Component\DomCrawler\Crawler;

class MetaboxTest extends TestCase
{
    /**
     * @var \BeyondWords\Editor\Classic\Metabox
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.

        global $wp_meta_boxes;
        $wp_meta_boxes = null;
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
    public function init()
    {
        Metabox::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('add_meta_boxes', array(Metabox::class, 'add_meta_box_callback')));
    }

    /**
     * @test
     */
    public function add_meta_box_callback()
    {
        global $wp_meta_boxes;

        Metabox::add_meta_box_callback('post');

        $this->assertArrayHasKey('beyondwords', $wp_meta_boxes['post']['side']['default']);

        $wp_meta_boxes = null;
    }

    /**
     * @test
     * @group integration
     * @dataProvider render_meta_box_content_provider
     */
    public function render_meta_box_content($expectPlayer, $postArgs)
    {
        // Set up API credentials for metabox rendering
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create($postArgs);

        $html = $this->capture_output(function () use ($postId) {
            Metabox::render_meta_box_content($postId);
        });

        $crawler = new Crawler($html);

        // Generate audio checkbox is always rendered.
        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-generate-audio'));

        // The sections render as headings in the required order.
        $headings = $crawler->filter('h4.beyondwords-metabox__heading')
            ->each(fn ($node) => $node->text());
        $this->assertSame(['Player', 'Content', 'Format', 'Voice', 'Data'], $headings);

        // Each section's primary control is present.
        $this->assertCount(1, $crawler->filter('select#beyondwords_embed'));
        $this->assertCount(1, $crawler->filter('select#beyondwords_source'));
        $this->assertCount(1, $crawler->filter('select#beyondwords_output'));
        $this->assertCount(1, $crawler->filter('select#beyondwords_language_code'));

        $this->assertCount(1, $crawler->filter('p#beyondwords-metabox-help'));
        $this->assertCount(0, $crawler->filter('div#beyondwords-metabox-errors'));

        wp_delete_post($postId, true);

        // Clean up
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    public function render_meta_box_content_provider()
    {
        return [
            'No Post Meta' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::1',
                ],
            ],
            'Empty beyondwords_content_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::2',
                    'meta_input' => ['beyondwords_content_id' => '']
                ],
            ],
            'Empty beyondwords_podcast_id' => [
                'expectPlayer' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::3',
                    'meta_input' => ['beyondwords_podcast_id' => '']
                ],
            ],
            'beyondwords_content_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::4',
                    'meta_input' => ['beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
            'beyondwords_podcast_id' => [
                'expectPlayer' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::render_meta_box_content::5',
                    'meta_input' => ['beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID]
                ],
            ],
        ];
    }

    /**
     * @test
     * @group integration
     */
    public function render_meta_box_content_with_invalid_post()
    {
        // Set up API credentials
        update_option('beyondwords_api_key', BEYONDWORDS_TESTS_API_KEY);
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        // Pass an invalid post (array instead of WP_Post or int)
        $html = $this->capture_output(function () {
            Metabox::render_meta_box_content(['ID' => BEYONDWORDS_TESTS_PROJECT_ID]);
        });

        $this->assertEmpty($html);

        // Clean up
        delete_option('beyondwords_api_key');
        delete_option('beyondwords_project_id');
    }

    /**
     * A crafted Content ID must never reach a JS execution context.
     *
     * Regression test for a stored XSS. The Content ID is editable post meta,
     * saved with only sanitize_text_field() (which keeps double quotes), and was
     * emitted into an inline `onload` JS string with esc_attr(). esc_attr()
     * encodes `"` as `&quot;`, but the browser HTML-decodes the attribute before
     * compiling the handler, so `&quot;` became a real `"` that closed the JS
     * string literal and ran the rest of the value as code.
     *
     * REST-API content is now embedded by classic-metabox.js only after it polls
     * the content status, so player_embed() emits no inline handler at all on
     * this path. The Content ID travels in an esc_attr()'d `data-content-id`
     * attribute and is read back with getAttribute(), so it is never interpolated
     * into JavaScript source — removing the vector rather than escaping around
     * it. The client-side path still embeds inline and is covered by
     * player_embed_json_encodes_the_client_side_config() below.
     *
     * @test
     */
    public function player_embed_neutralises_content_id_xss()
    {
        $payload = '"});alert(document.domain);({"';

        $postId = self::factory()->post->create([
            'post_title' => 'MetaboxTest::player_embed_neutralises_content_id_xss',
        ]);

        // Set the meta after creation so no save_post handler can overwrite the
        // payload before we render — this keeps the assertions meaningful.
        update_post_meta($postId, 'beyondwords_project_id', 12345);
        update_post_meta($postId, 'beyondwords_content_id', $payload);

        $html = $this->capture_output(function () use ($postId) {
            Metabox::player_embed($postId);
        });

        $crawler = new Crawler($html);

        // The injected markup must not spawn extra elements: exactly one player
        // container and one (SDK) <script>.
        $container = $crawler->filter('#beyondwords-metabox-player');
        $this->assertCount(1, $container);

        $script = $crawler->filter('script');
        $this->assertCount(1, $script);

        // No inline handler on the polling path, so the payload has no JS
        // execution context to break out of.
        $this->assertNull($script->attr('onload'));

        // Crawler::attr() returns the HTML-decoded attribute — the exact string
        // getAttribute() hands to the player SDK as a plain value.
        $this->assertSame($payload, $container->attr('data-content-id'));

        // esc_attr() encoded the quotes, so the raw break-out sequence never
        // appears in the emitted markup.
        $this->assertStringNotContainsString('"});alert(document.domain)', $html);

        wp_delete_post($postId, true);
    }

    /**
     * The client-side (Magic Embed) path still embeds inline, so it must keep
     * JSON-encoding its config.
     *
     * That integration is keyed on the source (post) ID and has no Content ID to
     * poll, so player_embed() writes an inline `onload` handler. The preview
     * token comes from the REST API and is untrusted in that output context, so
     * the config is JSON-encoded with the HEX flags: an injected quote is
     * hex-encoded and stays inside the JS string literal instead of closing it.
     *
     * @test
     */
    public function player_embed_json_encodes_the_client_side_config()
    {
        $payload = '"});alert(document.domain);({"';

        $postId = self::factory()->post->create([
            'post_title' => 'MetaboxTest::player_embed_json_encodes_the_client_side_config',
        ]);

        // Client-side integration: a project ID but no Content ID, so the player
        // embeds inline from the source (post) ID.
        update_post_meta($postId, 'beyondwords_project_id', 12345);
        update_post_meta(
            $postId,
            'beyondwords_integration_method',
            \BeyondWords\Settings\Fields::INTEGRATION_CLIENT_SIDE
        );
        update_post_meta($postId, 'beyondwords_preview_token', $payload);

        $html = $this->capture_output(function () use ($postId) {
            Metabox::player_embed($postId);
        });

        $crawler = new Crawler($html);

        $script = $crawler->filter('#beyondwords-metabox-player script');
        $this->assertCount(1, $script);

        // Crawler::attr() returns the HTML-decoded attribute — exactly what the
        // browser hands to the JS engine when the onload handler fires.
        $onload = $script->attr('onload');

        // wp_json_encode() has hex-encoded the injected quotes so they stay
        // inside the JS string literal. Assert the exact encoded value
        // (structural quotes included) is present verbatim.
        $encoded = wp_json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        );
        $this->assertStringContainsString($encoded, $onload);

        // The raw break-out sequence (a bare " closing the string) must be absent.
        $this->assertStringNotContainsString('"});alert(document.domain)', $onload);

        // The player is still initialised.
        $this->assertStringContainsString('new BeyondWords.Player(', $onload);

        wp_delete_post($postId, true);
    }

    /**
     * @test
     * @dataProvider errors_provider
     */
    public function errors($expect, $postArgs)
    {
        $post = self::factory()->post->create_and_get($postArgs);

        $html = $this->capture_output(function () use ($post) {
            Metabox::errors($post);
        });

        $crawler = new Crawler($html);

        if ($expect) {
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors'));
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error'));
            $this->assertCount(1, $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error > p'));

            $errorText = $crawler->filter('#beyondwords-metabox-errors > .beyondwords-error > p')->text();
            $this->assertSame($errorText, get_post_meta($post->ID, 'beyondwords_error_message', true));
        } else {
            $this->assertSame('', $html);
        }

        wp_delete_post($post->ID, true);
    }

    public function errors_provider()
    {
        return [
            'No errors' => [
                'expect' => false,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::errors::1',
                ],
            ],
            'Error 500' => [
                'expect' => true,
                'postArgs' => [
                    'post_title' => 'MetaboxTest::errors::2',
                    'meta_input' => ['beyondwords_error_message' => '[500] Unknown error.']
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function regenerate_instructions()
    {
        $html = $this->capture_output(function () {
            Metabox::regenerate_instructions();
        });

        $crawler = new Crawler($html);

        $text = 'To create audio, resolve the error above then select ‘Update’ with ‘Generate audio’ checked.';

        $this->assertCount(1, $crawler->filter('p'));
        $this->assertSame($text, $crawler->filter('p')->text());
    }
}
