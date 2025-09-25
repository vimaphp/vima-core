# Vima Core

**Vima Core** is a framework-independent **authorization library** that provides a clean foundation for **RBAC (Role-Based Access Control)** and **ABAC (Attribute-Based Access Control)**.

It is designed to be extended by framework-specific packages (e.g. `vima/tempest`, `vima/codeigniter`) while staying lightweight and testable at the core.

---

## ✨ Features

- 🔑 **Entities**: `User`, `Role`, `Permission`
- 📜 **Contracts**: Interfaces for storage & access logic
- 🗄 **Storage**: In-memory repositories for testing & prototyping
- ⚙️ **Services**:

  - `AccessManager` – RBAC & ABAC evaluation
  - `PolicyRegistry` – central registry for ABAC rules

- 🚀 **Framework Agnostic**: Works in any PHP project
- 🧪 **Pest tests** included (100% coverage)

---

## 📦 Installation

```bash
composer require vima/core
```

---

## 🔧 Basic Usage

### 1. Define Roles & Permissions

```php
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;

$admin = Role::define('admin');
$editor = Role::define('editor');

$updatePosts = Permission::define('posts.update');
$deletePosts = Permission::define('posts.delete');

$admin->addPermission($updatePosts)->addPermission($deletePosts);
$editor->addPermission($updatePosts);
```

---

### 2. Create Users & Assign Roles

```php
use Vima\Core\Entities\User;

$alice = new User(1);
$alice->assignRole($admin);

$bob = new User(2);
$bob->assignRole($editor);
```

---

### 3. RBAC – Check Access

```php
use Vima\Core\Services\AccessManager;

$manager = new AccessManager();

$manager->can($alice, 'posts.delete'); // true
$manager->can($bob, 'posts.delete');   // false
```

---

### 4. ABAC – Define Policies

```php
use Vima\Core\Services\PolicyRegistry;

$policies = PolicyRegistry::define([
    'posts.update' => fn(User $user, $post) => $user->getId() === $post->ownerId,
]);

$manager = new AccessManager($policies);

$post = (object) ['ownerId' => 2];

$manager->evaluatePolicy($bob, 'posts.update', $post); // true (owner matches)
$manager->evaluatePolicy($alice, 'posts.update', $post); // false
```

---

## 🛠 CLI

The package ships with a lightweight CLI (via Symfony Console).

```bash
php vendor/bin/vima
```

Example commands:

```bash
php vendor/bin/vima list
php vendor/bin/vima make:role admin
php vendor/bin/vima make:permission posts.update
```

---

## 🧪 Testing

This package uses [Pest](https://pestphp.com/) for testing.

Run the test suite:

```bash
composer test
```

With coverage:

```bash
composer test-coverage
```

Expected: **100% code coverage** ✅

---

## 📂 Package Structure

```
src/
 ├── Contracts/         # Interfaces
 ├── Entities/          # User, Role, Permission
 ├── Exceptions/        # Domain-specific exceptions
 ├── Services/          # AccessManager, PolicyRegistry
 ├── Storage/           # InMemory repositories
 └── Console/           # CLI entrypoint
tests/                  # Pest tests
```

---

## 🔮 Roadmap

- [ ] Add persistence adapters (DB, cache, file storage)
- [ ] Framework integrations (Laravel, Symfony, CI4)
- [ ] Policy composition (`can` + `evaluatePolicy`)
- [ ] Middleware support for HTTP frameworks

---

## 📜 License

MIT License. Do whatever you want, just don’t blame us if you lock yourself out. 🔒
