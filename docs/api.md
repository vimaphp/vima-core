# Vima Core API Reference

The core package provides the main authorization engine and persistence-agnostic services.

## Services

### AccessManagerInterface
The main entry point for all authorization checks and management.

- `isPermitted(object $user, string $permission, array $context = [], ?string $namespace = null): bool`
  Check if a user has a specific permission through roles or direct assignment. Supports fine-grained context and namespace filtering.
- `can(object $user, string $permission, ?string $namespace = null, ...$arguments): bool`
  Combined RBAC + ABAC check. Supports scoping by namespace and passing context arguments to policies.
- `enforce(object $user, string $permission, ?string $namespace = null, ...$arguments): void`
  Same as `can()`, but throws `AccessDeniedException` on failure.
- `evaluatePolicy(object $user, string $action, ?string $namespace = null, ...$arguments): bool`
  Manually evaluate a policy for a specific action/ability. Supports namespacing and context arguments.
- `hasRole(object $user, string|Role $role, array $context = []): bool`
  Check if a user is assigned a specific role, optionally filtering by role context.
- `assignRole(object $user, string|Role $role): void`
  Assign a role to a user.
- `detachRole(object $user, string|Role $role): void`
  Revoke a role from a user.
- `getUserRoles(object $user, bool $resolve = false): array`
  Retrieve all Roles assigned to a user.
- `getUserPermissions(object $user, array $context = []): array`
  Retrieve all Permissions (direct and via roles) for a user, filtered by context.
- `permit(object $user, string|Permission $permission): void`
  Grant a direct permission to a user.
- `forbid(object $user, string|Permission $permission): void`
  Revoke a direct permission.
- `reconcileAccess(object $user, array $roles, ?array $permissions = null): void`
  Sync a user's access state to an exact set of roles and permissions.
- `deny(object $user, string|Permission $permission, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): void`
  Explicitly deny a permission to a user. Overrides all other grants. Supports wildcards like `*` (global) or `blog:*` (namespace). Supports temporal denials via `expiresAt`.
- `undeny(object $user, string|Permission $permission): void`
  Remove an explicit permission denial.
- `denyRole(object $user, string|Role $role, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): void`
  Explicitly deny a role to a user. The user will be treated as if they don't have this role, even if it is assigned.
- `undenyRole(object $user, string|Role $role): void`
  Remove an explicit role denial.
- `isDenied(object $user, string|Permission $permission, ?string $namespace = null): bool`
  Check if a permission is denied, considering wildcards and expiration.
- `isRoleDenied(object $user, string|Role $role): bool`
  Check if a role is explicitly denied.

### SyncService
Synchronizes declarative configuration (`Setup`) into persistent storage.

- `sync(VimaConfig $config): SyncResponse`
  Synchronize roles and permissions from config into repositories.
- `refresh(bool $refresh = true): self`
  If enabled, deletes all existing roles and permissions before syncing (clean slate).

### AccessResolver
Used to verify identifiers against the application `Setup`.

- `role(string|Role $role): Role`
  Resolves a role name to a persisted Entity, validating it against `Setup`.
- `permission(string|Permission $permission): Permission`
  Resolves a permission name, validating it against `Setup` (direct or via roles).

### PolicyInterface
Every class-based policy must implement this interface to provide the resource class it handles.

- `public static function getResource(): string`
  Return the fully qualified class name of the resource this policy handles.

### MapToPermission Attribute
Used to map policy methods to specific permissions and optionally a namespace.

```php
use Vima\Core\Attributes\MapToPermission;
use Vima\Core\DTOs\AccessContext;

class PostPolicy implements PolicyInterface {
    public static function getResource(): string {
        return Post::class;
    }

    // Maps to 'posts.delete' automatically (canDelete)
    public function canDelete(AccessContext $ctx, Post $post) {
        return $ctx->user->id === $post->userId;
    }

    // Maps to 'posts.publish' via attribute
    #[MapToPermission('publish')]
    public function someCustomMethod(AccessContext $ctx, Post $post) {
        return true;
    }

    // Maps to 'blog:publish' (namespaced)
    #[MapToPermission('publish', namespace: 'blog')]
    public function namespacedPublish(AccessContext $ctx, Post $post) {
        return true;
    }
}
```

## DTOs

### AccessContext
An instance of this class is passed as the first argument to all policy methods. It provides convenient helpers for checking the user's roles and permissions without needing to manually interact with the `AccessManager`.

- `user`: The user object being checked.
- `permission`: The permission name being checked.
- `namespace`: The current namespace of the check.
- `additionalContext`: Array of additional arguments passed to `can()`.

**Methods:**
- `is(string $roleName): bool`
  Check if user has the specific role.
- `isAny(array $roleNames): bool`
  Check if user has any of the provided roles.
- `isAll(array $roleNames): bool`
  Check if user has all of the provided roles.
- `hasRole(string|array $roleName, bool $useAny = true): bool`
  Internal helper for role checks.
- `isSuperAdmin(): bool`
  Check if the user is a super admin.
- `owns(mixed $resource, string $ownerKey = 'user_id'): bool`
  Check if the current user "owns" the resource by comparing IDs.
- `can(string $permission): bool`
  Perform a manual RBAC check for the user.
- `resolveId(): int|string|null`
  Resolve the user's primary key.

## Entities & Configuration

### Role
- `Role::define(string $name, array $permissions = [], ?string $description = null, ?string $namespace = null, array $context = [], array $parents = [], array $children = []): Role`
  Declarative helper for use in `Setup`. Supports adding context to roles (e.g. `['school_id' => 1]`) and defining hierarchical inheritance via `parents` or `children`.

### Permission
- `Permission::define(string $name, ?string $description = null, ?string $namespace = null): Permission`
  Declarative helper for use in `Setup`. Supports namespacing.

## Schema Support

Vima Core provides a framework-agnostic schema definition system to help automate storage setup.

### FrameworkIntegration
- `getSchema(): Vima\Core\Schema\Schema`
  Returns a `Schema` object reflecting the required tables and fields based on configuration.

### Vima\Core\Schema classes
- `Schema`: Collection of `Table` objects.
- `Table`: Contains `Field` objects, keys, and `ForeignKey` relationships.
- `Field`: Defines column attributes (`type`, `length`, `nullable`, `unsigned`, `autoIncrement`).
- `ForeignKey`: Defines relational constraints.

### Setup
A simple container for your declarative authorization structure. Used as the single source of truth for synchronization and resolution.

## Exceptions

- `AccessDeniedException`: Thrown by `enforce()`.
- `PolicyNotFoundException`: Thrown when a resource check is attempted without a registered policy class.
- `PolicyMethodNotFoundException`: Thrown when a policy method is missing.
- `RoleNotFoundException`: Thrown if a requested role does not exist in storage.
