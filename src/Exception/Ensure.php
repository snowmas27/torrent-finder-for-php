<?php

namespace App\Exception;

use Assert\Assertion;
use App\VideoSettings\Size;

class Ensure extends Assertion
{
    public static function sizeUnitAllowed(string $sizeUnit)
    {
        if ($sizeUnit !== Size::UNIT_MB && $sizeUnit !== Size::UNIT_GB && $sizeUnit !== Size::UNIT_KB) {
            throw new UnsupportedSizeUnit();
        }
    }
}