<?php

namespace Phug\Test\Split\Git;

use PHPUnit\Framework\TestCase;
use Phug\Split\Git\Act;
use Phug\Split\Git\Author;
use Phug\Split\Git\Commit;
use Phug\Split\Git\Date;
use Phug\Split\Git\EmptyLogList;
use Phug\Split\Git\ImmutableLogException;
use Phug\Split\Git\InvalidGitLogUnit;
use Phug\Split\Git\Log;

/**
 * @coversDefaultClass \Phug\Split\Git\Log
 */
class LogTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers \Phug\Split\Git\EmptyLogList::__construct
     */
    public function testEmptyLog()
    {
        $this->expectException(EmptyLogList::class);
        $this->expectExceptionMessage('Log list should contain at least one commit.');

        new Log([]);
    }

    /**
     * @covers ::__construct
     * @covers \Phug\Split\Git\InvalidGitLogUnit::__construct
     */
    public function testInvalidGitLogType()
    {
        $this->expectException(InvalidGitLogUnit::class);
        $this->expectExceptionMessage('Invalid git log unit: integer, Phug\\Split\\Git\\Commit expected');

        new Log([1]);
    }

    /**
     * @covers ::__construct
     * @covers \Phug\Split\Git\InvalidGitLogUnit::__construct
     */
    public function testInvalidGitLogObject()
    {
        $this->expectException(InvalidGitLogUnit::class);
        $this->expectExceptionMessage('Invalid git log unit: stdClass, Phug\\Split\\Git\\Commit expected');

        new Log([(object) []]);
    }

    /**
     * @covers ::__construct
     * @covers \Phug\Split\Git\ImmutableLogException::__construct
     */
    public function testImmutabilityOnSet()
    {
        $this->expectException(ImmutableLogException::class);
        $this->expectExceptionMessage(
            'Log instance is immutable, create a new instance new Log($commits) with a new list of commits'
        );

        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );
        $log = new Log([$commit]);

        $log[0] = clone $commit;
    }

    /**
     * @covers ::__construct
     * @covers \Phug\Split\Git\ImmutableLogException::__construct
     */
    public function testImmutabilityOnUnset()
    {
        $this->expectException(ImmutableLogException::class);
        $this->expectExceptionMessage(
            'Log instance is immutable, create a new instance new Log($commits) with a new list of commits'
        );

        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );
        $log = new Log([$commit]);

        unset($log[0]);
    }
}
