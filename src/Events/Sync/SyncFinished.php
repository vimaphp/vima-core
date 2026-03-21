<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events\Sync;

use Vima\Core\Events\Event;
use Vima\Core\Entities\Sync\SyncResponse;

class SyncFinished extends Event
{
    public const string NAME = 'vima.sync.finished';

    public function __construct(SyncResponse $response)
    {
        parent::__construct([
            'response' => $response
        ]);
    }
}
