<?php

namespace Phug\Split\Command;

use Exception;
use Phug\Split\Git\Commit;
use Phug\Split\Git\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleCli\Command;
use SimpleCli\Options\Help;

abstract class CommandBase implements Command
{
    use Help;

    /**
     * Escape a value for git command argument or option.
     *
     * @param string $value
     *
     * @return string
     */
    protected function gitEscape(string $value): string
    {
        return "$'".addcslashes($value, "'\\")."'";
    }

    /**
     * Get a command using git program.
     *
     * @param string      $command  Git command and mandatory arguments
     * @param array       $options  CLI git command options
     * @param string|null $redirect redirection suffix (like '2>&1')
     *
     * @return string
     */
    protected function getGitCommand(string $command, array $options = [], string $redirect = null): string
    {
        foreach ($options as $name => $value) {
            $command .= ' --'.$name.'='.$this->gitEscape($value);
        }

        return $this->gitProgram.' '.$command.($redirect ? ' '.$redirect : '');
    }

    /**
     * Execute a command using git program.
     *
     * @param string      $command  Git command and mandatory arguments
     * @param array       $options  CLI git command options
     * @param string|null $redirect redirection suffix (like '2>&1')
     *
     * @return string|null
     */
    protected function git(string $command, array $options = [], string $redirect = null): ?string
    {
        $command = $this->getGitCommand($command, $options, $redirect);

        if (strpos($command, '$\'') === false) {
            return shell_exec($command);
        }

        $script = sys_get_temp_dir().'/script.sh';
        file_put_contents($script, "#!/bin/sh\n".$command);
        chmod($script, 0777);
        $output = shell_exec(escapeshellcmd($script).($redirect ? ' '.$redirect : ''));
        unlink($script);

        return $output;
    }

    /**
     * Return given count of latest commits as Log instance (collection of Commit instances).
     *
     * @param int    $count
     * @param string $directory
     *
     * @throws Exception
     *
     * @return Log|Commit[]
     */
    protected function latest($count = 1, string $directory = ''): Log
    {
        return Log::fromGitLogString($this->git("log --pretty=fuller --max-count=$count $directory"));
    }

    /**
     * Return the last commit.
     *
     * @param string $directory
     *
     * @throws Exception
     *
     * @return Commit
     */
    protected function last(string $directory = ''): Commit
    {
        return $this->latest(1, $directory)[0];
    }

    /**
     * Get the hash of the linked commit in the mono-repository for the last sub-package commit.
     *
     * @return string|null
     *
     * @throws Exception
     */
    protected function getCurrentLinkedCommitHash(): ?string
    {
        return $this->last()->findInMessage('/^'.preg_quote($this->hashPrefix).'(.+)$/m');
    }

    /**
     * Remove a file or a directory even if not empty.
     *
     * @param string $fileOrDirectory
     *
     * @return bool
     */
    protected function remove(string $fileOrDirectory): bool
    {
        if (is_dir($fileOrDirectory)) {
            $dir = new RecursiveDirectoryIterator($fileOrDirectory, RecursiveDirectoryIterator::SKIP_DOTS);
            $dir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($dir as $filename => $file) {
                is_file($filename)
                    ? unlink($filename)
                    : rmdir($filename);
            }

            return rmdir($fileOrDirectory);
        }

        if (file_exists($fileOrDirectory)) {
            return unlink($fileOrDirectory);
        }

        return false;
    }
}