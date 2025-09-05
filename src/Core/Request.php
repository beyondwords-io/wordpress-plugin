<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

class Request
{
    public const AUTH_HEADER_NAME = 'X-Api-Key';

    public const CONTENT_TYPE_HEADER_NAME = 'Content-Type';

    public const CONTENT_TYPE_HEADER_VALUE = 'application/json';

    private string $method = '';

    private string $url = '';

    private string $body = '';

    private array $headers = [];

    /**
     * Request constructor.
     *
     * @param string $method
     * @param string $url
     * @param mixed $body
     * @param array $headers
     *
     * @return void
     */
    public function __construct(
        string $method,
        string $url,
        string $body = '',
        array $headers = []
    ) {
        $this->setMethod($method);
        $this->setUrl($url);
        $this->setBody($body);

        // Default headers.
        $this->addHeaders([
            self::AUTH_HEADER_NAME => get_option('beyondwords_api_key'),
            self::CONTENT_TYPE_HEADER_NAME => self::CONTENT_TYPE_HEADER_VALUE,
        ]);

        // Custom headers.
        $this->addHeaders($headers);
    }

    /**
     * Get the HTTP method for the request.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set the HTTP method for the request.
     *
     * @param string $method
     *
     * @return void
     */
    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * Get the URL for the request.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the URL for the request.
     *
     * @param string $url
     *
     * @return void
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get the body for the request.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the body for the request.
     *
     * @param string $body
     *
     * @return void
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Get the headers for the request.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the headers to the request.
     *
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Add extra headers to the request.
     *
     * @since 6.0.0 Introduced.
     *
     * @param array $headers
     *
     * @return void
     */
    public function addHeaders(array $headers): void
    {
        $this->setHeaders(array_merge(
            (array) $this->getHeaders(),
            $headers
        ));
    }
}
