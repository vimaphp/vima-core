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

namespace Vima\Core\Tests\Fixtures;

use Vima\Core\Contracts\EventDispatcherInterface;

class MockEventDispatcher implements EventDispatcherInterface
{
    public array $dispatched = [];

    public function dispatch(object $event): object
    {
        $this->dispatched[] = $event;
        return $event;
    }
}
