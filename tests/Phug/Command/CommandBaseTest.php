<?php

namespace Phug\Test\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split\Command\CommandBase;
use Phug\Split\Command\Copy;
use Phug\Split\Git\Commit;
use Phug\Split\Git\Log;
use ReflectionException;
use ReflectionMethod;

/**
 * @coversDefaultClass \Phug\Split\Command\CommandBase
 */
class CommandBaseTest extends TestCase
{
    public function getEscapableStrings()
    {
        return [
            ['foobar'],
            ["something\\Yoh'la' hop"],
            ["something\\Yoh\'\"12\"' hop"],
        ];
    }

    /**
     * @dataProvider getEscapableStrings
     *
     * @covers ::gitEscape
     *
     * @param string $string
     *
     * @throws ReflectionException
     */
    public function testGitEscape(string $string)
    {
        $cwd = getcwd();
        $copy = new Copy();
        $gitEscape = new ReflectionMethod(CommandBase::class, 'gitEscape');
        $gitEscape->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        shell_exec('git init');
        file_put_contents('readme.md', '# README');
        shell_exec('git add readme.md');
        shell_exec('git commit --message='.$gitEscape->invoke($copy, $string));
        $log = shell_exec('git log -n 1 2>&1');
        chdir($cwd);
        @shell_exec('rm -rf '.escapeshellarg($directory).' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q '.escapeshellarg($directory).' 2>&1');
        @FileSystem::delete($directory);

        $log = explode("\n\n", $log);
        $log = trim(preg_replace('/^ {4}/m', '', $log[1]));

        $this->assertSame($string, $log);
    }

    /**
     * @covers ::getGitCommand
     */
    public function testGetGitCommand()
    {
        $copy = new Copy();
        $getGitCommand = new ReflectionMethod(CommandBase::class, 'getGitCommand');
        $getGitCommand->setAccessible(true);

        $this->assertSame('git abc', $getGitCommand->invoke($copy, 'abc'));
        $this->assertSame('git abc --foo="bar"', $getGitCommand->invoke($copy, 'abc', ['foo' => 'bar']));
    }

    /**
     * @covers ::git
     */
    public function testGit()
    {
        $copy = new Copy();
        $copy->gitProgram = 'echo';
        $git = new ReflectionMethod(CommandBase::class, 'git');
        $git->setAccessible(true);

        $this->assertSame("abc\n", $git->invoke($copy, 'abc'));
    }

    /**
     * @covers ::latest
     */
    public function testLatest()
    {
        $cwd = getcwd();
        $copy = new Copy();
        $latest = new ReflectionMethod(CommandBase::class, 'latest');
        $latest->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('a', $messageA = "Commit A\n\nsplit: abc");
        file_put_contents('b', $messageB = "Commit B\n\nsplit: def");
        file_put_contents('c', $messageC = "Commit C\n\nsplit: 123");
        shell_exec('git init');
        FileSystem::createDir('foo');
        file_put_contents('foo/bar', 'bar');
        shell_exec('git add foo/bar');
        shell_exec('git commit --file=a');
        file_put_contents('foo/baz', 'baz');
        shell_exec('git add foo/baz');
        shell_exec('git commit --file=b');
        file_put_contents('readme.md', '# README');
        shell_exec('git add readme.md');
        shell_exec('git commit --file=c');
        /** @var Commit[]|Log $commits */
        $commits = $latest->invoke($copy, 2);
        /** @var Commit[]|Log $fooCommits */
        $fooCommits = $latest->invoke($copy, 2, 'foo');
        chdir($cwd);
        @shell_exec('rm -rf '.escapeshellarg($directory).' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q '.escapeshellarg($directory).' 2>&1');
        @FileSystem::delete($directory);

        $this->assertInstanceOf(Log::class, $commits);
        $this->assertInstanceOf(Log::class, $fooCommits);
        $this->assertCount(2, $commits);
        $this->assertCount(2, $fooCommits);
        $this->assertInstanceOf(Commit::class, $commits[0]);
        $this->assertInstanceOf(Commit::class, $fooCommits[0]);
        $this->assertInstanceOf(Commit::class, $commits[1]);
        $this->assertInstanceOf(Commit::class, $fooCommits[1]);
        $this->assertSame($messageC, $commits[0]->getMessage());
        $this->assertSame($messageB, $commits[1]->getMessage());
        $this->assertSame($messageB, $fooCommits[0]->getMessage());
        $this->assertSame($messageA, $fooCommits[1]->getMessage());
    }

