<?php

namespace Phug\Split\Git;

use ArrayAccess;
use ArrayObject;
use Exception;
use IteratorAggregate;
use Traversable;

class Log implements IteratorAggregate, ArrayAccess
{
    /**
     * List of git commits.
     *
     * @var Commit[]
     */
    protected $commits = [];

    /**
     * Retrieve an external iterator.
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new ArrayObject($this->commits);
    }

    public function __construct(array $commits)
    {
        if (!count($commits)) {
            throw new EmptyLogList();
        }

        foreach ($commits as $commit) {
            if (!($commit instanceof Commit)) {
                $type = gettype($commit);

                if ($type === 'object') {
                    $type = get_class($commit);
                }

                throw new InvalidGitLogUnit($type);
            }
        }

        $this->commits = $commits;
    }

    /**
     * Create a Log instance (list of Commit) from a git log commit string.
     *
     * @param string $log raw string from git log command.
     *
     * @throws InvalidGitLogStringException|Exception
     *
     * @return self
     */
    public static function fromGitLogString(string $log): self
    {
        $commitLogs = preg_split('/[\n\r](?=commit )/', $log);

        return new self(array_map(static function (string $commitLog) {
            return Commit::fromGitLogString($commitLog);
        }, $commitLogs));
    }

    /**
     * Whether a offset exists.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->commits[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset): Commit
    {
        return $this->commits[$offset];
    }

    /**
     * Change a commit of the list is forbidden.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @throws ImmutableLogException
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        throw new ImmutableLogException();
    }

    /**
     * Remove a commit of the list is forbidden.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     *
     * @throws ImmutableLogException
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        throw new ImmutableLogException();
    }
}
