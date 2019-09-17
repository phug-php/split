<?php

namespace Phug\Split\Command;

use Exception;
use Phug\Split;
use Phug\Split\Command\Options\GitProgram;
use Phug\Split\Command\Options\HashPrefix;
use SimpleCli\SimpleCli;

/**
 * Copy files from the mono-repository to a sub-package at the current linked revision.
 */
class Copy extends CommandBase
{
    use HashPrefix, GitProgram;

    /**
     * @argument
     *
     * Source mono-repository URL.
     *
     * @var string
     */
    public $repository;

    /**
     * @argument
     *
     * Destination directory.
     *
     * @var string
     */
    public $destination = '.';

    /**
     * @option
     *
     * Glob filters separated by commas to select the files to copy.
     *
     * @var string
     */
    public $filters = 'composer.json';

    /**
     * @param Split|SimpleCli $cli
     *
     * @throws Exception
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        if (!$this->repository) {
            return $cli->error('Please provide an input repository URL.');
        }

        $hash = $this->getCurrentLinkedCommitHash();

        if (!$hash) {
            return $cli->error('Last commit must be linked to a mono-repository commit.');
        }

        $destination = realpath($this->destination);

        if (!$destination) {
            return $cli->error('Destination directory "'.$this->destination.'" does not seem to exist.');
        }

        $workDirectory = sys_get_temp_dir().'/split-copy-'.mt_rand(0, 9999999);
        mkdir($workDirectory, 0777, true);
        $cli->chdir($workDirectory);
        $this->git('clone '.$this->repository.' .');
        $this->git("reset --hard $hash");

        foreach (explode(',', $this->filters) as $filter) {
            shell_exec('cp -r '.$filter.' '.escapeshellarg($destination.DIRECTORY_SEPARATOR));
        }

        $cli->writeLine('copy');
        $this->remove($workDirectory);

        return true;
    }
}
