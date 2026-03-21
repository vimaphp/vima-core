<?php

namespace Vima\Core\Entities\Sync;

final class SyncResponse
{
    public function __construct(
        public readonly Skipped $skipped,
        public readonly bool $warn,
    ) {
    }
}
