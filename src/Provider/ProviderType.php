<?php

namespace TorrentFinder\Provider;

use Assert\Assertion;

class ProviderType
{
    private string $name;
    private string $type;

    private const TYPES = [
        self::JACKETT => self::JACKETT,
        self::PROVIDER => self::PROVIDER,
    ];

    private const JACKETT = 'jackett';
    private const PROVIDER = 'provider';

    public static function fromArray(array $data): self
    {
        Assertion::keyExists($data, 'name', 'Name key is missing');
        Assertion::keyExists($data, 'type', 'Type key is missing');
        return new self($data['name'], $data['type']);
    }

    public static function jackett(string $name): self
    {
        return new self($name, self::JACKETT);
    }
    public static function provider(string $name): self
    {
        return new self($name, self::PROVIDER);
    }

    private function __construct(string $name, string $type)
    {
        Assertion::inArray($type, self::TYPES, 'Type must be one of: ' . implode(', ', self::TYPES));
        $this->name = $name;
        $this->type = $type;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getType(): string
    {
        return $this->type;
    }
}
