<?php

namespace TorrentFinder\Provider\Jackett;

class JackettIndexerList
{
    private array $indexerList;

    public function __construct(string $indexerList)
    {
        $this->indexerList = explode(',', $indexerList);
    }

    public function getIndexerList(): array
    {
        return $this->indexerList;
    }
}
