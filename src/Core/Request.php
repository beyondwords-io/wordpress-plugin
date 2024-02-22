<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

class Request
{
    public const AUTH_HEADER_NAME = 'X-Api-Key';
    public const CONTENT_TYPE_HEADER_NAME = 'Content-Type';
    public const CONTENT_TYPE_HEADER_VALUE = 'application/json';

    private $method;
    private $url;
    private $body;
    private $headers;

    /**
     * @param string $method
     * @param string $url
     * @param mixed $body
     */
    public function __construct($method, $url, $body = null, $headers = null)
    {
        $this->setMethod($method);
        $this->setUrl($url);
        $this->setBody($body);

        if ($headers === null) {
            $headers = $this->getDefaultHeaders();
        }

        $this->setHeaders($headers);
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get default headers (Authorization & Content-Type).
     *
     * @return mixed
     */
    public function getDefaultHeaders()
    {
        return [
            self::AUTH_HEADER_NAME => get_option('beyondwords_api_key'),
            self::CONTENT_TYPE_HEADER_NAME => self::CONTENT_TYPE_HEADER_VALUE,
        ];
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }
}
