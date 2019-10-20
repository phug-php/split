<?php

namespace Phug\Tests\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split;
use Phug\Split\Command\Copy;

/**
 * @coversDefaultClass \Phug\Split\Command\Copy
 */
class CopyTest extends TestCase
{
    /**
     * @covers ::run
     */
    public function testRun()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $copy = new Copy();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        $directory1 = realpath($directory1);
        chdir($directory1);
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash = $match[1];

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);
        chdir($directory2);
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC\n\nsplit: $hash");
        shell_exec('git commit --file=message.txt 2>&1');

        ob_start();
        $copy->repository = $directory1;
        $copy->destination = "$directory2/tests";
        $copy->filters = 'a.txt,b.txt';
        $return = $copy->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $contentA = @file_get_contents("$directory2/tests/a.txt");
        $contentB = @file_get_contents("$directory2/tests/b.txt");
        $contentC = @file_get_contents("$directory2/tests/c.txt");

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $this->assertSame("Copy ended.\n", $output);
        $this->assertTrue($return);
        $this->assertSame('A', $contentA);
        $this->assertSame('B', $contentB);
        $this->assertFalse($contentC);
    }

    /**
     * @covers ::run
     */
    public function testErrors()
    {
        $cli = new Split();
        $cli->setEscapeCharacter('#');

        $copy = new Copy();
        $copy->repository = null;

        ob_start();
        $return = $copy->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame("#[0;31mPlease provide an input repository URL.\n#[0m", $output);
        $this->assertFalse($return);

        $cwd = getcwd();
        $copy = new Copy();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        $directory1 = realpath($directory1);
        chdir($directory1);
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);
        chdir($directory2);
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC");
        shell_exec('git commit --file=message.txt 2>&1');

        ob_start();
        $copy->repository = $directory1;
        $copy->destination = "$directory2/tests";
        $copy->filters = 'a.txt,b.txt';
        $return = $copy->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $contentA = @file_get_contents("$directory2/tests/a.txt");
        $contentB = @file_get_contents("$directory2/tests/b.txt");
        $contentC = @file_get_contents("$directory2/tests/c.txt");

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $this->assertSame("#[0;31mLast commit must be linked to a mono-repository commit.\n#[0m", $output);
        $this->assertFalse($return);
        $this->assertFalse($contentA);
        $this->assertFalse($contentB);
        $this->assertFalse($contentC);

        $copy = new Copy();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        $directory1 = realpath($directory1);
        chdir($directory1);
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash = $match[1];

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);
        chdir($directory2);
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC\n\nsplit: $hash");
        shell_exec('git commit --file=message.txt 2>&1');

        ob_start();
        $copy->repository = $directory1;
        $copy->destination = 'i-do-not-exist';
        $copy->filters = 'a.txt,b.txt';
        $return = $copy->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $contentA = @file_get_contents("$directory2/tests/a.txt");
        $contentB = @file_get_contents("$directory2/tests/b.txt");
        $contentC = @file_get_contents("$directory2/tests/c.txt");

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $this->assertSame("#[0;31mDestination directory \"i-do-not-exist\" does not seem to exist.\n#[0m", $output);
        $this->assertFalse($return);
        $this->assertFalse($contentA);
        $this->assertFalse($contentB);
        $this->assertFalse($contentC);
    }
}
