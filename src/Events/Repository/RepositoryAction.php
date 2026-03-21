<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events\Repository;

use Vima\Core\Events\Event;

class RepositoryAction extends Event
{
    public const string NAME = 'vima.repository.action';
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_DELETED_ALL = 'deleted_all';

    public function __construct(public readonly string $action, public readonly string $entityClass, public readonly mixed $payload = null)
    {
        parent::__construct([
            'action' => $action,
            'entity' => $entityClass,
            'payload' => $payload
        ]);
    }
}
