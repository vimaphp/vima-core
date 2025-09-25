<?php

namespace Vima\Core\Storage\InMemory;

use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Contracts\UserInterface;

class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var UserInterface[] */
    private array $users = [];

    public function findById(string|int $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    public function save(UserInterface $user): void
    {
        $this->users[$user->vimaGetId()] = $user;
    }

    public function delete(string|int $id): void
    {
        unset($this->users[$id]);
    }
}
