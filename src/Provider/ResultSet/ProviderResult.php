<?php

namespace App\Provider\ResultSet;

use App\VideoSettings\Size;

class ProviderResult
{
    private $torrentMetaData;
    private $size;
    private $provider;

    public function __construct(string $provider, TorrentData $torrentMetaData, Size $size)
    {
        $this->torrentMetaData = $torrentMetaData;
        $this->size = $size;
        $this->provider = $provider;
    }

    public function getTorrentMetaData(): TorrentData
    {
        return $this->torrentMetaData;
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function hasResult(): bool
    {
        return !$this->torrentMetaData->isEmpty();
    }

    public function hasMoreSeedsThan(ProviderResult $providerResult): bool
    {
        if (!$this->hasResult()) {
            return false;
        }

        return $this->getTorrentMetaData()->hasMoreSeedsThan($providerResult->getTorrentMetaData());
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'size' => $this->size->getHumanSize(),
            'data' => $this->torrentMetaData->toArray(),
        ];
    }
}