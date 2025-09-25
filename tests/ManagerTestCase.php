<?php
namespace Vima\Core\Tests;

final class ManagerTestCase extends TestCase
{
    /**
     * Summary of manager
     * @var \Vima\Core\Services\AccessManager
     */
    public $accessManager;

    /**
     * Summary of manager
     * @var \Vima\Core\Services\PermissionManager
     */
    public $permissionManager;
    /**
     * Summary of manager
     * @var \Vima\Core\Services\UserManager
     */
    public $userManager;
    /**
     * Summary of manager
     * @var \Vima\Core\Services\RoleManager
     */
    public $roleManager;

    /**
     * Summary of permissionRepo
     * @var \Vima\Core\Contracts\PermissionRepositoryInterface
     */
    public $permissionRepo;

    /**
     * Summary of roleRepo
     * @var \Vima\Core\Contracts\RoleRepositoryInterface
     */
    public $roleRepo;

    /**
     * Summary of roleRepo
     * @var \Vima\Core\Contracts\UserRepositoryInterface
     */
    public $userRepo;


}
