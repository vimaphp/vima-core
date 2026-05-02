<?php

use Vima\Core\Schema\Schema;
use Vima\Core\Schema\Table;

it('manages tables in schema', function () {
    $schema = new Schema();
    $table = new Table('users');
    
    $schema->addTable($table);
    expect($schema->getTables())->toHaveCount(1);
    expect($schema->getTable('users'))->toBe($table);
    
    expect(fn() => $schema->getTable('missing'))->toThrow(\Exception::class);
});
