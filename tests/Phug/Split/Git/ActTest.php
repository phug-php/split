<?php

namespace Phug\Tests\Split\Git;

use PHPUnit\Framework\TestCase;
use Phug\Split\Git\Act;
use Phug\Split\Git\Author;
use Phug\Split\Git\Date;

/**
 * @coversDefaultClass \Phug\Split\Git\Act
 */
class ActTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getAuthor
     */
    public function testGetAuthor()
    {
        $author = new Author('John Smith', 'john.smith@company.com');
        $act = new Act($author, new Date());

        $this->assertSame($author, $act->getAuthor());
    }

    /**
     * @covers ::__construct
     * @covers ::getDate
     */
    public function testGetDate()
    {
        $date = new Date();
        $act = new Act(new Author('John Smith', 'john.smith@company.com'), $date);

        $this->assertSame($date, $act->getDate());
    }

    /**
     * @covers ::__toString
     */
    public function testStringCast()
    {
        $act = new Act(new Author('John Smith', 'john.smith@company.com'), new Date());

        $this->assertSame('John Smith <john.smith@company.com>', (string) $act);
    }
}
