<?php

declare(strict_types=1);

use BeyondWords\Post\Meta;
use BeyondWords\Core\Utils;

class MetaTest extends TestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * Test if we can get a Project ID from the plugin settings.
     *
     * @test
     * @dataProvider get_project_id_with_plugin_setting_provider
     *
     * @param boolean $expected Expected Project ID
     * @param int     $postId   WordPress Post ID
     */
    public function get_project_id_with_plugin_setting($expected, $postArgs)
    {
        $postId = self::factory()->post->create($postArgs);

        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $this->assertEquals($expected, Meta::get_project_id($postId));

        delete_option('beyondwords_project_id');

        wp_delete_post($postId, true);
    }

    /**
     *
     */
    public function get_project_id_with_plugin_setting_provider()
    {
        return [
            'No BeyondWords metadata' => [BEYONDWORDS_TESTS_PROJECT_ID, ['post_title' => 'UtilsTest:getProjectIdWithPluginSettingProvider']],
            // post_meta.beyondwords_project_id
            'beyondwords_project_id is empty'   => [BEYONDWORDS_TESTS_PROJECT_ID, ['post_title' => 'UtilsTest:getProjectIdWithPluginSettingProvider', 'meta_input' => ['beyondwords_project_id' => '']]],
            'beyondwords_project_id is invalid' => [BEYONDWORDS_TESTS_PROJECT_ID, ['post_title' => 'UtilsTest:getProjectIdWithPluginSettingProvider', 'meta_input' => ['beyondwords_project_id' => 'foo']]],
            // post_meta.speechkit_project_id
            'speechkit_project_id is empty'   => [BEYONDWORDS_TESTS_PROJECT_ID, ['post_title' => 'UtilsTest:getProjectIdWithPluginSettingProvider', 'meta_input' => ['speechkit_project_id' => '']]],
            'speechkit_project_id is invalid' => [BEYONDWORDS_TESTS_PROJECT_ID, ['post_title' => 'UtilsTest:getProjectIdWithPluginSettingProvider', 'meta_input' => ['speechkit_project_id' => 'foo']]],
        ];
    }

    /**
     * Test if we can get a Project ID from the post.
     *
     * @test
     * @dataProvider get_project_id_without_plugin_setting_provider
     *
     * @param boolean $expected Expected Project ID
     * @param int     $postId   WordPress Post ID
     */
    public function get_project_id_without_plugin_setting($expected, $postArgs)
    {
        $postId = self::factory()->post->create($postArgs);

        $this->assertEquals($expected, Meta::get_project_id($postId));

        wp_delete_post($postId, true);
    }

    /**
     *
     */
    public function get_project_id_without_plugin_setting_provider()
    {
        return [
            'No BeyondWords metadata' => [false, ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider']],
            // post_meta.beyondwords_project_id
            'beyondwords_project_id is empty'   => [false, ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['beyondwords_project_id' => '']]],
            'beyondwords_project_id is invalid' => [false, ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['beyondwords_project_id' => 'foo']]],
            'beyondwords_project_id = ' . BEYONDWORDS_TESTS_PROJECT_ID      => [BEYONDWORDS_TESTS_PROJECT_ID,  ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID]]],
            // post_meta.speechkit_project_id
            'speechkit_project_id is empty'   => [false, ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['speechkit_project_id' => '']]],
            'speechkit_project_id is invalid' => [false, ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['speechkit_project_id' => 'foo']]],
            'speechkit_project_id = ' . BEYONDWORDS_TESTS_PROJECT_ID      => [BEYONDWORDS_TESTS_PROJECT_ID,  ['post_title' => 'UtilsTest:getProjectIdWithoutPluginSettingProvider', 'meta_input' => ['speechkit_project_id' => BEYONDWORDS_TESTS_PROJECT_ID]]],
        ];
    }

    /**
     * Test a Post's Project ID remains fixed after the plugin
     * Project ID setting changes.
     *
     * @test
     */
    public function get_project_id_when_setting_changes()
    {
        $firstPostId = self::factory()->post->create([
            'post_title' => 'UtilsTest:getProjectIdWhenSettingChanges::1',
            'meta_input' => [
                'beyondwords_project_id' => 1234,
            ],
        ]);

        $this->assertEquals(1234, Meta::get_project_id($firstPostId));

        update_option('beyondwords_project_id', 5678);

        $secondPostId = self::factory()->post->create([
            'post_title' => 'UtilsTest:getProjectIdWhenSettingChanges::2',
        ]);

        // The first Post should still have the original Project ID
        $this->assertEquals(1234, Meta::get_project_id($firstPostId));

        // The second Post should be using the updated plugin setting
        $this->assertEquals(5678, Meta::get_project_id($secondPostId));

        delete_option('beyondwords_project_id');

        wp_delete_post($firstPostId, true);
        wp_delete_post($secondPostId, true);
    }

    /**
     * Test if we can get a content ID from all the various places it can be.
     *
     * @test
     * @dataProvider get_content_id_provider
     *
     * @param boolean $expected Expected Content ID
     * @param int     $postArgs WordPress Post args
     */
    public function get_content_id($expected, $postArgs)
    {
        $postId = self::factory()->post->create($postArgs);

        $this->assertEquals($expected, Meta::get_content_id($postId));

        wp_delete_post($postId, true);
    }

    /**
     *
     */
    public function get_content_id_provider()
    {
        return [
            'No BeyondWords metadata' => [false, ['post_title' => 'UtilsTest:getContentIdProvider']],
            // post_meta.beyondwords_content_id
            'beyondwords_content_id is empty'  => ['',         ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_content_id' => '']]],
            'beyondwords_content_id = foo'     => ['foo',      ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_content_id' => 'foo']]],
            'beyondwords_content_id = ' . BEYONDWORDS_TESTS_CONTENT_ID => [BEYONDWORDS_TESTS_CONTENT_ID, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID]]],
            // post_meta.beyondwords_podcast_id
            'beyondwords_podcast_id is empty'  => ['',         ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_podcast_id' => '']]],
            'beyondwords_podcast_id = foo'     => ['foo',      ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_podcast_id' => 'foo']]],
            'beyondwords_podcast_id = ' . BEYONDWORDS_TESTS_CONTENT_ID => [BEYONDWORDS_TESTS_CONTENT_ID, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['beyondwords_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID]]],
            // post_meta.speechkit_podcast_id
            'speechkit_podcast_id is empty'    => ['',      ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['speechkit_podcast_id' => '']]],
            'speechkit_podcast_id = foo'       => ['foo',   ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['speechkit_podcast_id' => 'foo']]],
            'speechkit_podcast_id = ' . BEYONDWORDS_TESTS_CONTENT_ID => [BEYONDWORDS_TESTS_CONTENT_ID, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['speechkit_podcast_id' => BEYONDWORDS_TESTS_CONTENT_ID]]],
            // post_meta.speechkit_link
            'speechkit_link is empty'                          => [false,   ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => '']]],
            'speechkit_link = https://spkt.io/a/1234567'       => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://spkt.io/a/1234567']]],
            'speechkit_link = https://spkt.io/a/1234567/'      => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://spkt.io/a/1234567/']]],
            'speechkit_link = https://spkt.io/a/1234567?x=456' => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://spkt.io/a/1234567?x=456']]],
            'speechkit_link = https://example.com/a/1234567'   => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://example.com/a/1234567']]],
            'speechkit_link = https://spkt.io/e/1234567'       => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://spkt.io/e/1234567']]],
            'speechkit_link = https://spkt.io/m/1234567'       => [1234567, ['post_title' => 'UtilsTest:getContentIdProvider', 'meta_input' => ['_speechkit_link' => 'https://spkt.io/m/1234567']]],
        ];
    }

    /**
     * A Content ID is interpolated into BeyondWords API URL paths, so only the
     * `[a-zA-Z0-9\-]+` charset the inspect REST route allows may be stored —
     * anything with path/query characters is blanked to defeat URL injection.
     *
     * @test
     * @dataProvider sanitize_content_id_provider
     *
     * @param mixed  $input    Raw submitted Content ID.
     * @param string $expected Expected sanitized value.
     */
    public function sanitize_content_id($input, $expected)
    {
        $this->assertSame($expected, Meta::sanitize_content_id($input));
    }

    public function sanitize_content_id_provider()
    {
        return [
            // Legitimate values pass through unchanged.
            'UUID'                    => ['9279c9e0-e0b5-4789-9040-f44478ed3e9e', '9279c9e0-e0b5-4789-9040-f44478ed3e9e'],
            'numeric id'              => ['1234567', '1234567'],
            'all-zero UUID'           => ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000000'],
            'empty string'           => ['', ''],
            'surrounding whitespace'  => ['  9279c9e0-e0b5  ', '9279c9e0-e0b5'],
            // Anything outside [a-zA-Z0-9-] is blanked.
            'path traversal + query'  => ['x/../../projects/999/content/abc?force=1', ''],
            'forward slash'           => ['abc/def', ''],
            'dot segment'             => ['abc.def', ''],
            'query string'            => ['abc?force=1', ''],
            'fragment'                => ['abc#frag', ''],
            'ampersand'               => ['a&b', ''],
            'colon'                   => ['abc:def', ''],
            'underscore'              => ['content_id', ''],
            'space'                   => ['a b', ''],
            'script tag'              => ['<script>alert(1)</script>', ''],
        ];
    }

    /**
     * Get API response body from post meta field.
     *
     * @test
     * @dataProvider get_http_response_body_from_post_meta_provider
     *
     * @param boolean $expected Expected speechkit_response
     * @param int     $postArgs WordPress Post args
     */
    public function get_http_response_body_from_post_meta($expected, $postArgs)
    {
        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create($postArgs);

        $this->assertSame($expected, Meta::get_http_response_body_from_post_meta($postId, 'speechkit_response'));

        wp_delete_post($postId, true);

        delete_option('beyondwords_project_id');
    }

    /**
     *
     */
    public function get_http_response_body_from_post_meta_provider()
    {
        $json = '{"foo":"bar","baz":42}';

        return [
            'Missing'             => ['',    ['post_title' => 'UtilsTest:getHttpResponseBodyFromPostMetaProvider']],
            'Empty string'        => ['',    ['post_title' => 'UtilsTest:getHttpResponseBodyFromPostMetaProvider', 'meta_input' => ['speechkit_response' => '']]],
            'String'              => [$json, ['post_title' => 'UtilsTest:getHttpResponseBodyFromPostMetaProvider', 'meta_input' => ['speechkit_response' => $json]]],
        ];
    }

    /**
     * Legacy `speechkit_response` meta that was stored as a WordPress HTTP response
     * array or a WP_Error object (plugin ~3.x) must be read back without a fatal error.
     *
     * These non-scalar shapes cannot be seeded via `meta_input` / update_post_meta,
     * because Sync::register_meta() registers `speechkit_response` with a
     * `sanitize_text_field` sanitize_callback that coerces any write to a string. Real
     * legacy rows predate that registration, so we reproduce them by writing the
     * serialized value straight to wp_postmeta — exactly as an old plugin version did.
     *
     * @test
     * @dataProvider get_http_response_body_from_post_meta_legacy_provider
     *
     * @param string $expected  Expected return value.
     * @param mixed  $metaValue Raw (unserialized) legacy meta value to seed.
     */
    public function get_http_response_body_from_post_meta_with_legacy_data($expected, $metaValue)
    {
        global $wpdb;

        update_option('beyondwords_project_id', BEYONDWORDS_TESTS_PROJECT_ID);

        $postId = self::factory()->post->create(['post_title' => 'MetaTest:getHttpResponseBodyFromPostMetaLegacy']);

        // Write the serialized value directly, bypassing the registered sanitize_callback,
        // to mimic a legacy row already present in the database.
        $wpdb->insert(
            $wpdb->postmeta,
            [
                'post_id'    => $postId,
                'meta_key'   => 'speechkit_response',
                'meta_value' => maybe_serialize($metaValue),
            ]
        );
        wp_cache_delete($postId, 'post_meta');

        $this->assertSame($expected, Meta::get_http_response_body_from_post_meta($postId, 'speechkit_response'));

        wp_delete_post($postId, true);

        delete_option('beyondwords_project_id');
    }

    /**
     *
     */
    public function get_http_response_body_from_post_meta_legacy_provider()
    {
        $json = '{"foo":"bar","baz":42}';

        return [
            // is_array() branch: return the 'body' of the stored HTTP response.
            'HTTP response array' => [
                $json,
                ['body' => $json, 'response' => ['code' => 500, 'message' => 'Internal Server Error']],
            ],
            // is_wp_error() branch: the regression case — instance methods must not be
            // called statically, else PHP 8 throws an uncaught Error (white-screen fatal).
            'WP_Error object'     => [
                'WP_Error [http_request_failed] A valid URL was not provided.',
                new \WP_Error('http_request_failed', 'A valid URL was not provided.'),
            ],
        ];
    }

    /**
     *
     */
    public function exported_data_helper($path)
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
     * Test if we can get a content ID from all the various places it can be.
     *
     * @test
     * @dataProvider has_generate_audio_provider
     *
     * @param boolean $expected Expected method return value.
     * @param array   $postArgs WordPress post args.
     */
    public function has_generate_audio($expected, $postArgs)
    {
        // Isolate the meta-reading + fallback from the preselect setting, whose
        // default ('post' => all) would otherwise make an empty-meta post
        // preselect. Preselect-driven behaviour is covered in PreselectTest.
        update_option('beyondwords_preselect', []);

        $postId = self::factory()->post->create($postArgs);

        $this->assertEquals($expected, Meta::has_generate_audio($postId));

        wp_delete_post($postId, true);
        delete_option('beyondwords_preselect');
    }

    /**
     *
     */
    public function has_generate_audio_provider()
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
     * Test removeAllBeyondwordsMetadata removes all BeyondWords keys
     * without affecting other post meta.
     *
     * @since 6.0.1
     *
     * @test
     */
    public function remove_all_beyondwords_metadata()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'MetaTest:removeAllBeyondwordsMetadata',
        ]);

        // Set all BeyondWords meta keys. Use a hyphen rather than an underscore so
        // the value survives beyondwords_content_id's strict sanitiser
        // (Meta::sanitize_content_id) while remaining valid for every other key.
        $beyondwordsKeys = Utils::get_post_meta_keys('all');

        foreach ($beyondwordsKeys as $key) {
            update_post_meta($postId, $key, 'test-value');
        }

        // Set a non-BeyondWords meta key that should NOT be removed
        $customKey = 'my_custom_meta_key';
        update_post_meta($postId, $customKey, 'custom_value');

        // Verify all keys are set
        foreach ($beyondwordsKeys as $key) {
            $this->assertNotEmpty(
                get_post_meta($postId, $key, true),
                "Expected $key to be set before removal"
            );
        }
        $this->assertEquals('custom_value', get_post_meta($postId, $customKey, true));

        // Remove all BeyondWords metadata
        Meta::remove_all_beyondwords_metadata($postId);

        // Verify all BeyondWords keys are removed
        foreach ($beyondwordsKeys as $key) {
            $this->assertEmpty(
                get_post_meta($postId, $key, true),
                "Expected $key to be removed"
            );
        }

        // Verify non-BeyondWords key is still present
        $this->assertEquals(
            'custom_value',
            get_post_meta($postId, $customKey, true),
            'Expected custom meta key to remain after removal'
        );

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function get_body_voice_id()
    {
        $postId = self::factory()->post->create();

        $this->assertFalse(Meta::get_body_voice_id($postId));

        update_post_meta($postId, 'beyondwords_body_voice_id', '123');
        $this->assertSame('123', Meta::get_body_voice_id($postId));

        wp_delete_post($postId, true);
    }

}
