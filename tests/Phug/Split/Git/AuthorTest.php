<?php

namespace Phug\Tests\Split\Git;

use PHPUnit\Framework\TestCase;
use Phug\Split\Git\Author;

/**
 * @coversDefaultClass \Phug\Split\Git\Author
 */
class AuthorTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName()
    {
        $author = new Author('John Smith', 'john.smith@company.com');

        $this->assertSame('John Smith', $author->getName());
    }
    /**
     * @covers ::__construct
     * @covers ::getEmail
     */
    public function testGetEmail()
    {
        $author = new Author('John Smith', 'john.smith@company.com');

        $this->assertSame('john.smith@company.com', $author->getEmail());
    }

    /**
     * @covers ::__toString
     */
    public function testStringCast()
    {
        $author = new Author('John Smith', 'john.smith@company.com');

        $this->assertSame('John Smith <john.smith@company.com>', (string) $author);
    }
}
