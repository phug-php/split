<?php

namespace Phug\Split\Command;

use Phug\Split;
use Phug\Split\Command\Options\GitProgram;
use SimpleCli\Options\Verbose;
use SimpleCli\SimpleCli;

/**
 * Create a distribution directory with each sub-package in it.
 */
class Dist extends Analyze
{
    use Verbose, GitProgram;

    /**
     * @argument
     *
     * Output directory.
     *
     * @var string
     */
    public $output = 'dist';

    /**
     * @option git-credentials
     *
     * Git credentials.
     *
     * @var string
     */
    public $gitCredentials = '';

    /**
     * @option
     *
     * Composer API (by default repo.packagist.org URL used).
     * %s pattern in the API is replaced by the package full name.
     * It can be any local or remote path file_get_contents() is able to get.
     *
     * @var string
     */
    public $api = 'https://repo.packagist.org/p/%s.json';

    /**
     * @param Split|SimpleCli $cli
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        return $this->distribute($cli);
    }

    protected function info(Split $cli, string $message): void
    {
        if ($this->verbose) {
            $cli->writeLine($message, 'brown');
        }
    }

    protected function distribute(Split $cli): bool
    {
        if (!$this->calculatePackagesTree($cli)) {
            return false;
        }

        $this->remove($this->output);
        $cli->chdir($this->directory);
        @mkdir($this->output, 0777, true);
        $this->output = @realpath($this->output);

        if (!$this->output) {
            return $cli->error('Unable to create output directory.');
        }

        if (!preg_match('/^\* (.+)$/m', $this->git('branch', [], '2>&1'), $branch)) {
            return $cli->error('You must be on a branch in a git repository to run this command.');
        }

        $branch = $branch[1];

        if (substr($branch, 0, 18) === '(HEAD detached at ') {
            $branch = trim(explode("\n", $this->git('describe --contains --all HEAD', [], '2>&1'))[0]);
        }

        foreach ($this->getPackages() as $package) {
            $this->distributePackage($cli, $package, $branch);
        }

        $cli->writeLine('Build distributed in '.$this->output, 'light_purple');

        return true;
    }

    protected function distributePackage(Split $cli, array $package, string $branch): bool
    {
        $cli->chdir($this->directory);

        $name = $package['name'];

        $data = json_decode(file_get_contents(sprintf($this->api, $name)), true);
        $config = $data['packages'][$name] ?? [];
        $config = $config['dev-master'] ?? next($config);

        if (!isset($config['source']) || $config['source']['type'] !== 'git') {
            return $cli->error("No git source found for the package $name");
        }

        $cli->writeLine("Build $name", 'light_purple');

        $url = $config['source']['url'];
        $directory = $this->output."/$name";

        $cli->writeLine("git clone $url $directory", 'light_green');

        if (strlen($this->gitCredentials)) {
            [$protocol, $url] = explode('://', $url, 2);
            $url = $protocol.'://'.$this->gitCredentials.'@'.$url;
        }

        $cli->gray();
        $cli->write($this->git("clone -c advice.detachedHead=false $url $directory", [], '2>&1'));
        $cli->discolor();

        $cli->chdir($directory);

        $this->info($cli, "git rev-parse --verify origin/$branch");
        $branchRevision = trim($this->git("rev-parse --verify origin/$branch", [], '2>&1') ?: '');
        $this->info($cli, ' => '.($branchRevision === '' ? 'no revision' : $branchRevision));
        $option = preg_match('/^[0-9a-f]+$/i', $branchRevision) ? '' : ' -b';
        $cli->writeLine("git checkout$option $branch", 'light_green');
        $this->git("checkout$option $branch", [], '2>&1');

        return true;
    }
}
