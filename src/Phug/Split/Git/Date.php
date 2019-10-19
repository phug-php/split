<?php

namespace Phug\Split\Git;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

class Date extends DateTimeImmutable
{
    /**
     * Get the date string originally passed to the constructor.
     *
     * @var string|null
     */
    protected $originalString;

    /**
     * Date constructor.
     *
     * @param string $time
     * @param DateTimeZone $timezone
     *
     * @throws Exception
     */
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

        return $this->format('r') ?: '';
    }
}
