<?php

namespace Phug\Split\Command;

use Phug\Split;
use SimpleCli\Command;
use SimpleCli\Options\Help;
use SimpleCli\SimpleCli;

/**
 * Compare master repository to sub-repositories.
 */
class Compare implements Command
{
    use Help;

    /**
     * @param Split $cli
     *
     * @return bool
     */
    public function run(SimpleCli $cli): bool
    {
        if (!file_exists('composer.json')) {

        }

        var_dump(glob('*/composer.json'));

        return true;
    }
}