<?php

declare(strict_types=1);

namespace Vima\Core\Contracts;

interface PolicyRepositoryInterface
{
    public function define(string $action, callable $rule): void;

    public function get(string $action): ?callable;

    public function all(): array;
}
