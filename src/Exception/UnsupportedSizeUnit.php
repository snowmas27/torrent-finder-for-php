<?php

namespace App\Exception;

use Throwable;

class UnsupportedSizeUnit extends \Exception
{
    public function __construct(string $message = null, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}