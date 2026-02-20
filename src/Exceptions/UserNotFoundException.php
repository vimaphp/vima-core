<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Exceptions;

class UserNotFoundException extends VimaException
{
    public function __construct(string|int $id)
    {
        parent::__construct("User with ID '{$id}' not found.");
    }
}
