<?php
declare(strict_types=1);

namespace Vima\Core\Services;

use Vima\Core\Contracts\{PolicyRegistryInterface, UserInterface};

class PolicyRegistry implements PolicyRegistryInterface
{
    /** @var array<string, callable> */
    private array $policies = [];

    private static $instance = null;

    public static function instance(): PolicyRegistry
    {
        $instance = self::$instance;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public function register(string $ability, callable $callback): void
    {
        $this->policies[$ability] = $callback;
    }

    public function evaluate(UserInterface $user, string $ability, mixed $resource): bool
    {
        if (!isset($this->policies[$ability])) {
            return false;
        }

        return (bool) call_user_func($this->policies[$ability], $user, $resource);
    }

    /**
     * Define and return a new PolicyRegistry with the given rules.
     *
     * @param array<string, callable> $rules
     * 
     */
    public static function define(array $rules): self
    {
        $registry = self::instance();

        foreach ($rules as $ability => $callback) {
            $registry->register($ability, $callback);
        }

        return $registry;
    }

    public function has(string $permission): bool
    {
        return isset($this->policies[$permission]);
    }
}
