<?php

namespace Phug\Test\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split;
use Phug\Split\Command\Dist;

/**
 * @coversDefaultClass \Phug\Split\Command\Dist
 */
class DistTest extends TestCase
{
    /**
     * @covers ::run
     * @covers ::distribute
     * @covers ::distributePackage
     * @covers ::info
     */
    public function testRun()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $dist = new Dist();

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
        $hash1 = $match[1];

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
        $hash2 = $match[1];
        shell_exec("git checkout $hash2 2>&1");

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
        $expected = implode("\n", [
            'vendor/package',
            '#[1;35mBuild vendor/sub-package',
            "#[0m#[1;32mgit clone $directory2 {$path}dist/vendor/sub-package",
            "#[0m#[1;30mCloning into '{$path}dist/vendor/sub-package'...",
            'done.',
            "#[0m#[1;34mcd {$path}dist/vendor/sub-package",
            '#[0m#[1;32mgit checkout master',
            "#[0m#[1;35mBuild distributed in {$path}dist",
            '#[0m',
        ]);
        $this->assertSame($expected, $output);
        $this->assertTrue($return);
        $this->assertSame('D', $contentD);
        $this->assertSame('E', $contentE);
        $this->assertSame('F', $contentF);
    }

    /**
     * @covers ::info
     */
    public function testVerboseOption()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $dist = new Dist();
        $dist->verbose = true;

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
        $hash1 = $match[1];

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
        $hash2 = $match[1];
        shell_exec("git checkout $hash2 2>&1");

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
        $expected = implode("\n", [
            'vendor/package',
            '#[1;35mBuild vendor/sub-package',
            "#[0m#[1;32mgit clone $directory2 {$path}dist/vendor/sub-package",
            "#[0m#[1;30mCloning into '{$path}dist/vendor/sub-package'...",
            'done.',
            "#[0m#[1;34mcd {$path}dist/vendor/sub-package",
            '#[0m#[0;33mgit rev-parse --verify origin/master',
            "#[0m#[0;33m => $hash2",
            '#[0m#[1;32mgit checkout master',
            "#[0m#[1;35mBuild distributed in {$path}dist",
            '#[0m',
        ]);
        $this->assertSame($expected, $output);
        $this->assertTrue($return);
        $this->assertSame('D', $contentD);
        $this->assertSame('E', $contentE);
        $this->assertSame('F', $contentF);
    }

    /**
     * @covers ::run
     * @covers ::distribute
     */
    public function testErrors()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');

        $dist = new Dist();

        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        $directory = realpath($directory);
        chdir($directory);

        ob_start();
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
        @FileSystem::delete($directory);

        $this->assertSame("#[0;31mRoot project directory should contains a composer.json file.\n#[0m", $output);
        $this->assertFalse($return);

        $dist = new Dist();

        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        $directory = realpath($directory);
        chdir($directory);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
        file_put_contents('b.txt', 'B');
        file_put_contents('c.txt', 'C');
        shell_exec('git init 2>&1');
        shell_exec('git add --all 2>&1');
        shell_exec('git commit --message=Init 2>&1');
        preg_match('/^commit (\S+)/', shell_exec('git log -n 1'), $match);

        ob_start();
        $dist->output = "//////\nnot/writable";
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        chdir($cwd);
        @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
        @FileSystem::delete($directory);

        $this->assertSame("vendor/package\n#[0;31mUnable to create output directory.\n#[0m", $output);
        $this->assertFalse($return);

        $dist = new Dist();

        $directory1 = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory1);
        $directory1 = realpath($directory1);
        chdir($directory1);
        file_put_contents('composer.json', json_encode(['name' => 'vendor/package']));
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
        file_put_contents('composer.json', json_encode(['name' => 'vendor/sub-package']));
        file_put_contents('e.txt', 'E');
        file_put_contents('f.txt', 'F');

        ob_start();
        $return = $dist->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        chdir($cwd);

        foreach ([$directory1, $directory2] as $directory) {
            @shell_exec('rm -rf ' . escapeshellarg($directory) . ' 2>&1');
            file_exists($directory) && @shell_exec('rmdir /S /Q ' . escapeshellarg($directory) . ' 2>&1');
            @FileSystem::delete($directory);
        }

        $this->assertSame(
            "vendor/sub-package\n#[0;31mYou must be on a branch in a git repository to run this command.\n#[0m",
            $output
        );
        $this->assertFalse($return);
    }
}
