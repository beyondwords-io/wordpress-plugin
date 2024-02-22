<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Core;

class Response
{
    private $headers;
    private $body;
    private $response;
    private $cookies;
    private $filename;

    /**
     * @param array $response
     */
    public function __construct($response = array())
    {
        if (array_key_exists('headers', $response)) {
            $this->headers = $response['headers'];
        }

        if (array_key_exists('body', $response)) {
            $this->body = $response['body'];
        }

        if (array_key_exists('response', $response)) {
            $this->response = $response['response'];
        }

        if (array_key_exists('cookies', $response)) {
            $this->cookies = $response['cookies'];
        }

        if (array_key_exists('filename', $response)) {
            $this->filename = $response['filename'];
        }
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
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param mixed $cookies
     */
    public function setCookies($cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param mixed $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
}
