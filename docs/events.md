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
