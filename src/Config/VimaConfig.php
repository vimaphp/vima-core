<?php

declare(strict_types=1);

namespace Vima\Core\Config;

class VimaConfig
{
    public function __construct(
        public Tables $tables,
        public Columns $columns,
        public Models $models,
        public Setup $setup,
        public UserMethods $userMethods,
    ) {
    }
}
