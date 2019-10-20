<?php

namespace Phug\Tests\Split\Git;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Phug\Split\Git\Date;

/**
 * @coversDefaultClass \Phug\Split\Git\Date
 */
class DateTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testType()
    {
        $this->assertInstanceOf(DateTimeImmutable::class, new Date());
    }

    /**
     * @covers ::__toString
     */
    public function testStringCast()
    {
        $date = new Date();
        $date = $date->setTimestamp(123456789);

        $this->assertSame('Thu, 29 Nov 1973 21:33:09 +0000', (string) $date);
        $this->assertSame('2019-08-15 23:00:15', (string) new Date('2019-08-15 23:00:15'));
        $this->assertSame('next Monday', (string) new Date('next Monday'));
    }
}
