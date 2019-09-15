<?php

namespace Phug\Split\Command;

use Phug\Split;
use SimpleCli\Command;
use SimpleCli\Options\Help;
use SimpleCli\SimpleCli;
use Traversable;

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
     * @var array[]
     */
    protected $ast;

    /**
     * @param Split $cli
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        $this->directory = realpath($this->directory);

        if (!$this->directory) {
            return $cli->error('Input directory not found.');
        }

        return $this->calculatePackagesTree($cli) &&
            $this->dumpPackagesTree($cli, $this->getPackages());
    }

    protected function calculatePackagesTree(Split $cli): bool
    {
        chdir($this->directory);

        if (!file_exists($this->composerFile)) {
            return $cli->error('Root project directory should contains a '.$this->composerFile.' file.');
        }

        $data = json_decode(file_get_contents($this->composerFile), true);
        $vendorDirectory = ($data['config'] ?? [])['vendor-dir'] ?? 'vendor';

        $cli->writeLine($data['name']);
        $this->ast = $this->mapDirectories('.', function (string $path, string $element) use ($vendorDirectory) {
            if ($element === $vendorDirectory) {
                return null;
            }

            return $this->scanDirectories($path);
        });

        return true;
    }

    protected function getPackages(): iterable
    {
        if ($this->ast instanceof Traversable) {
            $this->ast = iterator_to_array($this->ast);
        }

        return $this->ast;
    }

    protected function dumpPackagesTree(Split $cli, iterable $packages, int $level = 0): bool
    {
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

    protected function getPackage(string $directory, array $data): array
    {
        return [
            'name' => $data['name'],
            'children' => [],
        ];
    }

    protected function scanDirectories(string $directory): iterable
    {
        $mainPackage = null;
        $composerPath = $directory.DIRECTORY_SEPARATOR.$this->composerFile;

        if (file_exists($composerPath)) {
            $data = json_decode(file_get_contents($composerPath), true);
            $mainPackage = $this->getPackage($directory, $data);
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