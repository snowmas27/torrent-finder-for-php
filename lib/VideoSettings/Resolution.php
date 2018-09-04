<?php

namespace TorrentFinder\VideoSettings;

use TorrentFinder\Exception\UnsupportedVideoResolution;

class Resolution
{
	const FULL_HD = '1080p';
	const HD = '720p';
	const LD = '480p';
    const RESOLUTIONS = [
        self::FULL_HD,
        self::HD,
        self::LD,
    ];

	private $value;

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
		if ($value !== self::FULL_HD && $value !== self::HD && $value !== self::LD) {
			throw new UnsupportedVideoResolution($value);
		}
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

	public function isHD(): bool
	{
		return $this->value === self::FULL_HD || $this->value === self::HD;
	}

	public function isLD(): bool
	{
		return $this->value === self::LD;
	}

	public static function getResolutions(): array
    {
        return self::RESOLUTIONS;
    }
}
