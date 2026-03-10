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

**Note:** `UserRoleRepositoryInterface::getRolesForUser` signature:
`public function getRolesForUser(int|string $user_id, bool $resolve = false): array;`
(Context filtering is now handled at the service level by inspecting `Role` entities).

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
function can(string $permission, ?string $namespace = null, ...$arguments): bool {
    $manager = Vima\Core\resolve(AccessManager::class);
    $user = auth()->user();

    // you can also have a namespace dynamically resolved from the arguments or else set it to null
    
    return $manager->can($user, $permission, $namespace, ...$arguments);
}
```

### Route Filters
Implement middleware or filters that use `AccessManager::enforce()` to guard routes.

```php
public function handle($request, Closure $next, $permission) {
    $vima = resolve(AccessManager::class);
    $vima->enforce(auth()->user(), $permission);
    
    return $next($request);
}
```

## 5. Automated Schema Setup

Vima Core provides a `FrameworkIntegration::getSchema()` method that returns a typed `Schema` DTO. You can use this to automate database migrations or configuration-based storage.

### Example: Dynamic Migrations
```php
public function up() {
    $schema = FrameworkIntegration::getSchema();
    
    foreach ($schema->getTables() as $tableName => $table) {
        $fields = [];
        foreach ($table->fields as $field) {
            // Map agnostic types (integer, string, text, json) to your DB layer
            $fields[$field->name] = [
                'type' => $field->type === 'integer' ? 'INT' : 'VARCHAR',
                'unsigned' => $field->unsigned,
                'null' => $field->nullable,
            ];
        }
        $this->dbForge->addField($fields);
        $this->dbForge->createTable($tableName);
    }
}
```

By using the `Schema` DTO, your integration will automatically support new fields (like `context` or `namespace`) added to the Core package without manual code updates.

---
(c) Vima PHP <https://github.com/vimaphp>
