<?php

use BeyondWords\Editor\Components\BlockAttributes;

class BlockAttributesTest extends TestCase
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
    public function init()
    {
        BlockAttributes::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'register_audio_attribute')));
        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'register_marker_attribute')));
        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'register_language_attribute')));
        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'register_voice_attribute')));
    }

    /**
     * @test
     *
     * The data attributes are only added around the API body build, so the
     * front end never renders them.
     */
    public function init_does_not_register_the_render_block_filter()
    {
        BlockAttributes::init();

        do_action('wp_loaded');

        $this->assertFalse(has_action('render_block', array(BlockAttributes::class, 'add_segment_attributes')));
    }

    /**
     * @test
     * @dataProvider register_audio_attribute_provider
     */
    public function register_audio_attribute($args, $expect)
    {
        $this->assertSame($expect, BlockAttributes::register_audio_attribute($args));
    }

    public function register_audio_attribute_provider($args) {
        $newAttribute = [
            'beyondwordsAudio' => [
                'type' => 'boolean',
                'default' => true,
            ]
        ];

        return [
            'No args' => [
                'args'   => null,
                'expect' => [
                    'attributes' => $newAttribute
                ],
            ],
            'Empty args' => [
                'args'   => [],
                'expect' => [
                    'attributes' => $newAttribute
                ],
            ],
            'Existing other args' => [
                'args'   => [
                    'foo' => 'bar',
                ],
                'expect' => [
                    'foo' => 'bar',
                    'attributes' => $newAttribute,
                ],
            ],
            'Existing other attributes' => [
                'args'   => [
                    'attributes' => [
                        'bar' => 'baz',
                    ],
                ],
                'expect' => [
                    'attributes' => array_merge(
                        ['bar' => 'baz'],
                        $newAttribute,
                    )
                ],
            ],
            'Existing same attribute' => [
                'args' => [
                    'attributes' => [
                        'beyondwordsAudio' => [
                            'type' => 'number',
                            'default' => 1,
                        ],
                    ],
                ],
                'expect' => [
                    'attributes' => [
                        'beyondwordsAudio' => [
                            'type' => 'number',
                            'default' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider register_marker_attribute_provider
     */
    public function register_marker_attribute($args, $expect)
    {
        $this->assertSame($expect, BlockAttributes::register_marker_attribute($args));
    }

    public function register_marker_attribute_provider($args) {
        $newAttribute = [
            'beyondwordsMarker' => [
                'type' => 'string',
                'default' => '',
            ]
        ];

        return [
            'No args' => [
                'args'   => null,
                'expect' => [
                    'attributes' => $newAttribute
                ],
            ],
            'Empty args' => [
                'args'   => [],
                'expect' => [
                    'attributes' => $newAttribute
                ],
            ],
            'Existing other args' => [
                'args'   => [
                    'foo' => 'bar',
                ],
                'expect' => [
                    'foo' => 'bar',
                    'attributes' => $newAttribute,
                ],
            ],
            'Existing other attributes' => [
                'args'   => [
                    'attributes' => [
                        'bar' => 'baz',
                    ],
                ],
                'expect' => [
                    'attributes' => array_merge(
                        ['bar' => 'baz'],
                        $newAttribute,
                    )
                ],
            ],
            'Existing same attribute' => [
                'args' => [
                    'attributes' => [
                        'beyondwordsMarker' => [
                            'type' => 'number',
                            'default' => 1,
                        ],
                    ],
                ],
                'expect' => [
                    'attributes' => [
                        'beyondwordsMarker' => [
                            'type' => 'number',
                            'default' => 1,
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function register_language_attribute()
    {
        $expect = [
            'beyondwordsLanguageCode' => [
                'type' => 'string',
                'default' => '',
            ],
        ];

        $this->assertSame(['attributes' => $expect], BlockAttributes::register_language_attribute([]));
    }

    /**
     * @test
     */
    public function register_language_attribute_keeps_an_existing_definition()
    {
        $args = [
            'attributes' => [
                'beyondwordsLanguageCode' => [
                    'type' => 'number',
                    'default' => 1,
                ],
            ],
        ];

        $this->assertSame($args, BlockAttributes::register_language_attribute($args));
    }

    /**
     * @test
     */
    public function register_voice_attribute()
    {
        $expect = [
            'beyondwordsVoiceId' => [
                'type' => 'string',
                'default' => '',
            ],
        ];

        $this->assertSame(['attributes' => $expect], BlockAttributes::register_voice_attribute([]));
    }

    /**
     * @test
     */
    public function register_voice_attribute_keeps_an_existing_definition()
    {
        $args = [
            'attributes' => [
                'beyondwordsVoiceId' => [
                    'type' => 'number',
                    'default' => 1,
                ],
            ],
        ];

        $this->assertSame($args, BlockAttributes::register_voice_attribute($args));
    }

    /**
     * @test
     */
    public function register_voice_attribute_keeps_other_args()
    {
        $args = [
            'foo' => 'bar',
            'attributes' => [
                'baz' => 'qux',
            ],
        ];

        $expect = [
            'foo' => 'bar',
            'attributes' => [
                'baz' => 'qux',
                'beyondwordsVoiceId' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
        ];

        $this->assertSame($expect, BlockAttributes::register_voice_attribute($args));
    }

    /**
     * @test
     * @dataProvider add_segment_attributes_provider
     */
    public function add_segment_attributes($attrs, $content, $expect)
    {
        $block = null === $attrs ? ['blockName' => 'core/paragraph'] : ['blockName' => 'core/paragraph', 'attrs' => $attrs];

        $this->assertSame($expect, BlockAttributes::add_segment_attributes($content, $block));
    }

    public function add_segment_attributes_provider()
    {
        return [
            'No attrs key' => [
                'attrs'   => null,
                'content' => '<p>Hello world.</p>',
                'expect'  => '<p>Hello world.</p>',
            ],
            'No overrides' => [
                'attrs'   => ['beyondwordsAudio' => true],
                'content' => '<p>Hello world.</p>',
                'expect'  => '<p>Hello world.</p>',
            ],
            'Empty overrides' => [
                'attrs'   => ['beyondwordsLanguageCode' => '', 'beyondwordsVoiceId' => ''],
                'content' => '<p>Hello world.</p>',
                'expect'  => '<p>Hello world.</p>',
            ],
            'Language and voice' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'fr_FR', 'beyondwordsVoiceId' => '784'],
                'content' => '<p>Bonjour tout le monde.</p>',
                'expect'  => '<p data-beyondwords-language="fr_FR" data-beyondwords-voice-id="784">Bonjour tout le monde.</p>',
            ],
            'Language only' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'fr_FR'],
                'content' => '<p>Bonjour tout le monde.</p>',
                'expect'  => '<p data-beyondwords-language="fr_FR">Bonjour tout le monde.</p>',
            ],
            'Voice only' => [
                'attrs'   => ['beyondwordsVoiceId' => '784'],
                'content' => '<p>Hello world.</p>',
                'expect'  => '<p data-beyondwords-voice-id="784">Hello world.</p>',
            ],
            'Existing attributes are preserved' => [
                'attrs'   => ['beyondwordsVoiceId' => '784'],
                'content' => '<p class="has-text-align-center">Hello world.</p>',
                'expect'  => '<p data-beyondwords-voice-id="784" class="has-text-align-center">Hello world.</p>',
            ],
            'Only the outermost tag is given the attributes' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'fr_FR'],
                'content' => '<blockquote class="wp-block-quote"><p>Bonjour.</p><p>Au revoir.</p></blockquote>',
                'expect'  => '<blockquote data-beyondwords-language="fr_FR" class="wp-block-quote"><p>Bonjour.</p><p>Au revoir.</p></blockquote>',
            ],
            'Leading whitespace is skipped' => [
                'attrs'   => ['beyondwordsVoiceId' => '784'],
                'content' => "\n<p>Hello world.</p>\n",
                'expect'  => "\n<p data-beyondwords-voice-id=\"784\">Hello world.</p>\n",
            ],
            'Values are trimmed' => [
                'attrs'   => ['beyondwordsLanguageCode' => ' fr_FR ', 'beyondwordsVoiceId' => ' 784 '],
                'content' => '<p>Bonjour tout le monde.</p>',
                'expect'  => '<p data-beyondwords-language="fr_FR" data-beyondwords-voice-id="784">Bonjour tout le monde.</p>',
            ],
            'Non-scalar values are ignored' => [
                'attrs'   => ['beyondwordsLanguageCode' => ['fr_FR'], 'beyondwordsVoiceId' => ['784']],
                'content' => '<p>Hello world.</p>',
                'expect'  => '<p>Hello world.</p>',
            ],
            'Tagless content is left alone' => [
                'attrs'   => ['beyondwordsVoiceId' => '784'],
                'content' => 'Hello world.',
                'expect'  => 'Hello world.',
            ],
            'Empty content is left alone' => [
                'attrs'   => ['beyondwordsVoiceId' => '784'],
                'content' => '',
                'expect'  => '',
            ],
            'Language codes with a script or dialect subtag are accepted' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'sr_Latn_RS'],
                'content' => '<p>Здраво.</p>',
                'expect'  => '<p data-beyondwords-language="sr_Latn_RS">Здраво.</p>',
            ],
            'A malformed language code is dropped' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'fr-FR', 'beyondwordsVoiceId' => '784'],
                'content' => '<p>Bonjour.</p>',
                'expect'  => '<p data-beyondwords-voice-id="784">Bonjour.</p>',
            ],
            'A non-numeric voice id is dropped' => [
                'attrs'   => ['beyondwordsLanguageCode' => 'fr_FR', 'beyondwordsVoiceId' => 'patrick'],
                'content' => '<p>Bonjour.</p>',
                'expect'  => '<p data-beyondwords-language="fr_FR">Bonjour.</p>',
            ],
            'A zero or negative voice id is dropped' => [
                'attrs'   => ['beyondwordsVoiceId' => '0'],
                'content' => '<p>Hello.</p>',
                'expect'  => '<p>Hello.</p>',
            ],
            'A voice id is normalised to its integer' => [
                'attrs'   => ['beyondwordsVoiceId' => '0784'],
                'content' => '<p>Hello.</p>',
                'expect'  => '<p data-beyondwords-voice-id="784">Hello.</p>',
            ],
        ];
    }

    /**
     * @test
     *
     * A core/audio block always carries its own audio — there's no toggle for
     * it, unlike language/voice which are opt-in per block.
     */
    public function add_segment_attributes_marks_the_audio_tag_on_a_core_audio_block()
    {
        $block = ['blockName' => 'core/audio'];

        $this->assertSame(
            '<figure class="wp-block-audio"><audio data-beyondwords-audio="true" controls src="cat.mp3"></audio></figure>',
            BlockAttributes::add_segment_attributes(
                '<figure class="wp-block-audio"><audio controls src="cat.mp3"></audio></figure>',
                $block
            )
        );
    }

    /**
     * @test
     *
     * The marker goes on <audio> itself, not the <figure> wrapping it.
     */
    public function add_segment_attributes_does_not_mark_the_figure_wrapper()
    {
        $block = ['blockName' => 'core/audio'];

        $result = BlockAttributes::add_segment_attributes(
            '<figure class="wp-block-audio"><audio controls src="cat.mp3"></audio></figure>',
            $block
        );

        $this->assertStringStartsWith('<figure class="wp-block-audio">', $result);
    }

    /**
     * @test
     */
    public function add_segment_attributes_leaves_a_core_audio_block_with_no_audio_tag_alone()
    {
        $block = ['blockName' => 'core/audio'];

        $this->assertSame(
            '<figure class="wp-block-audio"></figure>',
            BlockAttributes::add_segment_attributes('<figure class="wp-block-audio"></figure>', $block)
        );
    }

    /**
     * @test
     *
     * Only core/audio is auto-marked — every other block needs an explicit
     * language/voice override to get any data attribute at all.
     */
    public function add_segment_attributes_does_not_mark_other_blocks_with_an_audio_tag()
    {
        $block = ['blockName' => 'core/html'];

        $this->assertSame(
            '<audio controls src="cat.mp3"></audio>',
            BlockAttributes::add_segment_attributes('<audio controls src="cat.mp3"></audio>', $block)
        );
    }

    /**
     * @test
     *
     * The audio marker and a language/voice override are independent: a
     * core/audio block can carry both at once, on different tags.
     */
    public function add_segment_attributes_combines_the_audio_marker_with_language_and_voice()
    {
        $block = [
            'blockName' => 'core/audio',
            'attrs'     => [
                'beyondwordsLanguageCode' => 'fr_FR',
                'beyondwordsVoiceId'      => '784',
            ],
        ];

        $this->assertSame(
            '<figure data-beyondwords-language="fr_FR" data-beyondwords-voice-id="784" class="wp-block-audio"><audio data-beyondwords-audio="true" controls src="cat.mp3"></audio></figure>',
            BlockAttributes::add_segment_attributes(
                '<figure class="wp-block-audio"><audio controls src="cat.mp3"></audio></figure>',
                $block
            )
        );
    }

    /**
     * @test
     *
     * The comment delimiter is editor-writable, so neither value is trusted:
     * anything not shaped like the API's own values is dropped, not escaped.
     */
    public function add_segment_attributes_rejects_values_that_are_not_api_shaped()
    {
        $block = [
            'blockName' => 'core/paragraph',
            'attrs'     => [
                'beyondwordsLanguageCode' => 'en_GB" onload="alert(1)',
                'beyondwordsVoiceId'      => '"><script>alert(1)</script>',
            ],
        ];

        $this->assertSame(
            '<p>Hello world.</p>',
            BlockAttributes::add_segment_attributes('<p>Hello world.</p>', $block)
        );
    }
}
