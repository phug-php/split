<?php

namespace Phug\Test\Split\Command;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase;
use Phug\Split\Command\CommandBase;
use Phug\Split\Command\Copy;
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
     * @covers ::gitEscape
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
        @shell_exec('rmdir /S /Q '.escapeshellarg($directory));
        @FileSystem::delete($directory);

        $log = explode("\n\n", $log);
        $log = trim(preg_replace('/^ {4}/m', '', $log[1]));

        $this->assertSame($string, $log);
    }
}