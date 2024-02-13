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

    public function searchAll(SearchQueryBuilder $keywords)
    {
        $results = [];
        try {
            $generic = new JackettGenericSearch($this->url);
            $results = $generic->search($keywords);
        } catch (\Exception $e) {
            printf("%s\n", $e->getMessage());
        }

        return $results;
    }
}
