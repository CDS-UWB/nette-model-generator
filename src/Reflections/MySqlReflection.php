<?php

namespace Cds\NetteModelGenerator\Reflections;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\TypeMappers\MySqlTypeMapper;
use Cds\NetteModelGenerator\TypeMappers\TypeMapper;
use Iterator;
use Nette\Database\Connection;
use Nette\Database\Row;

readonly class MySqlReflection implements Reflection
{
    /**
     * @param array<string, mixed> $customTypes Custom mapping of DB types to PHP types, e.g. ['date' => DateTime::class]
     */
    public function __construct(protected Connection $connection, protected string $dbName, protected array $customTypes = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getTables(): Iterator
    {
        $sql = <<<'SQL'
            SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type IN ('BASE TABLE', 'VIEW');
        SQL;

        $data = $this->connection->query($sql, $this->dbName)->fetchAll();
        $tables = array_map(static fn (Row $row) => new Table($row->table_name), $data);

        return (new \ArrayObject($tables))->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getColumns(Table $table): Iterator
    {
        $sql = <<<'SQL'
            SELECT c.column_name AS name,
                   c.data_type AS type,
                   EXISTS (SELECT 1
                           FROM information_schema.key_column_usage kcu
                           WHERE kcu.column_name = c.column_name
                             AND kcu.table_name = c.table_name
                             AND kcu.constraint_name = 'PRIMARY') AS 'primary',
                   CASE WHEN c.is_nullable = 'YES' THEN true ELSE false END AS nullable,
                   CASE WHEN c.extra LIKE '%auto_increment%' THEN true ELSE false END AS auto_increment,
                   c.character_maximum_length AS size,
                   c.numeric_precision AS 'precision',
                c.column_comment,
                c.column_type
            FROM information_schema.columns c
            WHERE c.table_schema = ?
                AND c.table_name = ?
            ORDER BY c.ordinal_position;
        SQL;

        $data = $this->connection->query($sql, $this->dbName, $table->name)->fetchAll();
        $columns = array_map(fn (Row $row) => new Column(
            table: $table,
            name: $row->name,
            type: $row->type,
            primary: $row->primary,
            nullable: $row->nullable,
            autoIncrement: $row->auto_increment,
            size: $this->parseSize($row),
            comment: $row->column_comment
        ), $data);

        return (new \ArrayObject($columns))->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getEnums(): Iterator
    {
        $enums = [];

        foreach ($this->getTables() as $table) {
            $sql = <<<'SQL'
            SELECT c.table_name, c.column_name, c.column_type
            FROM information_schema.columns c
            JOIN information_schema.tables t ON (c.table_name = t.table_name AND c.table_schema = t.table_schema)
            WHERE c.table_schema = ?
                AND c.table_name = ?
                AND t.table_type = 'BASE TABLE'
                AND c.data_type = 'enum';
            SQL;

            $data = $this->connection->query($sql, $this->dbName, $table->getFullName())->fetchAll();

            $enums = array_merge($enums, array_map(fn (Row $row) => new Enum(
                name: $table->name . '_' . $row->column_name,
                values: $this->parseEnumValues($row->column_type),
            ), $data));
        }

        return (new \ArrayObject($enums))->getIterator();
    }

    /**
     * {@inheritDoc}
     *
     * @return MySqlTypeMapper
     */
    public function getTypeMapper(): TypeMapper
    {
        return new MySqlTypeMapper($this, $this->customTypes);
    }

    /**
     * @param string $columnType Column type in format `enum('value1', 'value2', ...)`
     *
     * @return array<string>
     */
    private function parseEnumValues(string $columnType): array
    {
        $values = [];
        $matches = [];
        preg_match_all('/\'(?P<value>[^\']+)\'/', $columnType, $matches);

        foreach ($matches['value'] as $value) {
            $values[] = $value;
        }

        return $values;
    }

    private function parseSize(Row $row): int|null
    {
        if (str_contains($row->column_type, '(')) {
            $matches = [];
            preg_match('/\((?P<size>\d+)\)/', $row->column_type, $matches);

            return isset($matches['size']) ? (int) $matches['size'] : null;
        }

        return null;
    }
}
