<?php

declare(strict_types=1);

use Beyondwords\Wordpress\Core\Response;

class ResponseTest extends TestCase
{
    /**
     * @var \Beyondwords\Wordpress\Core\Response;
     */
    private $_instance;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $this->_instance = new Response([
            'headers' => [],
            'body' => '',
            'response' => '',
            'cookies' => [],
            'filename' => '',
        ]);
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
    public function headers()
    {
        $this->assertSame([], $this->_instance->getHeaders());

        $headers = [
            'Content-Type' => 'application/json',
            'X-Foo' => 'bar',
        ];

        $this->_instance->setHeaders($headers);

        $this->assertSame($headers, $this->_instance->getHeaders());
    }

    /**
     * @test
     */
    public function body()
    {
        $this->assertSame('', $this->_instance->getBody());

        $body = 'foo';

        $this->_instance->setBody($body);

        $this->assertSame($body, $this->_instance->getBody());
    }

    /**
     * @test
     */
    public function response()
    {
        $this->assertSame('', $this->_instance->getResponse());

        $response = 'bar';

        $this->_instance->setResponse($response);

        $this->assertSame($response, $this->_instance->getResponse());
    }

    /**
     * @test
     */
    public function cookies()
    {
        $this->assertSame([], $this->_instance->getCookies());

        $cookies = [
            'unknown' => 'format',
        ];

        $this->_instance->setCookies($cookies);

        $this->assertSame($cookies, $this->_instance->getCookies());
    }

    /**
     * @test
     */
    public function filename()
    {
        $this->assertSame('', $this->_instance->getFilename());

        $filename = '/foo/bar/baz';

        $this->_instance->setFilename($filename);

        $this->assertSame($filename, $this->_instance->getFilename());
    }
}
