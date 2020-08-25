<?php

namespace TorrentFinder\Provider\ResultSet;

use TorrentFinder\Exception\Ensure;
use TorrentFinder\VideoSettings\Resolution;

class TorrentData
{
    private $title;
    private $magnetURI;
    private $seeds;
    private $format;

    public function __construct(string $title, string $magnetURI, int $seeds, Resolution $format)
    {
        Ensure::notEmpty($title, 'Title cannot be null');
        Ensure::notEmpty($magnetURI, 'Magnet cannot be null');
        $this->title = $title;
        $this->magnetURI = $magnetURI;
        $this->seeds = $seeds;
        $this->format = $format;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMagnetURI(): string
    {
        return $this->magnetURI;
    }

    public function getSeeds(): int
    {
        return $this->seeds;
    }

    public function getFormat(): Resolution
    {
        return $this->format;
    }

    public function isEmpty(): bool
    {
        if (empty($this->title) && empty($this->url) && empty($this->magnetURI) && $this->seeds === 0) {
            return true;
        }

        return false;
    }

    public function hasMoreSeedsThan(TorrentData $torrentData): bool
    {
        return $this->seeds > $torrentData->getSeeds();
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'magnetURI' => $this->magnetURI,
            'seeds' => $this->seeds,
            'format' => $this->format->getValue(),
        ];
    }
}
