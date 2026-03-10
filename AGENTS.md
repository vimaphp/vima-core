# Agent Integration Guide: Vima Core

This guide is for AI agents (like yourself) working on a codebase that uses `vima/core`.

## Core Concepts
Vima Core is a framework-agnostic authorization library providing **Contract-First** RBAC and ABAC.

- **RBAC**: Handled by `AccessManager::isPermitted()`. Checks if a user has a permission via roles or direct assignment.
- **ABAC**: Handled by `PolicyRegistry`. Evaluates resource-specific logic (policies).
- **Hybrid**: `can()` performs an RBAC check first, and if context/arguments are provided, it also evaluates policies.

## Key Services
1. **`AccessManager`**: The primary entry point.
   - `can(user, permission, namespace, ...args)`: Check authorization.
   - `enforce(...)`: Same as `can`, but throws `AccessDeniedException`.
   - `assignRole(user, role)`: Grant access.
2. **`PolicyRegistry`**: Where ABAC logic lives.
   - `register(action, callback)` or `registerClass(resource, policyClass)`.

## Namespacing & Context
- **Namespaces**: Isolate permissions/roles (e.g., `tenant_1`, `blog`).
- **Context**: Roles can have a `context` array (e.g., `['project_id' => 5]`). Authorization checks can filter roles based on this context.

## How to use in code
Always prefer the `AccessManager` over direct repository calls for authorization checks. 

```php
// RBAC Check
$manager->isPermitted($user, 'posts.edit');

// Contextual RBAC
$manager->isPermitted($user, 'project.view', ['project_id' => 10]);

// ABAC / Policy Check
$manager->evaluatePolicy($user, 'update', null, $post);

// Unified Check
$manager->can($user, 'posts.edit', 'blog', $post);
```

## Schema discovery
If you need to know the database structure, use `FrameworkIntegration::getSchema()`. It returns a `Schema` DTO containing `Table`, `Field`, and `ForeignKey` objects. Avoid guessing field names.
