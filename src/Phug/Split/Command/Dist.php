<?php

namespace Phug\Split\Command;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleCli\Command;
use SimpleCli\Options\Help;
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

    public function run(SimpleCli $cli): bool
    {
        if (!$this->calculatePackagesTree($cli)) {
            return false;
        }

        if (is_dir($this->output)) {
            $dir = new RecursiveDirectoryIterator($this->output, RecursiveDirectoryIterator::SKIP_DOTS);
            $dir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($dir as $filename => $file) {
                is_file($filename)
                    ? unlink($filename)
                    : rmdir($filename);
            }

            rmdir($this->output);
        } elseif (file_exists($this->output)) {
            unlink($this->output);
        }

        preg_match('/^\* (.+)$/m', shell_exec('git branch'), $branch);
        $branch = $branch[1];

        foreach ($this->getPackages() as $package) {
            chdir($this->directory);

            $name = $package['name'];

            $cli->writeLine("Build $name", 'light_purple');

            $data = json_decode(file_get_contents("https://repo.packagist.org/p/$name.json"), true);
            $config = $data['packages'][$name] ?? [];
            $config = $config['dev-master'] ?? next($config);

            if (!isset($config['source']) || $config['source']['type'] !== 'git') {
                $cli->writeLine("No git source found for the package $name", 'yellow');

                continue;
            }

            $url = $config['source']['url'];
            $directory = $this->output."/$name";

            $cli->writeLine("git clone $url $directory", 'light_green');
            shell_exec("git clone $url $directory");

            chdir($directory);

            $option = preg_match('/^[0-9a-f]+$/i', trim(shell_exec("git rev-parse --verify $branch 2> /dev/null") ?: '')) ? '' : ' -b';
            $cli->writeLine("git checkout$option $branch", 'light_green');
            shell_exec("git checkout$option $branch");
        }

        $cli->writeLine('Build distributed in '.$this->output, 'light_purple');

        return true;
    }
}