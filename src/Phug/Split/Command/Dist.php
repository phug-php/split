<?php

namespace Phug\Split\Command;

use Phug\Split;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleCli\SimpleCli;

/**
 * Compare master repository to sub-repositories.
 */
class Dist extends Analyze
{
    /**
     * @argument
     *
     * Output directory.
     *
     * @var string
     */
    public $output = 'dist';

    /**
     * @param Split $cli
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        return $this->distribute($cli);
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

    protected function distribute(Split $cli): bool
    {
        if (!$this->calculatePackagesTree($cli)) {
            return false;
        }

        $this->remove($this->output);

        preg_match('/^\* (.+)$/m', shell_exec('git branch'), $branch);
        $branch = $branch[1];

        foreach ($this->getPackages() as $package) {
            $this->distributePackage($cli, $package, $branch);
        }

        $cli->writeLine('Build distributed in '.$this->output, 'light_purple');

        return true;
    }

    protected function distributePackage(Split $cli, array $package, string $branch): bool
    {
        chdir($this->directory);

        $name = $package['name'];

        $cli->writeLine("Build $name", 'light_purple');

        $data = json_decode(file_get_contents("https://repo.packagist.org/p/$name.json"), true);
        $config = $data['packages'][$name] ?? [];
        $config = $config['dev-master'] ?? next($config);

        if (!isset($config['source']) || $config['source']['type'] !== 'git') {
            $cli->writeLine("No git source found for the package $name", 'yellow');

            return false;
        }

        $url = $config['source']['url'];
        $directory = $this->output."/$name";

        $cli->writeLine("git clone $url $directory", 'light_green');
        $cli->gray();
        shell_exec("git clone $url $directory");
        $cli->ungray();

        chdir($directory);

        $branchRevision = trim(shell_exec("git rev-parse --verify $branch 2> /dev/null") ?: '');
        $option = preg_match('/^[0-9a-f]+$/i', $branchRevision) ? '' : ' -b';
        $cli->writeLine("git checkout$option $branch", 'light_green');
        $cli->gray();
        shell_exec("git checkout$option $branch");
        $cli->ungray();

        return true;
    }
}