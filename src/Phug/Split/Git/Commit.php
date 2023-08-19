<?php

namespace Phug\Split\Git;

use Exception;

class Commit
{
    /**
     * Hash of the commit.
     *
     * @var string
     */
    protected $hash;

    /**
     * Author date, name and e-mail address.
     *
     * @var Act
     */
    protected $author;

    /**
     * Committer date, name and e-mail address.
     *
     * @var Act
     */
    protected $commit;

    /**
     * Commit message.
     *
     * @var string
     */
    protected $message;

    public function __construct(string $hash, Act $author, Act $commit, string $message)
    {
        $this->hash = $hash;
        $this->author = $author;
        $this->commit = $commit;
        $this->message = $message;
    }

    /**
     * Create a Commit instance from a git log commit string.
     *
     * @param string $log raw string from git log command.
     *
     * @throws InvalidGitLogStringException|Exception
     *
     * @return self
     */
    public static function fromGitLogString(string $log): self
    {
        $log = str_replace(["\r\n", "\r"], "\n", $log);

        if (!preg_match('/^'.implode('\n', [
            'commit (?<hash>\S+)[\S\s]*',
            'Author:     (?<authorName>[^<]+)<(?<authorEmail>[^>]+)>(?: .*)?',
            'AuthorDate: (?<authorDate>.+)',
            'Commit:     (?<commitName>[^<]+)<(?<commitEmail>[^>]+)>(?: .*)?',
            'CommitDate: (?<commitDate>.+)',
            '(?:.+\n)*\n    (?<message>[\S\s]+)',
        ]).'$/', $log, $matches)) {
            throw new InvalidGitLogStringException($log);
        }

        return new self(
            $matches['hash'],
            new Act(
                new Author(trim($matches['authorName']), trim($matches['authorEmail'])),
                new Date($matches['authorDate']),
            ),
            new Act(
                new Author(trim($matches['commitName']), trim($matches['commitEmail'])),
                new Date($matches['commitDate']),
            ),
            preg_replace('/^ {4}/m', '', trim($matches['message'])),
        );
    }

    /**
     * Hash of the commit.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get author date, name and e-mail address.
     *
     * @return Act
     */
    public function getAuthor(): Act
    {
        return $this->author;
    }

    /**
     * Get committer date, name and e-mail address.
     *
     * @return Act
     */
    public function getCommit(): Act
    {
        return $this->commit;
    }

    /**
     * Get commit message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Return preg_match first captured parenthesis in the commit message by a given regular expression.
     *
     * @param non-empty-string $regExp
     *
     * @return string|null
     */
    public function findInMessage(string $regExp): ?string
    {
        return preg_match($regExp, $this->message, $matches)
            ? $matches[1] ?? null
            : null;
    }
}
