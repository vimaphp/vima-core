# Vima Event System

Vima Core provides an internal event system that allows framework integrators and application developers to hook into core processes like synchronization, entity persistence, and mapping generation.

## How it Works

The event system is built around the `Vima\Core\Contracts\EventDispatcherInterface`. Services like `SyncService`, `MapGenerator`, and `MappingService` accept an optional dispatcher. If not provided, they fall back to a `DefaultEventDispatcher` which records events in memory (useful for testing).

## Available Events

### Sync Events
These events are fired by `Vima\Core\Services\SyncService`.

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Sync\SyncStarted` | `vima.sync.started` | Fired before synchronization begins. |
| `Vima\Core\Events\Sync\SyncFinished` | `vima.sync.finished` | Fired after synchronization completes. |

### Access & Authorization Events
These events are fired by `Vima\Core\Services\AccessManager` during authorization checks.

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Access\AuthorizationChecked` | `vima.access.authorization_checked` | Fired after any `can()` or policy check. |
| `Vima\Core\Events\Access\AccessDenied` | `vima.access.denied` | Fired when an authorization check fails (e.g. in `enforce()`). |

### Grant & Persistence Events
These events are fired when roles or permissions are manually assigned or revoked.

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Grant\RoleAssigned` | `vima.grant.role_assigned` | Fired when a role is assigned to a user. |
| `Vima\Core\Events\Grant\RoleDetached` | `vima.grant.role_detached` | Fired when a role is revoked from a user. |
| `Vima\Core\Events\Grant\PermissionGranted` | `vima.grant.permission_granted` | Fired when a direct permission is granted. |
| `Vima\Core\Events\Grant\PermissionRevoked` | `vima.grant.permission_revoked` | Fired when a direct permission is revoked. |

### Policy Events
These events relate to policy management.

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Policy\PolicyRegistered` | `vima.policy.registered` | Fired when a new policy callback or class is registered. |

### Repository Events
These events are fired by entity repository implementations (like those in `vima/codeigniter`).

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Repository\RepositoryAction` | `vima.repository.action` | Generic event for entity creation, update, or deletion. |

### Mapping Events
These events are fired when role/permission mappers are generated or updated.

| Event Class | CI4 Name | Description |
|---|---|---|
| `Vima\Core\Events\Mapping\MapGenerated` | `vima.mapping.generated` | Fired when a role or permission mapping is generated. |

## Integrating a Framework
To integrate Vima events with a framework's event system:

1.  Implement `Vima\Core\Contracts\EventDispatcherInterface`.
2.  Delegate the `dispatch()` call to the framework's internal dispatcher.
3.  Register your implementation in the Vima `DependencyContainer`.

```php
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\DependencyContainer;

class FrameworkEventDispatcher implements EventDispatcherInterface {
    public function dispatch(object $event): object {
        $name = method_exists($event, 'getName') ? $event->getName() : get_class($event);
        // Dispatch to framework...
        return $event;
    }
}

DependencyContainer::getInstance()->register(
    EventDispatcherInterface::class, 
    new FrameworkEventDispatcher()
);
```
