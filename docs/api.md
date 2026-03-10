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

### AccessResolver
Used to verify identifiers against the application `Setup`.

- `role(string|Role $role): Role`
  Resolves a role name to a persisted Entity, validating it against `Setup`.
- `permission(string|Permission $permission): Permission`
  Resolves a permission name, validating it against `Setup` (direct or via roles).

## Entities & Configuration

### Role
- `Role::define(string $name, array $permissions = [], ?string $description = null, array $context = []): Role`
  Declarative helper for use in `Setup`. Supports adding context to roles (e.g. `['school_id' => 1]`).

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
