<?php

namespace Phug\Split\Git;

use InvalidArgumentException;
use Throwable;

class EmptyLogList extends InvalidArgumentException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('Log list should contain at least one commit.', $code, $previous);
    }
}
