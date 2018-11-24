<?php

namespace TorrentFinder\VideoSettings;

use TorrentFinder\Exception\Ensure;

class Size
{
	const UNIT_KB = 'KB';
	const UNIT_MB = 'MB';
	const UNIT_GB = 'GB';
	private $bytes;

	public static function fromHumanSize(string $humanSize): self
	{
		preg_match(sprintf('/([\d\.]+).*(%s|%s|%s)/', self::UNIT_MB, self::UNIT_KB, self::UNIT_GB), $humanSize, $match);
		if (!isset($match[1])) {
			throw new \UnexpectedValueException($humanSize);
		}
		Ensure::sizeUnitAllowed($match[2]);

		return new static(self::convertToBytes($match[1], $match[2]));
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

		return sprintf('%f %s', $size, $unit);
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
		if ($unit === self::UNIT_MB) {
			return $value * 1024 ** 2;
		}
		if ($unit === self::UNIT_GB) {
			return $value * 1024 ** 3;
		}
	}
}
