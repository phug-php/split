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
}
