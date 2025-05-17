<?php

namespace TorrentFinder\Provider\ResultSet;

use Assert\Assertion;
use TorrentFinder\VideoSettings\Size;

class ProviderResult
{
    private TorrentData $torrentMetaData;
    private Size $size;
    private string $provider;

    public static function fromArray(array $data): self
    {
        Assertion::keyExists($data, 'provider', 'Provider key is missing');
        Assertion::keyExists($data, 'size', 'Size key is missing');
        Assertion::keyExists($data, 'data', 'Data key is missing');
        $size = Size::fromHumanSize($data['size']);
        $torrentMetaData = TorrentData::fromArray($data['data']);

        return new self($data['provider'], $torrentMetaData, $size);
    }

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
