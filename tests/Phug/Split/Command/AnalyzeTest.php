<?php

namespace Phug\Test\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split;
use Phug\Split\Command\Analyze;

/**
 * @coversDefaultClass \Phug\Split\Command\Analyze
 */
class AnalyzeTest extends TestCase
{
    /**
     * @covers ::run
     * @covers ::calculatePackagesTree
     * @covers ::getPackages
     * @covers ::dumpPackagesTree
     * @covers ::mapDirectories
     * @covers ::getPackage
     * @covers ::scanDirectories
     */
    public function testRun()
    {
        $cwd = getcwd();
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $analyse = new Analyze();
        $directory = sys_get_temp_dir().'/split-test-'.mt_rand(0, 9999999);
        FileSystem::createDir($directory);
        chdir($directory);
        file_put_contents('composer.json', json_encode(['name' => 'main/package']));
        FileSystem::createDir('vendor/foo/bar');
        file_put_contents('vendor/foo/bar/composer.json', json_encode(['name' => 'does-not/count']));
        FileSystem::createDir('foo/bar');
        file_put_contents('foo/bar/composer.json', json_encode(['name' => 'a/b']));
        FileSystem::createDir('foo/hop/hip');
        file_put_contents('foo/hop/hip/composer.json', json_encode(['name' => 'hop/hip']));
        FileSystem::createDir('foo/bar/biz/baz');
        file_put_contents('foo/bar/biz/baz/composer.json', json_encode(['name' => 'biz/baz']));
        FileSystem::createDir('fuz');
        file_put_contents('fuz/composer.json', json_encode(['name' => 'main/fuz']));

        ob_start();
        $return = $analyse->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        chdir($cwd);
        @shell_exec('rm -rf '.escapeshellarg($directory).' 2>&1');
        file_exists($directory) && @shell_exec('rmdir /S /Q '.escapeshellarg($directory).' 2>&1');
        @FileSystem::delete($directory);

        $this->assertSame(implode("\n", [
            'main/package',
            '#[1;36m ├ a/b',
            '#[0m#[1;36m │  └ biz/baz',
            '#[0m#[1;36m ├ hop/hip',
            '#[0m#[1;36m └ main/fuz',
            '#[0m',
        ]), $output);
        $this->assertTrue($return);
    }

    /**
     * @covers ::calculatePackagesTree
     */
    public function testErrors()
    {
        $cli = new Split();
        $cli->setEscapeCharacter('#');
        $analyse = new Analyze();
        $analyse->directory = 'i-do-not-exist';

        ob_start();
        $return = $analyse->run($cli);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame("#[0;31mInput directory not found.\n#[0m", $output);
        $this->assertFalse($return);

        $cwd = getcwd();
        chdir(__DIR__);

        ob_start();
        $return = $analyse->run($cli);
        $output = ob_get_contents();
        ob_end_clean();
        chdir($cwd);

        $this->assertSame("#[0;31mRoot project directory should contains a composer.json file.\n#[0m", $output);
        $this->assertFalse($return);
    }
}
