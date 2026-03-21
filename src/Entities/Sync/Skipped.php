<?php 

namespace Vima\Core\Entities\Sync;

final class Skipped
{
    public function __construct(
        /**
         * @var array<string, string>
         */
        public readonly array $roles,
        /**
         * @var array<string, string>
         */
        public readonly array $permissions,
    )
    {
    }
}
