<?php

namespace TorrentFinder\Provider\ResultSet;

use Doctrine\Common\Collections\ArrayCollection;
use Traversable;

class ProviderResults implements \IteratorAggregate
{
    /** @var ArrayCollection */
    private $results;

    public function __construct()
    {
        $this->results = new ArrayCollection();
    }

    public function add(ProviderResult $providerResult): void
    {
        $exists = $this->results->exists(function(int $key, ProviderResult $item) use ($providerResult) {
            return $providerResult->getTorrentMetaData()->getMagnetURI() === $item->getTorrentMetaData()->getMagnetURI();
        });

        if ($exists) {
            return;
        }

        $this->results->add($providerResult);
    }

    public function getResults(): array
    {
        return $this->results->toArray();
    }

    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->results->toArray());
    }
}
