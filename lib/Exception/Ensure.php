<?php

namespace TorrentFinder\Exception;

use TorrentFinder\VideoSettings\Size;

class Ensure
{
	public static function sizeUnitAllowed(string $sizeUnit)
	{
		if ($sizeUnit !== Size::UNIT_MB && $sizeUnit !== Size::UNIT_GB && $sizeUnit !== Size::UNIT_KB) {
			throw new UnsupportedSizeUnit();
		}
	}

	public static function notEmpty($value, $message = null)
	{
		if (empty($value)) {
			throw new \UnexpectedValueException($message);
		}
	}
}