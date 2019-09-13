<?php

namespace Phug\Split\Command;

use SimpleCli\Command;
use SimpleCli\Options\Help;
use SimpleCli\SimpleCli;

/**
 * Compare master repository to sub-repositories.
 */
class Analyze implements Command
{
    use Help;

    /**
     * @argument
     *
     * Root project directory.
     *
     * @var string
     */
    public $directory = '.';

    /**
     * @option
     *
     * Composer file name.
     *
     * @var string
     */
    public $composerFile = 'composer.json';

    /**
     * Last AST of projects.
     *
     * @var array
     */
    protected $ast;

    public function run(SimpleCli $cli): bool
    {
        return $this->calculatePackagesTree($cli) &&
            $this->dumpPackagesTree($cli, $this->ast);
    }

    protected function calculatePackagesTree(SimpleCli $cli): bool
    {
        chdir($this->directory);

        if (!file_exists($this->composerFile)) {
            $cli->write('Root project directory should contains a '.$this->composerFile.' file.', 'red');

            return false;
        }

        $data = json_decode(file_get_contents($this->composerFile), true);
        $vendorDirectory = ($data['config'] ?? [])['vendor-dir'] ?? 'vendor';

        $cli->writeLine($data['name']);
        $this->ast = $this->mapDirectories('.', function (string $path, string $element) use ($vendorDirectory) {
            if ($element === $vendorDirectory) {
                return;
            }

            return $this->scanDirectories($path);
        });

        return true;
    }

    protected function dumpPackagesTree(SimpleCli $cli, iterable $ast, int $level = 0): bool
    {
        $packages = is_array($ast) ? $ast : iterator_to_array($ast);
        $count = count($packages);

        foreach ($packages as $index => $package) {
            $symbol = $index === $count - 1 ? '└' : '├';
            $cli->writeLine(str_repeat('   ', $level).' '.$symbol.' '.$package['name'], 'light_cyan');
            $this->dumpPackagesTree($cli, $package['children']);
        }

        return true;
    }

    protected function mapDirectories(string $directory, callable $callback): iterable
    {
        foreach (scandir($directory) as $element) {
            if (substr($element, 0, 1) === '.') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$element;

            if (is_dir($path)) {
                $result = $callback($path, $element);

                if ($result !== null) {
                    foreach ($result as $item) {
                        yield $item;
                    }
                }
            }
        }
    }

    protected function scanDirectories(string $directory): iterable
    {
        $mainPackage = null;
        $composerPath = $directory.DIRECTORY_SEPARATOR.$this->composerFile;

        if (file_exists($composerPath)) {
            $data = json_decode(file_get_contents($composerPath), true);
            $mainPackage = [
                'name' => $data['name'],
                'children' => [],
            ];
        }

        foreach ($this->mapDirectories($directory, function (string $path) {
            return $this->scanDirectories($path);
        }) as $package) {
            if ($mainPackage) {
                $mainPackage['children'][] = $package;

                continue;
            }

            yield $package;
        }

        if ($mainPackage) {
            yield $mainPackage;
        }
    }
}