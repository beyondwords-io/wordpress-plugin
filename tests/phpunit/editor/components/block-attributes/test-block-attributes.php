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
        ];
    }

    /**
     * @test
     *
     * A voice id is untrusted input: it must never break out of the attribute.
     */
    public function add_segment_attributes_escapes_the_values()
    {
        $block = [
            'blockName' => 'core/paragraph',
            'attrs'     => ['beyondwordsVoiceId' => '"><script>alert(1)</script>'],
        ];

        $actual = BlockAttributes::add_segment_attributes('<p>Hello world.</p>', $block);

        $this->assertStringNotContainsString('<script>', $actual);
    }
}
