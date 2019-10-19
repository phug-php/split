<?php

namespace Phug\Test\Split\Git;

use ArrayAccess;
use ArrayObject;
use Exception;
use IteratorAggregate;
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
     * @covers ::offsetSet
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
     * @covers ::offsetUnset
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

    /**
     * @covers ::__construct
     * @covers ::getIterator
     */
    public function testGetIterator()
    {
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );
        $log = new Log([$commit]);

        $this->assertInstanceOf(IteratorAggregate::class, $log);
        $this->assertInstanceOf(ArrayAccess::class, $log);

        $iterator = $log->getIterator();

        $this->assertInstanceOf(ArrayObject::class, $iterator);
        $this->assertSame($commit, $iterator[0]);
    }

    /**
     * @covers ::offsetExists
     */
    public function testOffsetExists()
    {
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );
        $log = new Log([$commit]);

        $this->assertTrue(isset($log[0]));
        $this->assertFalse(isset($log[1]));
    }

    /**
     * @covers ::fromGitLogString
     * @covers ::count
     * @covers ::offsetGet
     * @throws Exception
     */
    public function testFromGitLogString()
    {
        $log = Log::fromGitLogString(implode("\n", [
            'commit 1027388876b5fe905ad39eea13e427bd86a4ab13 (HEAD -> master, origin/master, origin/HEAD)',
            'Author:     KyleKatarn <kylekatarnls@gmail.com>',
            'AuthorDate: Wed Sep 18 16:45:43 2019 +0200',
            'Commit:     KyleKatarn <kylekatarnls@gmail.com>',
            'CommitDate: Wed Sep 18 16:46:07 2019 +0200',
            '',
            '    Add unit tests',
            '',
            'commit 0464007d3f51e534109b2c2896afb67a18de199d',
            'Author:     KyleKatarn <kylekatarnls@gmail.com>',
            'AuthorDate: Tue Sep 17 17:29:05 2019 +0200',
            'Commit:     KyleKatarn <kylekatarnls@gmail.com>',
            'CommitDate: Tue Sep 17 17:29:05 2019 +0200',
            '',
            '    [Travis-CI] Debug tests',
            '',
        ]));

        $this->assertCount(2, $log);
        $this->assertSame('1027388876b5fe905ad39eea13e427bd86a4ab13', $log[0]->getHash());
        $this->assertSame('Wed Sep 18 16:46:07 2019 +0200', (string) $log[0]->getCommit()->getDate());
        $this->assertSame('Wed Sep 18 16:45:43 2019 +0200', (string) $log[0]->getAuthor()->getDate());
        $this->assertSame('0464007d3f51e534109b2c2896afb67a18de199d', $log[1]->getHash());
        $this->assertSame('Tue Sep 17 17:29:05 2019 +0200', (string) $log[1]->getCommit()->getDate());
        $this->assertSame('Tue Sep 17 17:29:05 2019 +0200', (string) $log[1]->getAuthor()->getDate());
    }
}
