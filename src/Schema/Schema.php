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

class Schema
{
    /** @var Table[] */
    private array $tables = [];

    public function addTable(Table $table): self
    {
        $this->tables[$table->name] = $table;
        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
