<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Compatibility\WPGraphQL\WPGraphQL;

class WPGraphQLTest extends WP_UnitTestCase
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
        WPGraphQL::init();

        // Actions
        $this->assertEquals(10, has_action('graphql_register_types', array(WPGraphQL::class, 'graphqlRegisterTypes')));
    }
}
