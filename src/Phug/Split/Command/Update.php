<?php

namespace Phug\Split\Command;

use Exception;
use Phug\Split;
use Phug\Split\Git\Author;
use Phug\Split\Git\Commit;
use Phug\Split\Git\EmptyLogList;
use Phug\Split\Git\Log;

/**
 * Compare master repository to sub-repositories.
 */
class Update extends Dist
{
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

    /**
     * @option p, hash-prefix
     *
     * Prefix to put before a commit hash to link a split commit to the original commit.
     *
     * @var string
     */
    public $hashPrefix = 'split: ';

    protected function getPackage(string $directory, array $data): array
    {
        return [
            'name' => $data['name'],
            'directory' => realpath($directory),
            'children' => [],
        ];
    }

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

        $commit = $this->last();
        $hash = $commit->findInMessage('/^'.preg_quote($this->hashPrefix).'(.+)$/m');

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

                $this->git('add .');
                $this->git('commit', [
                    'message' => $commit->getMessage()."\n\n".$this->hashPrefix.$hash,
                    'author' => $author,
                    'date' => $author->getDate(),
                ], '2>&1');

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
