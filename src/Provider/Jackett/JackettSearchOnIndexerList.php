<?php

namespace TorrentFinder\Provider\Jackett;

use TorrentFinder\Search\SearchQueryBuilder;

class JackettSearchOnIndexerList
{
    private JackettUrlBuilder $url;
    private JackettIndexerList $list;

    public function __construct(JackettUrlBuilder $url, JackettIndexerList $list)
    {
        $this->url = $url;
        $this->list = $list;
    }

    public function searchAll(SearchQueryBuilder $keywords)
    {
        $results = [];
        foreach ($this->list->getIndexerList() as $indexer) {
            try {
                $generic = new JackettGenericSearch($this->url, $indexer);
                $results = array_merge($results, $generic->search($keywords));
            } catch (\Exception $e) {
                printf("%s\n", $e->getMessage());
            }
        }

        return $results;
    }
}