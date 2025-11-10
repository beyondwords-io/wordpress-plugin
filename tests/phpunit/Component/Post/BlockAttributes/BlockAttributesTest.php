<?php

use Beyondwords\Wordpress\Component\Post\BlockAttributes\BlockAttributes;

class BlockAttributesTest extends TestCase
{
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
     * @test
     */
    public function init()
    {
        BlockAttributes::init();

        do_action('wp_loaded');

        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'registerAudioAttribute')));
        $this->assertEquals(10, has_action('register_block_type_args', array(BlockAttributes::class, 'registerMarkerAttribute')));
    }

    /**
     * @test
     * @dataProvider registerAudioAttributeProvider
     */
    public function registerAudioAttribute($args, $expect)
    {
        $this->assertSame($expect, BlockAttributes::registerAudioAttribute($args));
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
        $this->assertSame($expect, BlockAttributes::registerMarkerAttribute($args));
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
}
