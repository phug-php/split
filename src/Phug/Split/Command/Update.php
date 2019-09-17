<?php

namespace Phug\Split\Command;

use Exception;
use Phug\Split;
use Phug\Split\Command\Options\HashPrefix;
use Phug\Split\Git\Author;
use Phug\Split\Git\EmptyLogList;
use Phug\Split\Git\Log;

/**
 * Replay commits from the mono-repository to the sub-packages repositories.
 */
class Update extends Dist
{
    use HashPrefix;

    /**
     * @option n, no-push
     *
     * Do not push the replayed commits.
     *
     * @var bool
     */
    public $noPush = false;

    /**
     * @option m, maximum-commits-replay
     *
     * Maximum number of commits to replay.
     *
     * @var int
     */
    public $maximumCommitsReplay = 20;

    protected function getPackage(string $directory, array $data): array
    {
        return [
            'name' => $data['name'],
            'directory' => realpath($directory),
            'children' => [],
        ];
    }

    /**
     * Get list of commit to replay.
     *
     * @param Split  $cli
     * @param string $hash
     *
     * @throws Exception
     *
     * @return Log
     */
    protected function getReplayLog(Split $cli, string $hash): Log
    {
        $max = $this->maximumCommitsReplay;
        $log = $this->latest($max, '.');
        $commits = [];

        foreach ($log as $commit) {
            if ($commit->getHash() === $hash) {
                return new Log($commits);
            }

            array_unshift($commits, $commit);
        }

        $cli->writeLine('No matching commit found in the last '.$max.' commits, new step added.', 'yellow');

        return new Log([$log[0]]);
    }

    /**
     * Configure git local user to be the current author name and email.
     *
     * @param Author $author
     */
    protected function setGitCommitter(Author $author): void
    {
        $this->git('config user.name '.$this->gitEscape($author->getName()));
        $this->git('config user.email '.$this->gitEscape($author->getEmail()));
    }

    /**
     * Distribute and update the sub-package.
     *
     * @param Split $cli
     * @param array $package
     * @param string $branch
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function distributePackage(Split $cli, array $package, string $branch): bool
    {
        if (!parent::distributePackage($cli, $package, $branch)) {
            return false;
        }

        $hash = $this->getCurrentLinkedCommitHash();
        $distributionDirectory = getcwd();
        $sourceDirectory = $package['directory'];
        $localUser = file('.git/config')
            ? parse_ini_file('.git/config', true)['user'] ?? []
            : [];

        $cli->chdir($sourceDirectory);
        $this->git('stash');

        try {
            $log = $hash ? $this->getReplayLog($cli, $hash) : $this->latest(1, '.');

            foreach ($log as $commit) {
                $cli->chdir($sourceDirectory);
                $hash = $commit->getHash();
                $this->git('config advice.detachedHead false');
                $this->git("checkout -f $hash");
                rename("$distributionDirectory/.git", $this->output.'/.git.temp');
                $this->remove($distributionDirectory);
                shell_exec('cp -r . '.escapeshellarg($distributionDirectory));
                $this->remove("$distributionDirectory/.git");
                rename($this->output.'/.git.temp', "$distributionDirectory/.git");
                $cli->chdir($distributionDirectory);
                $this->setGitCommitter($commit->getCommit()->getAuthor());
                $author = $commit->getAuthor();

                $commitMessageFile = sys_get_temp_dir().'/commit-message-'.mt_rand(0, 99999999);
                file_put_contents($commitMessageFile, $commit->getMessage()."\n\n".$this->hashPrefix.$hash);
                $this->git('add .');
                $this->git('commit --file='.escapeshellarg($commitMessageFile), [
                    'author' => $author,
                    'date' => $author->getDate(),
                ], '2>&1');
                unlink($commitMessageFile);

                if (!$this->noPush) {
                    $name = $package['name'];
                    $cli->writeLine("Pushing $name\n".$this->git("push origin $branch"), 'light_cyan');
                }

                foreach (['name', 'email'] as $userConfig) {
                    $config = empty($localUser[$userConfig])
                        ? "--unset user.$userConfig"
                        : "user.$userConfig ".$this->gitEscape($localUser[$userConfig]);
                    $this->git('config '.$config);
                }
            }
        } catch (EmptyLogList $exception) {
            $cli->writeLine($package['name'].' is already up to date.', 'green');
        }

        $cli->chdir($sourceDirectory);

        $this->git("checkout -f $branch");
        $this->git('stash pop');

        return true;
    }
}
