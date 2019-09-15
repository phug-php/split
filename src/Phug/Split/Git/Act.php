<?php

namespace Phug\Split\Git;

class Act
{
    /**
     * Author of the act (code committing/authoring).
     *
     * @var Author
     */
    protected $author;

    /**
     * Date of the act (code committing/authoring).
     *
     * @var Date
     */
    protected $date;

    public function __construct(Author $author, Date $date)
    {
        $this->author = $author;
        $this->date = $date;
    }

    /**
     * Get author of the act (code committing/authoring).
     *
     * @return Author
     */
    public function getAuthor(): Author
    {
        return $this->author;
    }

    /**
     * Get date of the act (code committing/authoring).
     *
     * @return Date
     */
    public function getDate(): Date
    {
        return $this->date;
    }

    /**
     * Return the author of the act as string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getAuthor();
    }
}
