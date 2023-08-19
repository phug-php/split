<?php

namespace Phug\Split\Command;

use Exception;
use Phug\Split;
use Phug\Split\Command\Options\HashPrefix;
use Phug\Split\Git\Author;
use Phug\Split\Git\Commit;
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
     * Cache for "user" property from .git/config
     *
     * @var array|null
     */
    private $localUser = null;

    /**
     * @option m, maximum-commits-replay
     *
     * Maximum number of commits to replay.
     *
     * @var int|numeric-string @phan-suppress-current-line PhanUnextractableAnnotationSuffix
     */
    public $maximumCommitsReplay = 20; // @phan-suppress-current-line PhanUndeclaredTypeProperty

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
        $log = $this->latest((int) $max, '.');
        $commits = [];

        foreach ($log as /* @var Commit $commit */ $commit) {
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
     * Copy the current directory recursively to a given destination path.
     *
     * @suppressWarnings(PHPMD.LongVariableName)
     *
     * @param string $destination
     */
    protected function copyCurrentDirectory(string $destination): void
    {
        shell_exec('cp -r . '.escapeshellarg($destination).' 2>&1');

        // @codeCoverageIgnoreStart
        if (!file_exists($destination)) {
            shell_exec('xcopy . '.escapeshellarg($destination).' /e /i /h');
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get GIT local user config (.git/config file settings).
     *
     * @return array
     */
    protected function getLocalUserConfig(): array
    {
        if ($this->localUser === null) {
            $this->localUser = file_exists('.git/config')
                ? parse_ini_file('.git/config', true)['user'] ?? []
                : [];
        }

        return $this->localUser;
    }

    /**
     * Set a git config.
     *
     * @param string $name
     * @param string|null $value
     *
     * @return string|null
     */
    protected function setGitConfig(string $name, ?string $value): ?string
    {
        $config = $value === null
            ? "--unset $name"
            : "$name ".$this->gitEscape($value);

        return $this->git('config '.$config);
    }

    /**
     * Apply the given commit in the current repository.
     *
     * @param Split  $cli
     * @param array  $package
     * @param Commit $commit
     * @param string $distributionDirectory
     * @param string $branch
     *
     * @suppressWarnings(PHPMD.LongVariableName)
     */
    protected function cherryPickCommit(
        Split $cli,
        array $package,
        Commit $commit,
        string $distributionDirectory,
        string $branch
    ): void {
        $hash = $commit->getHash();
        $this->git('config advice.detachedHead false');
        $this->git("checkout -f $hash", [], '2>&1');
        rename("$distributionDirectory/.git", $this->output.'/.git.temp');
        $this->remove($distributionDirectory);
        $this->copyCurrentDirectory($distributionDirectory);

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
            $push = (string) $this->git("push origin $branch", [], '2>&1');
            $cli->writeLine("Pushing $name\n".$push, strpos($push, 'error:') === false ? 'light_cyan' : 'red');
        }

        $localUser = $this->getLocalUserConfig();

        foreach (['name', 'email'] as $userConfig) {
            $this->setGitConfig("user.$userConfig", $localUser[$userConfig] ?? null);
        }
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
     * @suppressWarnings(PHPMD.LongVariableName)
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

        $cli->chdir($sourceDirectory);
        $this->git('stash', [], '2>&1');

        try {
            $log = $hash ? $this->getReplayLog($cli, $hash) : $this->latest(1, '.');

            foreach ($log as $commit) {
                $cli->chdir($sourceDirectory);
                $this->cherryPickCommit($cli, $package, $commit, $distributionDirectory, $branch);
            }
        } catch (EmptyLogList $exception) {
            $cli->writeLine($package['name'].' is already up to date.', 'green');
        }

        $cli->chdir($sourceDirectory);

        $this->git("checkout -f $branch", [], '2>&1');
        $this->git('stash pop', [], '2>&1');

        return true;
    }
}
