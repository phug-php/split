<?php

namespace Phug\Split\Git;

use InvalidArgumentException;
use Throwable;

class ImmutableLogException extends InvalidArgumentException
{
    public function __construct(int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            'Log instance is immutable, create a new instance new Log($commits) with a new list of commits',
            $code,
            $previous
        );
    }
}
