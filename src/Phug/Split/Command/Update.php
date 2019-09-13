<?php

namespace Phug\Split\Command;

use Phug\Split;

/**
 * Compare master repository to sub-repositories.
 */
class Update extends Dist
{
    /**
     * @option
     *
     * Do not push the replayed commits.
     *
     * @var bool
     */
    public $noPush = false;

    protected function getPackage(string $directory, array $data): array
    {
        return [
            'name' => $data['name'],
            'directory' => realpath($directory),
            'children' => [],
        ];
    }

    protected function distributePackage(Split $cli, array $package, string $branch): bool
    {
        if (!parent::distributePackage($cli, $package, $branch)) {
            return false;
        }

        $distributionDirectory = getcwd();
        $sourceDirectory = $package['directory'];

        chdir($sourceDirectory);
        shell_exec('git stash');
        rename("$distributionDirectory/.git", $this->output.'/.git.temp');
        $this->remove($distributionDirectory);
        shell_exec('cp -r . '.escapeshellarg($distributionDirectory));
        $this->remove("$distributionDirectory/.git");
        rename($this->output.'/.git.temp', "$distributionDirectory/.git");
        shell_exec('git stash pop');

        chdir($distributionDirectory);
        shell_exec('git add .');
        $cli->writeLine(shell_exec('git status'), 'green');

        if (!$this->noPush) {
            $cli->writeLine('Push '.$package['name']);
        }

        return true;
    }
}