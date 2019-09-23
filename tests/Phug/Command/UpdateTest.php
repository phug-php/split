<?php

namespace Phug\Test\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split;
use Phug\Split\Command\Update;
use Phug\Split\Git\Author;
use Phug\Split\Git\Commit;
use ReflectionException;
use ReflectionMethod;
use Traversable;

/**
 * @coversDefaultClass \Phug\Split\Command\Update
 */
class UpdateTest extends TestCase
{
    /**
     * @covers ::getPackage
     *
     * @throws ReflectionException
     */
    public function testGetPackage()
    {
        $cwd = getcwd();
        $dist = new Update();
        $getPackage = new ReflectionMethod($dist, 'getPackage');
        $getPackage->setAccessible(true);
        $directory = realpath(sys_get_temp_dir());
        chdir($directory);
        $package = $getPackage->invoke($dist, '.', ['name' => 'hop']);
        chdir($cwd);

        $this->assertSame([
            'name' => 'hop',
            'directory' => $directory,
            'children' => [],
        ], $package);
    }

    /**
     * @covers ::getReplayLog
     *
     * @throws ReflectionException
     */
    public function testGetReplayLog()
    {
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $cwd = getcwd();
        $dist = new Update();
        $getReplayLog = new ReflectionMethod($dist, 'getReplayLog');
        $getReplayLog->setAccessible(true);
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
        file_put_contents('a.txt', 'A');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        file_put_contents('b.txt', 'B');
        shell_exec('git add b.txt 2>&1');
        shell_exec('git commit --message=AddB 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash = $match[1];
        file_put_contents('c.txt', 'C');
        shell_exec('git add c.txt 2>&1');
        shell_exec('git commit --message=AddC 2>&1');
        file_put_contents('d.txt', 'D');
        shell_exec('git add d.txt 2>&1');
        shell_exec('git commit --message=AddD 2>&1');
        /** @var Traversable $log1 */
        $log1 = $getReplayLog->invoke($dist, $cli, $hash);
        ob_start();
        /** @var Traversable $log2 */
        $log2 = $getReplayLog->invoke($dist, $cli, 'does-not-exist');
        $output = ob_get_contents();
        ob_end_clean();
        chdir($cwd);

        @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
        @FileSystem::delete($directory);

        $this->assertSame([
            'AddC',
            'AddD',
        ], array_map(function (Commit $commit) {
            return $commit->getMessage();
        }, iterator_to_array($log1)));

        $this->assertSame([
            'AddD',
        ], array_map(function (Commit $commit) {
            return $commit->getMessage();
        }, iterator_to_array($log2)));

        $this->assertSame(
            "#[1;33mNo matching commit found in the last 20 commits, new step added.\n#[0m",
            $output
        );
    }

    /**
     * @covers ::setGitCommitter
     *
     * @throws ReflectionException
     */
    public function testSetGitCommitter()
    {
        $dist = new Update();
        file_put_contents('log.txt', '');
        file_put_contents(
            'record.php',
            '<?php file_put_contents("log.txt", file_get_contents("log.txt").implode(" ", array_slice($argv, 1))."\n");'
        );
        $dist->gitProgram = 'php record.php';
        $setGitCommitter = new ReflectionMethod($dist, 'setGitCommitter');
        $setGitCommitter->setAccessible(true);
        $setGitCommitter->invoke($dist, new Author(
            'Aretha Franklin',
            'aretha.franklin@atlanticrecords.com',
        ));
        $output = file_get_contents('log.txt');
        unlink('log.txt');
        unlink('record.php');

        $this->assertSame(
            "config user.name Aretha Franklin\nconfig user.email aretha.franklin@atlanticrecords.com\n",
            $output
        );
    }
}
