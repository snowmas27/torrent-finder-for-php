<?php

namespace TorrentFinder\Exception;

use Assert\Assertion;
use TorrentFinder\VideoSettings\Size;

class Ensure extends Assertion
{
    public static function sizeUnitAllowed(string $sizeUnit)
    {
        if (!in_array($sizeUnit, Size::SIZE_LIST)) {
            throw new UnsupportedSizeUnit();
        }
    }
}