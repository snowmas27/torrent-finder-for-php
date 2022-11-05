<?php

namespace TorrentFinder\VideoSettings;

use TorrentFinder\Exception\Ensure;

class Size
{
    const UNIT_KB = 'KB';
    const UNIT_MB = 'MB';
    const UNIT_MO = 'MO';
    const UNIT_GB = 'GB';
    const UNIT_GO = 'GO';
    private $bytes;

    const SIZE_LIST = [
        self::UNIT_KB,
        self::UNIT_MB,
        self::UNIT_MO,
        self::UNIT_GB,
        self::UNIT_GO
    ];

    public static function fromHumanSize(string $humanSize): self
    {
        preg_match(sprintf('/([\d\.]+).*(%s)/i', implode('|', self::SIZE_LIST)), $humanSize, $match);

        if (!isset($match[1])) {
            throw new \UnexpectedValueException($humanSize);
        }
        $sizeUnit = strtoupper($match[2]);
        Ensure::sizeUnitAllowed($sizeUnit);

        return new static(self::convertToBytes($match[1], $sizeUnit));
    }

    public function __construct(float $bytes)
    {
        $this->bytes = $bytes;
    }

    public function getBytes(): float
    {
        return $this->bytes;
    }

    public function getHumanSize(): string
    {
        $minLengthMB = 1024 ** 2;
        $minLengthGB = 1024 ** 3;

        $unit = self::UNIT_MB;
        if ($this->bytes < $minLengthMB) {
            $size = 0;
        } elseif ($this->bytes >= $minLengthMB && $this->bytes < $minLengthGB) {
            $size = $this->bytes / $minLengthMB;
        } else {
            $size = $this->bytes / $minLengthGB;
            $unit = self::UNIT_GB;
        }
        $size = number_format($size, 2);

        return sprintf('%02f %s', $size, $unit);
    }

    public function isBiggerThan(Size $size): bool
    {
        return $this->bytes > $size->getBytes();
    }

    public function isSmallerThan(Size $size): bool
    {
        return $this->bytes < $size->getBytes();
    }

    public function isBetween(SizeRange $sizeRange): bool
    {
        return $sizeRange->inRange(new Size($this->bytes));
    }

    private static function convertToBytes(float $value, string $unit): float
    {
        if ($unit === self::UNIT_KB) {
            return $value * 1024 ** 1;
        }

        if ($unit === self::UNIT_MB || $unit === self::UNIT_MO) {
            return $value * 1024 ** 2;
        }

        if ($unit === self::UNIT_GB || $unit === self::UNIT_GO) {
            return $value * 1024 ** 3;
        }
    }
}
