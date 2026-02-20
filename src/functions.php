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

namespace Vima\Core;

/**
 * Get the DependencyContainer instance.
 *
 * @return DependencyContainer
 */
function container(): DependencyContainer
{
    return DependencyContainer::getInstance();
}

/**
 * Resolve a dependency from the container.
 *
 * @param string $id
 * @return object
 */
function resolve(string $id): object
{
    return container()->get($id);
}

/**
 * Alias for resolve().
 *
 * @param string $id
 * @return object
 */
function make(string $id): object
{
    return resolve($id);
}

/**
 * Register a binding in the container.
 *
 * @param string|object $abstract
 * @param object|null $concrete
 * @return void
 */
function register(string|object $abstract, ?object $concrete = null): void
{
    DependencyContainer::getInstance()->register($abstract, $concrete);
}

/**
 * Register multiple bindings in the container.
 *
 * @param array $dependencies
 * @return void
 */
function registerMany(array $dependencies): void
{
    DependencyContainer::getInstance()->registerMany($dependencies);
}

/**
 * Bind a singleton instance to an abstract.
 *
 * @param string $abstract
 * @param object $instance
 * @return void
 */
function singleton(string $abstract, object $instance): void
{
    container()->bind($abstract, $instance);
}
