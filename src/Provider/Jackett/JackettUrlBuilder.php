<?php

namespace TorrentFinder\Provider\Jackett;

class JackettUrlBuilder
{
    private string $host;
    private string $port;
    private string $apikey;

    public function __construct(string $host, string $port, string $apikey)
    {
        $this->host = $host;
        $this->port = $port;
        $this->apikey = $apikey;
    }

    public function buildAllGenericUrl(string $query): string
    {
        return sprintf(
            "http://%s:%s/api/v2.0/indexers/all/results/torznab/api?apikey=%s&t=search&cat=&q=%s",
            $this->host,
            $this->port,
            $this->apikey,
            $query
        );
    }

    public function buildSearchByIndexerUrl(string $indexer, string $query): string
    {
        return sprintf(
            "http://%s:%s/api/v2.0/indexers/%s/results/torznab/api?apikey=%s&t=search&cat=&q=%s",
            $this->host,
            $this->port,
            $indexer,
            $this->apikey,
            $query
        );
    }
}