    /**
     * @covers ::last
     */
    public function testLast()
    {
        $cwd = getcwd();
        $copy = new Copy();
        $last = new ReflectionMethod(CommandBase::class, 'last');
        $last->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('a', $messageA = "Commit A\n\nsplit: abc");
        file_put_contents('b', $messageB = "Commit B\n\nsplit: def");
        file_put_contents('c', $messageC = "Commit C\n\nsplit: 123");
        shell_exec('git init');
        FileSystem::createDir('foo');
        file_put_contents('foo/bar', 'bar');
        shell_exec('git add foo/bar');
        shell_exec('git commit --file=a');
        file_put_contents('foo/baz', 'baz');
        shell_exec('git add foo/baz');
        shell_exec('git commit --file=b');
        file_put_contents('readme.md', '# README');
        shell_exec('git add readme.md');
        shell_exec('git commit --file=c');
        /** @var Commit $commit */
        $commit = $last->invoke($copy);
        /** @var Commit $fooCommit */
        $fooCommit = $last->invoke($copy, 'foo');
        chdir($cwd);
        @shell_exec('rm -rf '.escapeshellarg($directory).' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q '.escapeshellarg($directory).' 2>&1');
        @FileSystem::delete($directory);

        $this->assertInstanceOf(Commit::class, $commit);
        $this->assertInstanceOf(Commit::class, $fooCommit);
        $this->assertSame($messageC, $commit->getMessage());
        $this->assertSame($messageB, $fooCommit->getMessage());
    }

    /**
     * @covers ::getCurrentLinkedCommitHash
     */
    public function testGetCurrentLinkedCommitHash()
    {
        $cwd = getcwd();
        $copy = new Copy();
        $getCurrentLinkedCommitHash = new ReflectionMethod(CommandBase::class, 'getCurrentLinkedCommitHash');
        $getCurrentLinkedCommitHash->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('a', $messageA = "Commit A\n\nsplit: abc");
        file_put_contents('b', $messageB = "Commit B\n\nsplit: def");
        file_put_contents('c', $messageC = "Commit C\n\nsplit: 123");
        shell_exec('git init');
        FileSystem::createDir('foo');
        file_put_contents('foo/bar', 'bar');
        shell_exec('git add foo/bar');
        shell_exec('git commit --file=a');
        file_put_contents('foo/baz', 'baz');
        shell_exec('git add foo/baz');
        shell_exec('git commit --file=b');
        file_put_contents('readme.md', '# README');
        shell_exec('git add readme.md');
        shell_exec('git commit --file=c');
        /** @var string $hash */
        $hash = $getCurrentLinkedCommitHash->invoke($copy);
        chdir($cwd);
        @shell_exec('rm -rf '.escapeshellarg($directory).' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q '.escapeshellarg($directory).' 2>&1');
        @FileSystem::delete($directory);

        $this->assertSame('123', $hash);
    }

    /**
     * @covers ::remove
     */
    public function testRemove()
    {
        $cwd = getcwd();
        $copy = new Copy();
        $remove = new ReflectionMethod(CommandBase::class, 'remove');
        $remove->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('a', 'anything');
        FileSystem::createDir('foo');
        file_put_contents('foo/bar', 'bar');
        chdir($cwd);
        $remove->invoke($copy, $directory);

        $this->assertDirectoryNotExists($directory);
    }
}
