<?php

namespace Phug\Split\Command;

use Phug\Split;
use Phug\Split\UnableToListDirectoryItems;
use SimpleCli\SimpleCli;
use Traversable;

/**
 * Display the tree of nested packages in the mono-repository.
 */
class Analyze extends CommandBase
{
    /**
     * @argument
     *
     * Root project directory.
     *
     * @var string
     */
    public $directory = '.';

    /**
     * @option c, composer-file
     *
     * Composer file name.
     *
     * @var string
     */
    public $composerFile = 'composer.json';

    /**
     * Last AST of projects.
     *
     * @var ?iterable<array>
     */
    protected $ast;

    /**
     * @param Split|SimpleCli $cli
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        $cli = $this->getSplitCli($cli);

        return $this->calculatePackagesTree($cli) &&
            $this->dumpPackagesTree($cli, $this->getPackages());
    }

    protected function calculatePackagesTree(Split $cli): bool
    {
        /** @psalm-var truthy-string|false $directory */
        $directory = realpath($this->directory);

        if (!$directory) {
            return $cli->error('Input directory not found.');
        }

        $this->directory = $directory;

        $cli->chdir($this->directory);

        if (!file_exists($this->composerFile)) {
            return $cli->error('Root project directory should contains a '.$this->composerFile.' file.');
        }

        $data = (array) json_decode((string) file_get_contents($this->composerFile), true);
        $vendorDirectory = ($data['config'] ?? [])['vendor-dir'] ?? 'vendor';

        $cli->writeLine((string) $data['name']);
        $this->ast = $this->mapDirectories('.', function (string $path, string $element) use ($vendorDirectory) {
            if ($element === $vendorDirectory) {
                return null;
            }

            return $this->scanDirectories($path);
        });

        return true;
    }

    /** @psalm-suppress RiskyTruthyFalsyComparison */
    protected function getPackages(): iterable
    {
        if ($this->ast instanceof Traversable) {
            $this->ast = iterator_to_array($this->ast);
        }

        return $this->ast ?: [];
    }

    protected function dumpPackagesTree(Split $cli, iterable $packages, int $level = 0): bool
    {
        $count = is_countable($packages) ? count($packages) : 0;

        foreach ($packages as $index => $package) {
            $symbol = $index === $count - 1 ? '└' : '├';
            $cli->writeLine(str_repeat(' │ ', $level).' '.$symbol.' '.$package['name'], 'light_cyan');
            $this->dumpPackagesTree($cli, $package['children'], $level + 1);
        }

        return true;
    }

    protected function mapDirectories(string $directory, callable $callback): iterable
    {
        $elements = scandir($directory);

        if ($elements === false) {
            throw new UnableToListDirectoryItems($directory);
        }

        foreach ($elements as $element) {
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

    /** @return array{name: string, directory: string, children: array} */
    protected function getPackage(string $directory, array $data): array
    {
        return [
            'name' => $data['name'],
            'directory' => $directory,
            'children' => [],
        ];
    }

    protected function scanDirectories(string $directory): iterable
    {
        $mainPackage = null;
        $composerPath = $directory.DIRECTORY_SEPARATOR.$this->composerFile;

        if (file_exists($composerPath)) {
            $data = json_decode((string) file_get_contents($composerPath), true);
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
