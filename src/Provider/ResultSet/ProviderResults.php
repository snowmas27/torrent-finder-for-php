<?php

namespace TorrentFinder\Provider\ResultSet;

use Doctrine\Common\Collections\ArrayCollection;

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
        $key = self::deduplicationKey($providerResult->getTorrentMetaData());
        $exists = $this->results->exists(function (int $index, ProviderResult $item) use ($key) {
            return $key === self::deduplicationKey($item->getTorrentMetaData());
        });

        if ($exists) {
            return;
        }

        $this->results->add($providerResult);
    }

    /**
     * The same torrent is exposed with different trackers / display names depending on the
     * site, so magnets are compared on their info hash. Torrents without a magnet are
     * compared on their .torrent URL.
     */
    private static function deduplicationKey(TorrentData $torrentData): string
    {
        $magnet = $torrentData->getMagnetURI();
        if (null === $magnet) {
            return 'url:' . $torrentData->getTorrentUrl();
        }

        if (preg_match('/urn:btih:([a-z0-9]+)/i', $magnet, $match)) {
            return 'btih:' . strtolower($match[1]);
        }

        return 'magnet:' . $magnet;
    }

    public function getResults(): array
    {
        return $this->results->toArray();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->results->toArray());
    }
}
