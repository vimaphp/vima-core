<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

interface UserRepositoryInterface
{
    /**
     * Find a user by its unique identifier.
     *
     * @param string|int $id
     * @return UserInterface|null
     */
    public function findById(string|int $id): ?UserInterface;

    /**
     * Persist the user instance.
     *
     * @param UserInterface $user
     */
    public function save(UserInterface $user): void;

    /**
     * Delete a user by ID.
     *
     * @param string|int $id
     */
    public function delete(string|int $id): void;
}
