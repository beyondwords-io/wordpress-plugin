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
     * @param mixed $body
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

        // Add API key header to all requests.
        $this->addHeaders([
            self::AUTH_HEADER_NAME => get_option('beyondwords_api_key'),
        ]);

        // Add Content-Type header for non-GET requests.
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            // Default headers.
            $this->addHeaders([
                self::CONTENT_TYPE_HEADER_NAME => self::CONTENT_TYPE_HEADER_VALUE,
            ]);
        }

        // Add custom headers.
        $this->addHeaders($headers);
    }

    /**
     * Get the HTTP method for the request.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set the HTTP method for the request.
     *
     *
     */
    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * Get the URL for the request.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the URL for the request.
     *
     *
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get the body for the request.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the body for the request.
     *
     *
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Get the headers for the request.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the headers to the request.
     *
     *
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
     *
     */
    public function addHeaders(array $headers): void
    {
        $this->setHeaders(array_merge(
            $this->getHeaders(),
            $headers
        ));
    }
}
