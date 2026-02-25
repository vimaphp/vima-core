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

namespace Vima\Core\Mappers;

use JsonSerializable;
use ReflectionClass;

abstract class EnumMapper implements JsonSerializable
{
    /**
     * Dynamically retrieves all constants from the inheriting class.
     * * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return (new ReflectionClass($this))->getConstants();
    }

    /**
     * Optional: A static helper to get constants without instantiating.
     * * @return array<string, mixed>
     */
    public static function all(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}