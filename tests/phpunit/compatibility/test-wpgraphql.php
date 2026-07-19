<?php

declare(strict_types=1);

use BeyondWords\Compatibility\WPGraphQL;

class WPGraphQLTest extends TestCase
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
        WPGraphQL::init();

        $this->assertEquals(10, has_action('graphql_register_types', array(WPGraphQL::class, 'graphql_register_types')));
    }
}
