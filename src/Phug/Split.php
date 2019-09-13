<?php

namespace Phug;

use Phug\Split\Command\Analyze;
use Phug\Split\Command\Compare;
use Phug\Split\Command\Dist;
use SimpleCli\SimpleCli;

class Split extends SimpleCli
{
    public function getCommands(): array
    {
        return [
            Analyze::class,
            Compare::class,
            Dist::class,
        ];
    }
}
