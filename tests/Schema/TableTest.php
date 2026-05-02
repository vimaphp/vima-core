<?php

use Vima\Core\Schema\Table;
use Vima\Core\Schema\Field;
use Vima\Core\Schema\ForeignKey;

it('manages fields and foreign keys in table', function () {
    $table = new Table('roles');
    
    $field = new Field('name', 'string', length: 100);
    $table->addField($field);
    
    expect($table->getFields())->toHaveCount(1);
    expect($table->getFields()[0])->toBe($field);
    
    $fk = new ForeignKey('user_id', 'users', 'id');
    $table->addForeignKey($fk);
    
    expect($table->getForeignKeys())->toHaveCount(1);
    expect($table->getForeignKeys()[0])->toBe($fk);
    
    expect($table->getName())->toBe('roles');
});
