<?php
namespace Tests;

final class AccessFlowTestCase extends TestCase
{
    /**
     * Summary of manager
     * @var \Vima\Core\Services\AccessManager
     */
    public $manager;

    /**
     * Summary of roleRepo
     * @var \Vima\Core\Storage\InMemory\InMemoryRoleRepository
     */
    public $roleRepo;

    /**
     * Summary of permissionRepo
     * @var \Vima\Core\Storage\InMemory\InMemoryPermissionRepository
     */
    public $permissionRepo;

    /**
     * Summary of policyRegistry
     * @var \Vima\Core\Services\PolicyRegistry
     */
    public $policyRegistry;

    /**
     * Summary of bob
     * @var \Vima\Core\Entities\User
     */
    public $bob;

    /**
     * Summary of alice
     * @var \Vima\Core\Entities\User
     */
    public $alice;

    /**
     * Summary of carol
     * @var \Vima\Core\Entities\User
     */
    public $carol;

    /**
     * Summary of post
     * @var \stdClass
     */
    public $post;

    /**
     * Summary of roles
     * @var \Vima\Core\Entities\Role[]
     */
    public $roles;

    /**
     * Summary of permissions
     * @var \Vima\Core\Entities\Permission[]
     */
    public $permissions;
}


