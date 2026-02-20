<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Services;

use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Contracts\UserInterface;
use Vima\Core\Exceptions\UserNotFoundException;

/**
 * Class UserManager
 * 
 * Internal service for managing User entities.
 *
 * @package Vima\Core\Services
 */
class UserManager
{
    /**
     * @param UserRepositoryInterface $users
     */
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    /**
     * Find a user by identifier.
     *
     * @param string|int $id
     * @return UserInterface
     * @throws UserNotFoundException
     */
    public function find(string|int $id): UserInterface
    {
        $user = $this->users->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        return $user;
    }

    /**
     * Save/Update a user entity.
     *
     * @param UserInterface $user
     * @return void
     */
    public function save(UserInterface $user): void
    {
        $this->users->save($user);
    }

    /**
     * Delete a user by identifier.
     *
     * @param string|int $id
     * @return void
     */
    public function delete(string|int $id): void
    {
        $this->users->delete($id);
    }
}