<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Contracts;

/**
 * Interface EventDispatcherInterface
 * 
 * Contract for a framework-agnostic event dispatcher.
 *
 * @package Vima\Core\Contracts
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param object $event The event object to dispatch.
     * @return object The dispatched event.
     */
    public function dispatch(object $event): object;
}
