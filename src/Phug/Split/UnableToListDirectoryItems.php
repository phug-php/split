<?php

namespace Phug\Split;

use RuntimeException;
use Throwable;

class UnableToListDirectoryItems extends RuntimeException
{
    public function __construct(string $directory, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Unable to list items of the directory '$directory'", $code, $previous);
    }
}
