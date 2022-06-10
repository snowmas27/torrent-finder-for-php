<?php

namespace TorrentFinder\VideoSettings;

use TorrentFinder\Exception\Ensure;

class Resolution
{
    const ULTRA_HD = '2160p';
    const FULL_HD = '1080p';
    const HD = '720p';
    const LD = '480p';

    const RESOLUTIONS = [
        self::ULTRA_HD,
        self::FULL_HD,
        self::HD,
        self::LD,
    ];
    const RESOLUTION_QUALITY_MAP = [
        self::ULTRA_HD => 4,
        self::FULL_HD => 3,
        self::HD => 2,
        self::LD => 1,
    ];

    private $value;

    public static function guessFromString(string $query): self
    {
        if (!preg_match(sprintf('/(%s)/i', implode('|', self::RESOLUTIONS)), strtolower($query), $match)) {

            return new static(self::LD);
        }

        return new static($match[1]);
    }

    public static function ultraHd(): self
    {
        return new static(self::ULTRA_HD);
    }

    public static function fullHd(): self
    {
        return new static(self::FULL_HD);
    }

    public static function hd(): self
    {
        return new static(self::HD);
    }

    public static function ld(): self
    {
        return new static(self::LD);
    }

    public function __construct(string $value)
    {
        Ensure::inArray($value, self::RESOLUTIONS);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getValueForSearch(): string
    {
        if ($this->value === self::LD) {
            return '';
        }

        return $this->value;
    }

    public function isUltraHd(): bool
    {
        return self::ULTRA_HD === $this->value;
    }

    public function isFullHD(): bool
    {
        return self::FULL_HD === $this->value;
    }

    public function isHD(): bool
    {
        return self::HD === $this->value;
    }

    public function isLD(): bool
    {
        return self::LD === $this->value;
    }

    public function isHigherThan(Resolution $other): bool
    {
        return self::RESOLUTION_QUALITY_MAP[$this->value] > self::RESOLUTION_QUALITY_MAP[$other->getValue()];
    }

    public static function getResolutions(): array
    {
        return self::RESOLUTIONS;
    }
}
