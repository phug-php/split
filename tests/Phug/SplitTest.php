<?php

namespace Phug\Test;

use PHPUnit\Framework\TestCase;

class SplitTest extends TestCase
{
    public function testShellOutput1()
    {
        $script = sys_get_temp_dir().'/script.sh';
        file_put_contents($script, "#!/bin/sh\necho $'something\n\\\\Yoh\n\n\\'la\\' hop\\\\'");
        chmod($script, 0777);
        $output = shell_exec(escapeshellcmd($script));
        unlink($script);

        $this->assertSame("something\n\\Yoh\n\n'la' hop\\", $output);
    }

    public function testShellOutput2()
    {
        $script = sys_get_temp_dir().'/script.sh';
        file_put_contents($script, "#!/bin/sh\necho 'something\n\\\\Yoh\n\n\\'la\\' hop\\\\'");
        chmod($script, 0777);
        $output = shell_exec(escapeshellcmd($script));
        unlink($script);

        $this->assertSame("something\n\\Yoh\n\n'la' hop\\", $output);
    }
}
