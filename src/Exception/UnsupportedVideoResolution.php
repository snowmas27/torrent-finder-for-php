<?php

namespace App\Exception;

use Throwable;

class UnsupportedVideoResolution extends \Exception
{
    public function __construct(string $format, Throwable $previous = null)
    {
        parent::__construct(sprintf("Unsupported video resolution '%s'", $format), 0, $previous);
    }
}