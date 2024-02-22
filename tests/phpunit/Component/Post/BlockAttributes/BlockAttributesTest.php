<?php

use Beyondwords\Wordpress\Component\Post\BlockAttributes\BlockAttributes;
use Beyondwords\Wordpress\Component\Settings\PlayerUI\PlayerUI;

class BlockAttributesTest extends WP_UnitTestCase
{
    /**
     * @var \Beyondwords\Wordpress\Component\Post\BlockAttributes\BlockAttributes
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new BlockAttributes();
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
        $blockAttributes = new BlockAttributes();
        $blockAttributes->init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('register_block_type_args', array($blockAttributes, 'registerAudioAttribute')));
        $this->assertEquals(10, has_action('register_block_type_args', array($blockAttributes, 'registerMarkerAttribute')));
        $this->assertEquals(10, has_action('render_block', array($blockAttributes, 'renderBlock')));
    }

    /**
     * @test
     * @dataProvider registerAudioAttributeProvider
     */
    public function registerAudioAttribute($args, $expect)
    {
        $blockAttributes = new BlockAttributes();

        $this->assertSame($expect, $blockAttributes->registerAudioAttribute($args));
    }

    public function registerAudioAttributeProvider($args) {
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
     * @dataProvider registerMarkerAttributeProvider
     */
    public function registerMarkerAttribute($args, $expect)
    {
        $this->assertSame($expect, $this->_instance->registerMarkerAttribute($args));
    }

    public function registerMarkerAttributeProvider($args) {
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
    public function renderBlockWithUiDisabled()
    {
        update_option('beyondwords_player_ui', PlayerUI::DISABLED);

        $this->assertSame(
            '<p>Test</p>',
            $this->_instance->renderBlock('<p>Test</p>', [
                'attrs' => [
                    'beyondwordsMarker' => 'foo',
                ]
            ])
        );

        delete_option('beyondwords_player_ui');
    }

    /**
     * @test
     */
    public function renderBlockWithoutCustomFields()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'BlockAttributesTest::renderBlockWithoutCustomFields',
            'post_type' => 'post',
        ]);

        $this->go_to(get_permalink($postId));
        global $post;
        setup_postdata($post);

        $this->assertSame(
            '<p>Test</p>',
            $this->_instance->renderBlock('<p>Test</p>', [
                'attrs' => [
                    'beyondwordsMarker' => 'foo',
                ]
            ])
        );

        wp_reset_postdata();

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function renderBlockWithoutMarkerAttribute()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'BlockAttributesTest::renderBlockWithoutMarkerAttribute',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->go_to(get_permalink($postId));
        global $post;
        setup_postdata($post);

        $this->assertSame(
            '<p>Test</p>',
            $this->_instance->renderBlock('<p>Test</p>', [
                'attrs' => [
                    'foo' => 'bar',
                ]
            ])
        );

        wp_reset_postdata();

        wp_delete_post($postId, true);
    }

    /**
     * @test
     */
    public function renderBlockWithMarkerAttribute()
    {
        $postId = self::factory()->post->create([
            'post_title' => 'BlockAttributesTest::renderBlockWithMarkerAttribute',
            'meta_input' => [
                'beyondwords_project_id' => BEYONDWORDS_TESTS_PROJECT_ID,
                'beyondwords_content_id' => BEYONDWORDS_TESTS_CONTENT_ID,
            ],
        ]);

        $this->go_to(get_permalink($postId));
        global $post;
        setup_postdata($post);

        $this->assertSame(
            '<p data-beyondwords-marker="baz">Test</p>',
            $this->_instance->renderBlock('<p>Test</p>', [
                'attrs' => [
                    'beyondwordsMarker' => 'baz',
                ]
            ])
        );

        wp_reset_postdata();

        wp_delete_post($postId, true);
    }
}
