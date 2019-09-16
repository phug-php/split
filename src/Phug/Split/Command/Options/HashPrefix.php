<?php

namespace Phug\Split\Command\Options;

trait HashPrefix
{
    /**
     * @option p, hash-prefix
     *
     * Prefix to put before a commit hash to link a split commit to the original commit.
     *
     * @var string
     */
    public $hashPrefix = 'split: ';
}