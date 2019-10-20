<?php

namespace Phug\Tests\Split\Command;

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

        // @codeCoverageIgnoreStart
        if (file_exists($directory)) {
            @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
        }
        // @codeCoverageIgnoreEnd

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

    /**
     * @covers ::distributePackage
     */
    public function testWithUpToDateDirectory()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $dist = new Update();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        FileSystem::createDir("$directory1/sub-package");
        FileSystem::createDir("$directory1/api/vendor");
        $directory1 = realpath($directory1);
        $subPackageDirectory = realpath("$directory1/sub-package");

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);

        chdir($directory1);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
        file_put_contents('sub-package/composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash1 = $match[1];

        file_put_contents('api/vendor/sub-package.json', json_encode([
            'packages' => [
                'vendor/sub-package' => [
                    'dev-master' => [
                        'source' => [
                            'type' => 'git',
                            'url' => $directory2,
                        ],
                    ],
                ],
            ],
        ]));

        chdir($directory2);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC\n\nsplit: $hash1");
        shell_exec('git commit --file=message.txt 2>&1');

        chdir($directory1);

        ob_start();
        $dist->api = 'api/%s.json';
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $outputDirectory = realpath("$directory1/dist");
        $contentD = @file_get_contents("$outputDirectory/vendor/sub-package/d.txt");
        $contentE = @file_get_contents("$outputDirectory/vendor/sub-package/e.txt");
        $contentF = @file_get_contents("$outputDirectory/vendor/sub-package/f.txt");

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $path = $directory1.DIRECTORY_SEPARATOR;
        $expected = implode("\n", array_merge([
            'vendor/package',
            '#[1;35mBuild vendor/sub-package',
            "#[0m#[1;32mgit clone $directory2 {$path}dist/vendor/sub-package",
            "#[0m#[1;30mCloning into '{$path}dist/vendor/sub-package'...",
            'done.',
            "#[0m#[1;34mcd {$path}dist/vendor/sub-package",
            '#[0m#[1;32mgit checkout master',
            "#[0m#[1;34mcd $subPackageDirectory",
            '#[0m#[0;32mvendor/sub-package is already up to date.',
            "#[0m#[1;35mBuild distributed in {$path}dist",
            '#[0m',
        ]));
        $this->assertSame($expected, $output);
        $this->assertTrue($return);
        $this->assertSame('D', $contentD);
        $this->assertSame('E', $contentE);
        $this->assertSame('F', $contentF);
    }

    /**
     * @covers ::distributePackage
     */
    public function testWithTouchedDirectory()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $dist = new Update();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        FileSystem::createDir("$directory1/sub-package");
        FileSystem::createDir("$directory1/api/vendor");
        $directory1 = realpath($directory1);
        $subPackageDirectory = realpath("$directory1/sub-package");

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);

        chdir($directory1);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
        file_put_contents('sub-package/composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('sub-package/d.txt', "D1\n");
        file_put_contents('sub-package/e.txt', 'E');
        file_put_contents('sub-package/f.txt', 'F');
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash1 = $match[1];
        file_put_contents('sub-package/d.txt', "D1\nD2\n");
        shell_exec('git add sub-package/d.txt 2>&1');
        shell_exec('git commit --message=ModifyD 2>&1');

        file_put_contents('api/vendor/sub-package.json', json_encode([
            'packages' => [
                'vendor/sub-package' => [
                    'dev-master' => [
                        'source' => [
                            'type' => 'git',
                            'url' => $directory2,
                        ],
                    ],
                ],
            ],
        ]));

        chdir($directory2);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC\n\nsplit: $hash1");
        shell_exec('git commit --file=message.txt 2>&1');

        chdir($directory1);

        ob_start();
        $dist->api = 'api/%s.json';
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $outputDirectory = realpath("$directory1/dist");
        $contentD = @file_get_contents("$outputDirectory/vendor/sub-package/d.txt");
        $contentE = @file_get_contents("$outputDirectory/vendor/sub-package/e.txt");
        $contentF = @file_get_contents("$outputDirectory/vendor/sub-package/f.txt");

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $path = $directory1.DIRECTORY_SEPARATOR;
        $output = preg_replace('/remote: error: [\s\S]+\n\n/', "@@ERROR@@\n", $output);
        $expected = implode("\n", array_merge([
            'vendor/package',
            '#[1;35mBuild vendor/sub-package',
            "#[0m#[1;32mgit clone $directory2 {$path}dist/vendor/sub-package",
            "#[0m#[1;30mCloning into '{$path}dist/vendor/sub-package'...",
            'done.',
            "#[0m#[1;34mcd {$path}dist/vendor/sub-package",
            '#[0m#[1;32mgit checkout master',
            "#[0m#[1;34mcd $subPackageDirectory",
            "#[0m#[1;34mcd {$path}dist/vendor/sub-package",
            '#[0m#[0;31mPushing vendor/sub-package',
            '@@ERROR@@',
            "#[0m#[1;34mcd $subPackageDirectory",
            "#[0m#[1;35mBuild distributed in {$path}dist",
            '#[0m',
        ]));
        $expected = str_replace('\\', '/', $expected);
        $output = str_replace('\\', '/', $output);
        $this->assertSame($expected, $output);
        $this->assertTrue($return);
        $this->assertSame("D1\nD2\n", $contentD);
        $this->assertSame('E', $contentE);
        $this->assertSame('F', $contentF);
    }

    /**
     * @covers ::distributePackage
     */
    public function testErrors()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $dist = new Update();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        FileSystem::createDir("$directory1/sub-package");
        FileSystem::createDir("$directory1/api/vendor");
        $directory1 = realpath($directory1);

        $directory2 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir("$directory2/tests");
        $directory2 = realpath($directory2);

        chdir($directory1);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
        file_put_contents('sub-package/composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('a.txt', 'A');
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);
        $hash1 = $match[1];

        file_put_contents('api/vendor/sub-package.json', json_encode([
            'packages' => [
                'vendor/sub-package' => [
                    'dev-master' => [
                        'source' => [
                            'type' => 'snv',
                            'url' => $directory2,
                        ],
                    ],
                ],
            ],
        ]));

        chdir($directory2);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('d.txt', 'D');
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        file_put_contents('message.txt', "ABC\n\nsplit: $hash1");
        shell_exec('git commit --file=message.txt 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);

        chdir($directory1);

        ob_start();
        $dist->api = 'api/%s.json';
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $outputDirectory = $directory1.DIRECTORY_SEPARATOR.'dist';

        $this->assertSame(
            "vendor/package\n#[0;31mNo git source found for the package vendor/sub-package\n".
            "#[0m#[1;35mBuild distributed in $outputDirectory\n#[0m",
            $output
        );
        $this->assertTrue($return);
    }
}
