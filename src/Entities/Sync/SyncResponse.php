<?php

namespace Vima\Core\Entities\Sync;

final readonly class SyncResponse
{
    public function __construct(
        public Skipped $skipped,
        public bool $warn,
    ) {
    }
}
