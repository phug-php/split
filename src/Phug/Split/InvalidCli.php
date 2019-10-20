<?php

namespace Phug\Split;

use InvalidArgumentException;
use Phug\Split;
use Throwable;

class InvalidCli extends InvalidArgumentException
{
    public function __construct(object $cli, int $code = 0, Throwable $previous = null)
    {
        $class = get_class($cli);

        parent::__construct("Invalid cli class given: $class, ".Split::class.' expected', $code, $previous);
    }
}
