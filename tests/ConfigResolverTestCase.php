<?php
namespace Tests;

use Vima\Core\Config\VimaConfig;

final class ConfigResolverTestCase extends TestCase
{
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

    /**
     * Summary of config
     * @var array
     */
    public VimaConfig $config;
}