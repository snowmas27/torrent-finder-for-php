<?php

namespace TorrentFinder\VideoSettings;

class SizeRange
{
    private $sizeMini;
    private $sizeMax;

    public function __construct(Size $sizeMini, Size $sizeMax)
    {
        $this->sizeMini = $sizeMini;
        $this->sizeMax = $sizeMax;
    }

    public function inRange(Size $size): bool
    {
        return $size->isBiggerThan($this->sizeMini) && $size->isSmallerThan($this->sizeMax);
    }

    public function getSizeMini(): Size
    {
        return $this->sizeMini;
    }

    public function getSizeMax(): Size
    {
        return $this->sizeMax;
    }
}