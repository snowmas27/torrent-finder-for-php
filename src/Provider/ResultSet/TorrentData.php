<?php

namespace TorrentFinder\Provider\ResultSet;

use TorrentFinder\Exception\Ensure;
use TorrentFinder\VideoSettings\Resolution;

class TorrentData
{
    private string $title;
    private ?string $magnetURI;
    private ?string $torrentUrl;
    private int $seeds;
    private Resolution $format;

    public static function fromArray(array $data): self
    {
        if (isset($data['magnetURI']) && !empty($data['magnetURI'])) {
            return self::fromMagnetURI(
                $data['title'],
                $data['magnetURI'],
                $data['seeds'],
                new Resolution($data['format'])
            );
        }

        if (isset($data['torrentUrl']) && !empty($data['torrentUrl'])) {
            return self::fromTorrentUrl(
                $data['title'],
                $data['torrentUrl'],
                $data['seeds'],
                new Resolution($data['format'])
            );
        }

        throw new \InvalidArgumentException('Either magnetURI or torrentUrl must be provided');
    }

    public static function fromMagnetURI(string $title, string $magnetURI, int $seeds, Resolution $format): self
    {
        return new self($title, $magnetURI, null, $seeds, $format);
    }

    public static function fromTorrentUrl(string $title, string $torrentUrl, int $seeds, Resolution $format): self
    {
        return new self($title, null, $torrentUrl, $seeds, $format);
    }

    public function __construct(string $title, ?string $magnetURI, ?string $torrentUrl, int $seeds, Resolution $format)
    {
        Ensure::notEmpty($title, 'Title cannot be empty');

        // Exactly one of magnetURI or torrentUrl must be provided
        if (empty($magnetURI) && empty($torrentUrl)) {
            throw new \InvalidArgumentException('Either magnetURI or torrentUrl must be provided');
        }

        if (!empty($magnetURI) && !empty($torrentUrl)) {
            throw new \InvalidArgumentException('Cannot provide both magnetURI and torrentUrl');
        }

        $this->title = $title;
        $this->magnetURI = $magnetURI;
        $this->torrentUrl = $torrentUrl;
        $this->seeds = $seeds;
        $this->format = $format;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMagnetURI(): ?string
    {
        return $this->magnetURI;
    }

    public function getTorrentUrl(): ?string
    {
        return $this->torrentUrl;
    }

    public function hasUrl(): bool
    {
        return !empty($this->torrentUrl);
    }

    public function hasMagnetURI(): bool
    {
        return !empty($this->magnetURI);
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
        if (empty($this->title) && empty($this->torrentUrl) && empty($this->magnetURI) && $this->seeds === 0) {
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
        $result = [
            'title' => $this->title,
            'seeds' => $this->seeds,
            'format' => $this->format->getValue(),
        ];

        if ($this->magnetURI !== null) {
            $result['magnetURI'] = $this->magnetURI;
        }

        if ($this->torrentUrl !== null) {
            $result['torrentUrl'] = $this->torrentUrl;
        }

        return $result;
    }
}
