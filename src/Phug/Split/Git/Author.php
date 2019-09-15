<?php

namespace Phug\Split\Git;

class Author
{
    /**
     * Git author user name.
     *
     * @var string
     */
    protected $name;

    /**
     * Git author e-mail address.
     *
     * @var string
     */
    protected $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * Get git author user name.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get git author e-mail address.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the author as string "name <email>".
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s <%s>', $this->name, $this->email);
    }
}
