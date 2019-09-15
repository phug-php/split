<?php

namespace Phug\Split\Command;

use Phug\Split;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleCli\Options\Verbose;
use SimpleCli\SimpleCli;

/**
 * Compare master repository to sub-repositories.
 */
class Dist extends Analyze
{
    use Verbose;

    /**
     * @argument
     *
     * Output directory.
     *
     * @var string
     */
    public $output = 'dist';

    /**
     * @option g, git-program
     *
     * Git binary program path.
     *
     * @var string
     */
    public $gitProgram = 'git';

    /**
     * @param Split $cli
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

    protected function gitEscape(string $value): string
    {
        return "$'".addcslashes($value, "'\\")."'";
    }

    protected function getGitCommand(string $command, array $options = [], string $redirect = null): string
    {
        foreach ($options as $name => $value) {
            $command .= ' --'.$name.'='.$this->gitEscape($value);
        }

        return $this->gitProgram.' '.$command.($redirect ? ' '.$redirect : '');
    }

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

        if (!preg_match('/^\* (.+)$/m', $this->git('branch'), $branch)) {
            return $cli->error('You must be on a branch to run this command.');
        }

        $branch = $branch[1];

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

        $cli->writeLine("Build $name", 'light_purple');

        $data = json_decode(file_get_contents("https://repo.packagist.org/p/$name.json"), true);
        $config = $data['packages'][$name] ?? [];
        $config = $config['dev-master'] ?? next($config);

        if (!isset($config['source']) || $config['source']['type'] !== 'git') {
            $cli->warning("No git source found for the package $name");
        }

        $url = $config['source']['url'];
        $directory = $this->output."/$name";

        $cli->writeLine("git clone $url $directory", 'light_green');
        $cli->gray();
        $this->git("clone $url $directory");
        $cli->ungray();

        $cli->chdir($directory);

        $this->info($cli, "git rev-parse --verify origin/$branch");
        $branchRevision = trim($this->git("rev-parse --verify origin/$branch", [], '2>&1') ?: '');
        $this->info($cli, ' => '.($branchRevision === '' ? 'no revision' : $branchRevision));
        $option = preg_match('/^[0-9a-f]+$/i', $branchRevision) ? '' : ' -b';
        $cli->writeLine("git checkout$option $branch", 'light_green');
        $cli->gray();
        $this->git("checkout$option $branch");
        $cli->ungray();

        return true;
    }
}