<?php

namespace TorrentFinder\Search;

use TorrentFinder\VideoSettings\Resolution;

class SearchQueryBuilder
{
    private $query;
    private $resolution;
    private $searchKeywords;
    private $customKeywords;

    public function __construct(SearchQuery $query, Resolution $resolution)
    {
        $this->query = $query->getQuery();
        $this->resolution = $resolution->getValue();
        $this->searchKeywords = trim(sprintf('%s %s', $this->query, $resolution->getValueForSearch()));
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFormat(): Resolution
    {
        return new Resolution($this->resolution);
    }

    public function getSearchKeywords(): string
    {
        $query = $this->searchKeywords;

        if (null !== $this->customKeywords) {
            $query = sprintf('%s %s', $query, $this->customKeywords);
        }

        return $query;
    }

    public function addKeywords(string $keywords): void
    {
        $this->customKeywords = $keywords;
    }

    public function urlize(string $char = '+'): string
    {
        return str_replace(' ', $char, $this->getSearchKeywords());
    }

    public function urlEncode(): string
    {
        return urlencode($this->getSearchKeywords());
    }
    public function rawUrlEncode(): string
    {
        return rawurlencode($this->getSearchKeywords());
    }
}
