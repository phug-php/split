<?php

namespace Phug\Split\Git;

use DateTimeImmutable;

class Date extends DateTimeImmutable
{
    /**
     * Get the date string originally passed to the constructor.
     *
     * @var string
     */
    protected $originalString;

    public function __construct($time = null, $timezone = null)
    {
        $this->originalString = $time;

        parent::__construct($time, $timezone);
    }

    public function __toString(): string
    {
        if ($this->originalString) {
            return (string) $this->originalString;
        }

        return $this->format('r');
    }
}
