<?php
declare(strict_types=1);

namespace Vima\Core;

function container(): DependencyContainer
{
    return DependencyContainer::getInstance();
}

function resolve(string $id): object
{
    return container()->get($id);
}

function make(string $id): object
{
    return resolve($id);
}
function register(string|object $abstract, ?object $concrete = null): void
{
    DependencyContainer::getInstance()->register($abstract, $concrete);
}

function registerMany(array $dependencies): void
{
    DependencyContainer::getInstance()->registerMany($dependencies);
}


function singleton(string $abstract, object $instance): void
{
    container()->bind($abstract, $instance);
}
