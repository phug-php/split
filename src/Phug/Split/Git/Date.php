<?php

namespace Phug\Split\Git;

use DateTimeImmutable;
use DateTimeZone;

/**
 * @psalm-suppress MethodSignatureMismatch
 */
class Date extends DateTimeImmutable
{
    /**
     * Get the date string originally passed to the constructor.
     *
     * @var string|null
     */
    protected $originalString;

    public function __construct(?string $time = null, ?DateTimeZone $timezone = null)
    {
        $this->originalString = $time;

        parent::__construct($time ?? 'now', $timezone);
    }

    public function __toString(): string
    {
        return $this->originalString ?? ($this->format('r') ?: '');
    }
}
