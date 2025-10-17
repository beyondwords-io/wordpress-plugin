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

    public function __construct(array $response = [])
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

    public function getHeaders(): mixed
    {
        return $this->headers;
    }

    public function setHeaders(mixed $headers): void
    {
        $this->headers = $headers;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }

    public function getResponse(): mixed
    {
        return $this->response;
    }

    public function setResponse(mixed $response): void
    {
        $this->response = $response;
    }

    public function getCookies(): mixed
    {
        return $this->cookies;
    }

    public function setCookies(mixed $cookies): void
    {
        $this->cookies = $cookies;
    }

    public function getFilename(): mixed
    {
        return $this->filename;
    }

    public function setFilename(mixed $filename): void
    {
        $this->filename = $filename;
    }
}
