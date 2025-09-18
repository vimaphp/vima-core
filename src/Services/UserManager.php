<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Contracts\UserInterface;
use Vima\Core\Exceptions\UserNotFoundException;

class UserManager
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function find(string|int $id): UserInterface
    {
        $user = $this->users->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        return $user;
    }

    public function save(UserInterface $user): void
    {
        $this->users->save($user);
    }

    public function delete(string|int $id): void
    {
        $this->users->delete($id);
    }
}