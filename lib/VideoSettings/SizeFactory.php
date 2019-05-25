<?php

namespace TorrentFinder\VideoSettings;

class SizeFactory
{
    public static function fromHumanSize(string $humanSize): Size
    {
        [$value, $unit] = explode(' ', $humanSize);

        return self::convertFromWeirdFormat($value, $unit);
    }

    public static function convertFromWeirdFormat(float $value, string $unit): Size
    {
        $unit = strtoupper($unit);
        $kbPossibleFormat = ['K', 'KO'];
        $mbPossibleFormat = ['M', 'MO', 'MIBYTE', 'MIB', 'MB', 'MBS'];
        $gbPossibleFormat = ['G', 'GO', 'GIBYTE', 'GIB', 'GB', 'GBS'];

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