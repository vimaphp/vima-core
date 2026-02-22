# Vima Core API Reference

The core package provides the main authorization engine and persistence-agnostic services.

## Services

### AccessManagerInterface
The main entry point for all authorization checks and management.

- `isPermitted(object $user, string $permission): bool`
  Check if a user has a specific permission through roles or direct assignment.
- `can(object $user, string $permission, ...$arguments): bool`
  Combined RBAC + ABAC check. Gracefully falls back to RBAC if policies are missing.
- `enforce(object $user, string $permission, ...$arguments): void`
  Same as `can()`, but throws `AccessDeniedException` on failure.
- `hasRole(object $user, string|Role $role): bool`
  Check if a user is assigned a specific role.
- `assignRole(object $user, string|Role $role): void`
  Assign a role to a user.
- `detachRole(object $user, string|Role $role): void`
  Revoke a role from a user.
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
- `Role::define(string $name, array $permissions = [], ?string $description = null): Role`
  Declarative helper for use in `Setup`.

### Permission
- `Permission::define(string $name, ?string $description = null): Permission`
  Declarative helper for use in `Setup`.

### Setup
A simple container for your declarative authorization structure. Used as the single source of truth for synchronization and resolution.

## Exceptions

- `AccessDeniedException`: Thrown by `enforce()`.
- `PolicyNotFoundException`: Thrown when a resource check is attempted without a registered policy class.
- `PolicyMethodNotFoundException`: Thrown when a policy method is missing.
- `RoleNotFoundException`: Thrown if a requested role does not exist in storage.
