<?php

namespace Phug\Split\Git;

use InvalidArgumentException;
use Throwable;

class InvalidGitLogStringException extends InvalidArgumentException
{
    public function __construct($log = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Invalid git log string: $log", $code, $previous);
    }
}
