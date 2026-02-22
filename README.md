# Vima Core

**Vima Core** is a framework-agnostic foundation for building robust **Role-Based Access Control (RBAC)** and **Attribute-Based Access Control (ABAC)** systems in PHP.

Unlike consumer-facing packages, Vima Core is designed specifically for **framework developers** and **system architects**. It provides a "Contract-First" toolkit that you can integrate into your framework's identity and storage systems.

## 🎯 Target Audience

- **Framework Integrators**: Building bridges for Laravel, Tempest, CodeIgniter, etc.
- **Library Authors**: Requiring a lightweight, testable authorization foundation.
- **Enterprise Architects**: Designing custom, decoupled security architectures.

## ✨ Core Features

- 🧩 **Contract-First Design**: Decoupled from storage and framework specifics.
- 🔑 **Entity Foundation**: Standardized `User`, `Role`, and `Permission` entities.
- 📜 **Unified Access Manager**: A single entry point for both RBAC and ABAC checks.
- ⚙️ **Flexible Policies**: Class-based and closure-based ABAC support.
- 🧪 **Testable**: Designed with dependency injection and PSR-11 compliance.

## 📦 Installation

```bash
composer require vima/core
```

## 🔧 Technical Overview

Vima Core provides the logic; you provide the implementation.

### 1. Register Implementation Contracts

As a framework integrator, you implement the storage interfaces (Repositories) and register them in the Vima container.

```php
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use function Vima\Core\registerMany;

registerMany([
    RoleRepositoryInterface::class => new YourDatabaseRoleRepository(),
    PermissionRepositoryInterface::class => new YourDatabasePermissionRepository(),
    // ... other repositories
]);
```

### 2. Authorization Checks

Once set up, authorization is simple and consistent.

```php
use Vima\Core\Services\AccessManager;
use function Vima\Core\resolve;

$vima = resolve(AccessManager::class);

// RBAC Check
if ($vima->can($user, 'posts.edit')) {
    // Authorized...
}

// ABAC Check (with context)
if ($vima->can($user, 'posts.edit', $post)) {
    // Authorized based on policy logic...
}
```

### 3. Defining Policies (ABAC)

Policies are class-based rules for specific resources.

```php
use Vima\Core\Contracts\PolicyInterface;

class PostPolicy implements PolicyInterface {
    public function canEdit(User $user, Post $post) {
        return $user->id === $post->userId;
    }
}

$vima->registerPolicy(Post::class, PostPolicy::class);
```

## 📚 Documentation

Detailed guides for deep integration:

- [**Architecture Overview**](docs/architecture.md) – Understand the design and "The Vima Way".
- [**Integration Guide**](docs/integration.md) – Step-by-step instructions for framework developers.

## 📂 Package Structure

```
src/
 ├── Contracts/         # Persistent layer and service interfaces
 ├── Entities/          # Core security data structures
 ├── Services/          # AccessManager, PolicyRegistry, and Managers
 ├── Support/           # Framework integration helpers
 └── DependencyContainer.php # Vima's PSR-11 container
```

## 📜 License

This package is part of **Vima PHP** and is released under the MIT License.

---
(c) Vima PHP <https://github.com/vimaphp>
