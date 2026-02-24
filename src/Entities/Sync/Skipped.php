<?php 

namespace Vima\Core\Entities\Sync;

final readonly class Skipped
{
    public function __construct(
        /**
         * @var array<string, string>
         */
        public array $roles,
        /**
         * @var array<string, string>
         */
        public array $permssions,
    )
    {
    }
}
