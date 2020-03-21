<?php

namespace App\Utils;

class Url
{
    private $scheme;
    private $host;
    private $path;
    private $query;

    public static function fromString(string $url): self
    {
        $urlParsed = parse_url($url);

        return new static($urlParsed['scheme'], $urlParsed['host'], $urlParsed['path'], $urlParsed['query'] ?? '');
    }

    public function __construct(string $scheme, string $host, string $path, string $query)
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->path = $path;
        $this->query = $query;
    }

    public function getBaseUrl(): string
    {
        return sprintf('%s://%s', $this->scheme, $this->host);
    }

    public function getUrl(): string
    {
        $url = $this->getBaseUrl() . $this->path;

        if (empty($this->query)) {

            return $url;
        }

        return sprintf('%s?%s', $url, $this->query);
    }
}