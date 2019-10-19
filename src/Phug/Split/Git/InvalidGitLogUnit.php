<?php

namespace Phug\Split\Git;

use InvalidArgumentException;
use Throwable;

class InvalidGitLogUnit extends InvalidArgumentException
{
    public function __construct(string $unit = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Invalid git log unit: $unit, ".Commit::class.' expected', $code, $previous);
    }
}
