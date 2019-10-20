<?php

namespace Phug\Tests\Split\Git;

use Exception;
use PHPUnit\Framework\TestCase;
use Phug\Split\Git\Act;
use Phug\Split\Git\Author;
use Phug\Split\Git\Commit;
use Phug\Split\Git\Date;
use Phug\Split\Git\InvalidGitLogStringException;

/**
 * @coversDefaultClass \Phug\Split\Git\Commit
 */
class CommitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getHash
     */
    public function testGetHash()
    {
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );

        $this->assertSame('abc123', $commit->getHash());
    }

    /**
     * @covers ::fromGitLogString
     * @covers \Phug\Split\Git\InvalidGitLogStringException::__construct
     * @throws Exception
     */
    public function testFromGitLogStringException()
    {
        $this->expectException(InvalidGitLogStringException::class);
        $this->expectExceptionMessage('Invalid git log string: abc');

        Commit::fromGitLogString('abc');
    }

    /**
     * @covers ::fromGitLogString
     * @throws Exception
     */
    public function testFromGitLogString()
    {
        $commit = Commit::fromGitLogString(implode("\n", [
            'commit def987',
            'Author:     Ana Log <ana.log@company.com>',
            'AuthorDate: Thu, 18 Jul 2019 14:28:32 +0000',
            'Commit:     Bob Ine <bob.ine@company.com>',
            'CommitDate: Thu, 18 Jul 2019 14:49:23 +0000',
            '',
            '    [Theme] Fix color #fff and "white"',
            '',
            '    Resolve:',
            '      - #123 @John',
            '      - #456 @Marc',
        ]));

        $this->assertSame('def987', $commit->getHash());
        $this->assertSame('Ana Log', $commit->getAuthor()->getAuthor()->getName());
        $this->assertSame('ana.log@company.com', $commit->getAuthor()->getAuthor()->getEmail());
        $this->assertSame('2019-07-18 14:28:32', $commit->getAuthor()->getDate()->format('Y-m-d H:i:s'));
        $this->assertSame('Bob Ine', $commit->getCommit()->getAuthor()->getName());
        $this->assertSame('bob.ine@company.com', $commit->getCommit()->getAuthor()->getEmail());
        $this->assertSame('2019-07-18 14:49:23', $commit->getCommit()->getDate()->format('Y-m-d H:i:s'));
        $this->assertSame(implode("\n", [
            '[Theme] Fix color #fff and "white"',
            '',
            'Resolve:',
            '  - #123 @John',
            '  - #456 @Marc',
        ]), $commit->getMessage());
    }

    /**
     * @covers ::getMessage
     */
    public function testGetMessage()
    {
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );

        $this->assertSame('message', $commit->getMessage());
    }

    /**
     * @covers ::getAuthor
     */
    public function testGetAuthor()
    {
        $author = new Act(new Author('a', 'b'), new Date());
        $commit = new Commit(
            'abc123',
            $author,
            new Act(new Author('a', 'b'), new Date()),
            'message'
        );

        $this->assertSame($author, $commit->getAuthor());
    }

    /**
     * @covers ::getCommit
     */
    public function testGetCommit()
    {
        $commitAct = new Act(new Author('a', 'b'), new Date());
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            $commitAct,
            'message'
        );

        $this->assertSame($commitAct, $commit->getCommit());
    }

    /**
     * @covers ::findInMessage
     */
    public function testFindInMessage()
    {
        $commit = new Commit(
            'abc123',
            new Act(new Author('a', 'b'), new Date()),
            new Act(new Author('a', 'b'), new Date()),
            implode("\n", [
                '[Theme] Fix color #fff and "white"',
                '',
                'Resolve:',
                '  - #123 @John',
                '  - #456 @Marc',
            ])
        );

        $this->assertNull($commit->findInMessage('/\((.+)\)/'));
        $this->assertSame('Theme', $commit->findInMessage('/\[(.+)\]/'));
    }
}
