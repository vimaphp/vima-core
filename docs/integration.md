# Vima Core Integration Guide

This guide is intended for framework developers looking to build a Vima bridge (e.g., `vima/laravel`, `vima/tempest`).

## 1. Implement Storage Contracts

Vima defines its storage requirements through repository interfaces. You need to implement these using your framework's DB layer.

Essential interfaces:
- `RoleRepositoryInterface`
- `PermissionRepositoryInterface`
- `UserRoleRepositoryInterface`
- `UserPermissionRepositoryInterface`
- `RolePermissionRepositoryInterface`

```php
namespace YourFramework\Vima\Repositories;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;

class DatabaseRoleRepository implements RoleRepositoryInterface {
    public function find(string|int $id): ?Role {
        // Fetch from DB and return Role entity
    }
    // ... implement other methods
}
```

## 2. User Resolution

Ensure your user models implement `Vima\Core\Contracts\UserInterface` or configure a custom `UserResolver` that can extract the correct ID and roles from your framework's Auth system.

```php
namespace YourFramework\Entities;

use Vima\Core\Contracts\UserInterface;

class User implements UserInterface {
    public function vimaGetId(): string|int {
        return $this->id;
    }
    
    public function vimaGetRoles(): array {
        return $this->roles->pluck('name')->toArray();
    }
}
```

## 3. Dependency Injection

Vima ships with its own `DependencyContainer`, but you should probably wire it into your framework's native container.

```php
// Example: Registering Vima services in a Tempest or Laravel provider
$container->register(RoleRepositoryInterface::class, fn() => new DatabaseRoleRepository());
$container->register(AccessManager::class, function($c) {
    return new AccessManager(); // AccessManager will resolve its own dependencies via vima\Core\resolve()
});
```

## 4. Helper Functions & Filters

To make Vima feel "native", provide framework-specific helpers.

### Global Helper
```php
function can(string $permission, ...$arguments): bool {
    $manager = Vima\Core\resolve(AccessManager::class);
    $user = auth()->user();
    
    return $manager->can($user, $permission, ...$arguments);
}
```

### Route Filters
Implement middleware or filters that use `AccessManager::authorize()` to guard routes.

```php
public function handle($request, Closure $next, $permission) {
    $vima = resolve(AccessManager::class);
    $vima->authorize(auth()->user(), $permission);
    
    return $next($request);
}
```

---
(c) Vima PHP <https://github.com/vimaphp>
