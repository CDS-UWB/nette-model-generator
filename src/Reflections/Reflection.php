<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Reflections;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\TypeMappers\TypeMapper;
use Iterator;

interface Reflection
{
    /**
     * Returns all tables in the database.
     *
     * @return Iterator<int, Table>
     */
    public function getTables(): Iterator;

    /**
     * Returns all columns in the table.
     *
     * @return Iterator<int, Column>
     */
    public function getColumns(Table $table): Iterator;

    /**
     * Returns all enums in the database.
     *
     * @return Iterator<int, Enum>
     */
    public function getEnums(): Iterator;

    /**
     * Returns the DB to PHP type mapper.
     *
     * @return TypeMapper<PostgreSqlReflection>|TypeMapper<MySqlReflection>
     */
    public function getTypeMapper(): TypeMapper;

    /**
     * Returns custom types defined by the user.
     *
     * @return array<CustomType>
     */
    public function getCustomTypes(): array;
}
