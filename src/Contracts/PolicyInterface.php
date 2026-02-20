<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Contracts;

/**
 * Interface PolicyInterface
 * 
 * Every class-based policy must implement this interface to provide
 * the resource class it handles.
 */
interface PolicyInterface
{
    /**
     * Return the fully qualified class name of the resource this policy handles.
     *
     * @return string
     */
    public static function getResource(): string;
}
