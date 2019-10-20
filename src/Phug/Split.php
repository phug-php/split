<?php

namespace Phug;

use Phug\Split\Command\Analyze;
use Phug\Split\Command\Copy;
use Phug\Split\Command\Dist;
use Phug\Split\Command\Update;
use SimpleCli\SimpleCli;

/**
 * Class Split: main CLI program to manage split packages in a mono-repository.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Split extends SimpleCli
{
    public function getCommands(): array
    {
        return [
            Analyze::class,
            Copy::class,
            Dist::class,
            Update::class,
        ];
    }

    public function gray(): void
    {
        $this->write($this->getColorCode('dark_gray'));
    }

    public function discolor(): void
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

    public function chdir(string $directory): bool
    {
        if ($directory !== getcwd()) {
            $this->writeLine("cd $directory", 'light_blue');

            return chdir($directory);
        }

        return true;
    }
}
