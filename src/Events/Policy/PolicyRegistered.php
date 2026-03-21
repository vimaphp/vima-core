<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events\Policy;

use Vima\Core\Events\Event;

class PolicyRegistered extends Event
{
    public const string NAME = 'vima.policy.registered';

    public function __construct(string $abilityOrResource, string|callable $handler)
    {
        parent::__construct([
            'ability_or_resource' => $abilityOrResource,
            'handler' => $handler
        ]);
    }
}
