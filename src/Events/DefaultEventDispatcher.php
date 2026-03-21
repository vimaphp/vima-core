<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events;

use Vima\Core\Contracts\EventDispatcherInterface;

/**
 * Class DefaultEventDispatcher
 * 
 * A basic implementation that doesn't do anything or potentially logs it.
 * This can be the default if no actual framework dispatcher is injected.
 *
 * @package Vima\Core\Events
 */
class DefaultEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array
     */
    protected array $events = [];

    /**
     * Dispatch an event.
     *
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        // For debugging purposes in tests, we can keep track of events.
        $this->events[] = $event;
        return $event;
    }

    /**
     * Get all dispatched events.
     *
     * @return array
     */
    public function getDispatchedEvents(): array
    {
        return $this->events;
    }

    /**
     * Clear dispatched events list.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->events = [];
    }
}
