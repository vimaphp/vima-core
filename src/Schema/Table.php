<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Schema;

class Table
{
    /** @var Field[] */
    public array $fields = [];
    /** @var string[] */
    public array $primaryKeys = [];
    /** @var array<array<string>> */
    public array $uniqueKeys = [];
    /** @var ForeignKey[] */
    public array $foreignKeys = [];

    public function __construct(public string $name)
    {
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function addPrimaryKey(string $column): self
    {
        $this->primaryKeys[] = $column;
        return $this;
    }

    public function addUniqueKey(array $columns): self
    {
        $this->uniqueKeys[] = $columns;
        return $this;
    }

    public function addForeignKey(ForeignKey $fk): self
    {
        $this->foreignKeys[] = $fk;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKeys[0] ?? null;
    }

    /**
     * @return array<array<string>>
     */
    public function getUniqueKeys(): array
    {
        return $this->uniqueKeys;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }
}
