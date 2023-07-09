<?php

namespace TorrentFinder\Provider\Jackett;

class JackettIndexerList
{
    private array $indexerList;

    public function __construct(array $indexerList)
    {
        $this->indexerList = $indexerList;
    }

    public function getIndexerList(): array
    {
        return $this->indexerList;
    }


}