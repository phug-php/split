<?php

namespace Phug\Split\Command\Options;

trait GitProgram
{
    /**
     * @option g, git-program
     *
     * Git binary program path.
     *
     * @var string
     */
    public $gitProgram = 'git';
}