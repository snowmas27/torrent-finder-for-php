<?php

namespace TorrentFinder\Provider\ResultSet;

use Assert\Assertion;
use TorrentFinder\Provider\ProviderType;
use TorrentFinder\VideoSettings\Size;

class ProviderResult
{
    private TorrentData $torrentMetaData;
    private Size $size;
    private ProviderType $providerType;

    public static function fromArray(array $data): self
    {
        Assertion::keyExists($data, 'providerType',  'providerType key is missing');
        Assertion::keyExists($data, 'size', 'Size key is missing');
        Assertion::keyExists($data, 'data', 'Data key is missing');
        $size = Size::fromHumanSize($data['size']);
        $torrentMetaData = TorrentData::fromArray($data['data']);

        return new self(ProviderType::fromArray($data['providerType']), $torrentMetaData, $size);
    }

    public function __construct(ProviderType $providerType, TorrentData $torrentMetaData, Size $size)
    {
        $this->torrentMetaData = $torrentMetaData;
        $this->size = $size;
        $this->providerType = $providerType;
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
        return $this->providerType->getName();
    }

    public function getProviderType(): string
    {
        return $this->providerType->getType();
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
            'providerType' => [
                'name' => $this->providerType->getName(),
                'type' => $this->providerType->getType(),
            ],
            'size' => $this->size->getHumanSize(),
            'data' => $this->torrentMetaData->toArray(),
        ];
    }
}
