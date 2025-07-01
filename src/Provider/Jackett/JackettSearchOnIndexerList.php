<?php

namespace TorrentFinder\Provider\Jackett;

use TorrentFinder\Search\SearchQueryBuilder;

class JackettSearchOnIndexerList
{
    private JackettUrlBuilder $url;

    public function __construct(JackettUrlBuilder $url)
    {
        $this->url = $url;
    }

    public function searchAll(SearchQueryBuilder $keywords, array $options = [])
    {
        $results = [];
        try {
            $generic = new JackettGenericSearch($this->url->buildAllGenericUrl($keywords->urlize()));
            $results = $generic->search($options);
        } catch (\Exception $e) {
            printf("%s\n", $e->getMessage());
        }

        return $results;
    }

    public function searchByIndexer(string $indexer, SearchQueryBuilder $keywords, array $options = [])
    {
        $results = [];
        try {
            $generic = new JackettGenericSearch(
                $this->url->buildSearchByIndexerUrl($indexer, $keywords->urlize())
            );
            $results = $generic->search($options);
        } catch (\Exception $e) {
            printf("%s\n", $e->getMessage());
        }

        return $results;
    }
}
