<?php

namespace TorrentFinder\VideoSettings;

class SizeFactory
{
    public static function convertFromWeirdFormat(float $value, string $unit): Size
    {
        $unit = strtoupper($unit);
        $kbPossibleFormat = ['K', 'KO'];
        $mbPossibleFormat = ['M', 'MO', 'MIBYTE', 'MIB'];
        $gbPossibleFormat = ['G', 'GO', 'GIBYTE', 'GIB'];

        if (in_array($unit, $kbPossibleFormat, true)) {
            return Size::fromHumanSize(sprintf('%f %s', $value, Size::UNIT_KB));
        }
        if (in_array($unit, $mbPossibleFormat, true)) {
            return Size::fromHumanSize(sprintf('%f %s', $value, Size::UNIT_MB));
        }
        if (in_array($unit, $gbPossibleFormat, true)) {
            return Size::fromHumanSize(sprintf('%f %s', $value, Size::UNIT_GB));
        }

        throw new \UnexpectedValueException("'$unit' not allowed");
    }
}