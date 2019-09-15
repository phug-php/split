<?php

namespace Phug;

use Phug\Split\Command\Analyze;
use Phug\Split\Command\Compare;
use Phug\Split\Command\Dist;
use Phug\Split\Command\Update;
use SimpleCli\SimpleCli;

class Split extends SimpleCli
{
    public function getCommands(): array
    {
        return [
            Analyze::class,
            Dist::class,
            Update::class,
        ];
    }

    public function gray()
    {
        $this->write($this->getColorCode('dark_gray'));
    }

    public function ungray()
    {
        $this->write($this->escapeCharacter.'[0m');
    }

    public function error(string $error): bool
    {
        $this->writeLine($error, 'red');

        return false;
    }

    public function warning(string $error): bool
    {
        $this->writeLine($error, 'yellow');

        return false;
    }

    public function chdir($directory): bool
    {
        if ($directory !== getcwd()) {
            $this->writeLine("cd $directory", 'light_blue');

            return chdir($directory);
        }

        return true;
    }
}
