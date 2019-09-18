<?php

namespace Phug\Test;

use PHPUnit\Framework\TestCase;
use Phug\Split;

/**
 * @coversDefaultClass \Phug\Split
 */
class SplitTest extends TestCase
{
    /**
     * @covers ::getCommands
     */
    public function testGetCommands()
    {
        $split = new Split();
        $commands = array_values(array_filter(array_map(static function (string $file) {
            $tokens = token_get_all(file_get_contents($file));
            $prev = null;

            foreach ($tokens as $index => $token) {
                if (is_array($token) && $token[0] === T_CLASS) {
                    if ($prev && $prev[0] === T_ABSTRACT) {
                        return null;
                    }

                    for ($i = $index + 1; isset($tokens[$i]) && $tokens[$i] !== '{'; $i++) {
                        if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                            return 'Phug\\Split\\Command\\'.$tokens[$i][1];
                        }
                    }
                }

                if (is_array($token) && $token[0] !== T_WHITESPACE) {
                    $prev = $token;
                }
            }

            return $file;
        }, glob(__DIR__.'/../../src/Phug/Split/Command/*.php')), static function ($class) {
            return $class !== null;
        }));

        $this->assertSame($commands, $split->getCommands());
    }

    /**
     * @covers ::gray
     */
    public function testGray()
    {
        $split = new Split();
        $split->setEscapeCharacter('[escape]');
        ob_start();
        $split->gray();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame('[escape][1;30m', $output);
    }

    /**
     * @covers ::discolor
     */
    public function testDiscolor()
    {
        $split = new Split();
        $split->setEscapeCharacter('[escape]');
        ob_start();
        $split->discolor();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame('[escape][0m', $output);
    }

    /**
     * @covers ::error
     */
    public function testError()
    {
        $split = new Split();
        $split->setEscapeCharacter('[escape]');
        ob_start();
        $this->assertFalse($split->error('Foobar'));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame("[escape][0;31mFoobar\n[escape][0m", $output);
    }

    /**
     * @covers ::warning
     */
    public function testWarning()
    {
        $split = new Split();
        $split->setEscapeCharacter('[escape]');
        ob_start();
        $this->assertFalse($split->warning('Foobar'));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame("[escape][1;33mFoobar\n[escape][0m", $output);
    }

    /**
     * @covers ::chdir
     */
    public function testChdir()
    {
        $cwd = getcwd();
        chdir(__DIR__.'/../../src');
        $split = new Split();
        $split->setEscapeCharacter('[escape]');
        ob_start();
        $this->assertTrue($split->chdir(__DIR__));
        $this->assertTrue($split->chdir(__DIR__));
        $directory = getcwd();
        $output = ob_get_contents();
        ob_end_clean();
        chdir($cwd);

        $this->assertSame(__DIR__, $directory);
        $this->assertSame("[escape][1;34mcd $directory\n[escape][0m", $output);
    }
}
