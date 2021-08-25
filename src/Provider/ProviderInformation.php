<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Utils\Url;

class ProviderInformation
{
    private $name;
    private $searchUrl;

    public function __construct(string $name, Url $searchUrl)
    {
        $this->name = $name;
        $this->searchUrl = $searchUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSearchUrl(): Url
    {
        return $this->searchUrl;
    }
}
