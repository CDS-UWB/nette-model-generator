<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Reflections;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\TypeMappers\PostgreSqlTypeMapper;
use Cds\NetteModelGenerator\TypeMappers\TypeMapper;
use Iterator;
use Nette\Database\Connection;
use Nette\Database\Row;

readonly class PostgreSqlReflection implements Reflection
{
    /**
     * @param list<string>         $schemas     list of schemas
     * @param array<string, mixed> $customTypes Custom mapping of DB types to PHP types, e.g. ['date' => DateTime::class]
     */
    public function __construct(
        protected Connection $connection,
        protected string $dbName,
        protected array $schemas = [],
        protected array $customTypes = []
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getTables(): Iterator
    {
        $sql = <<<'SQL'
            SELECT * FROM (
                -- Tables
                SELECT
                    schemaname AS "schema",
                    tablename AS "table_name",
                    FALSE AS is_view
                FROM pg_tables
                LEFT JOIN pg_class ON pg_class.relname = pg_tables.tablename
                LEFT JOIN pg_namespace ON pg_namespace.nspname = pg_tables.schemaname
                WHERE pg_class.relnamespace = pg_namespace.oid
                UNION
                -- Views
                SELECT
                    schemaname AS "schema",
                    viewname AS "table_name",
                    TRUE AS is_view
                FROM pg_views
                LEFT JOIN pg_class ON pg_class.relname = pg_views.viewname
                LEFT JOIN pg_namespace ON pg_namespace.nspname = pg_views.schemaname
                WHERE pg_class.relnamespace = pg_namespace.oid
            ) AS grp
        SQL;

        $params = [];

        // Filter by provided schemas array
        if (!empty($this->schemas)) {
            $sql .= ' WHERE "schema" IN (?)';
            $params[] = $this->schemas;
        }

        $sql .= ' ORDER BY "schema", "table_name"';

        $data = $this->connection->query($sql, ...$params)->fetchAll();
        $tables = array_map(static fn (Row $row) => new Table(
            name: $row->table_name,
            schema: $row->schema,
            isView: $row->is_view
        ), $data);

        return (new \ArrayObject($tables))->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getColumns(Table $table): Iterator
    {
        $sql = <<<'SQL'
            SELECT
                a.attname::varchar AS name,
                c.relname::varchar AS "table",
                atttypid::regtype::text AS nativetype,
                CASE WHEN a.atttypmod = -1 THEN NULL ELSE a.atttypmod -4 END AS size,
                FALSE AS unsigned,
                NOT (a.attnotnull OR t.typtype = 'd' AND t.typnotnull) AS nullable,
                LTRIM(pg_catalog.pg_get_expr(ad.adbin, ad.adrelid)::text, 'B') AS "default",
                (
                    coalesce(co.contype = 'p' AND
                    strpos(pg_catalog.pg_get_expr(ad.adbin, ad.adrelid), 'nextval') = 1, FALSE)
                ) AS autoincrement,
                coalesce(co.contype = 'p', FALSE) AS "primary",
                substring(
                    pg_catalog.pg_get_expr(ad.adbin, ad.adrelid)
                    from 'nextval[(]''\"?([^''\"]+)'
                ) AS sequence,
                des.description AS comment
            FROM
                pg_catalog.pg_attribute AS a
                JOIN pg_catalog.pg_class AS c ON a.attrelid = c.oid
                JOIN pg_catalog.pg_type AS t ON a.atttypid = t.oid
                LEFT JOIN pg_catalog.pg_attrdef AS ad ON ad.adrelid = c.oid AND ad.adnum = a.attnum
                LEFT JOIN pg_catalog.pg_constraint AS co
                    ON co.connamespace = c.relnamespace
                    AND contype = 'p'
                    AND co.conrelid = c.oid
                    AND a.attnum = ANY(co.conkey)
                LEFT JOIN pg_catalog.pg_description AS des ON c.oid = des.objoid AND a.attnum = des.objsubid
            WHERE
                c.relkind IN ('r', 'v')
                AND c.oid = ?::regclass
                AND a.attnum > 0
                AND NOT a.attisdropped
            ORDER BY
                a.attnum
        SQL;

        $data = $this->connection->query($sql, $table->getFullName())->fetchAll();
        $columns = array_map(static fn (Row $row) => new Column(
            table: $table,
            name: $row->name,
            type: $row->nativetype,
            primary: $row->primary,
            nullable: $row->nullable,
            autoIncrement: $row->autoincrement,
            size: $row->size,
            comment: $row->comment
        ), $data);

        return (new \ArrayObject($columns))->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getEnums(): Iterator
    {
        $enums = [];

        $data = $this->connection->query(<<<'SQL'
            SELECT
                nspname AS "schema",
                typname AS "name",
                string_agg(enumlabel, ',') AS "values"
            FROM pg_type
                LEFT JOIN pg_namespace ON pg_type.typnamespace = pg_namespace.oid
                LEFT JOIN pg_enum ON pg_enum.enumtypid = pg_type.oid
            WHERE nspname IN(?) AND typtype = 'e'
            GROUP BY "schema", "name"
            ORDER BY "typname"
        SQL, $this->schemas)->fetchAll();

        $enums = array_merge($enums, array_map(static fn (Row $row) => new Enum(
            name: $row->name,
            schema: $row->schema,
            values: explode(',', $row->values),
        ), $data));

        return (new \ArrayObject($enums))->getIterator();
    }

    public function translateDomain(string $type): string|null
    {
        return $this->connection->query(<<<'SQL'
            SELECT data_type
            FROM information_schema.domains
            WHERE domain_schema || '.' || domain_name = ?
                OR domain_name = ? AND domain_schema = ANY(pg_catalog.current_schemas(FALSE))
        SQL, $type, $type)->fetchField();
    }

    public function isEnum(string $type): bool
    {
        $row = $this->connection->query(<<<'SQL'
            SELECT 1
            FROM pg_type
            WHERE typtype = 'e'
            AND format_type(oid, NULL) = ?
        SQL, $type)->fetch();

        return $row !== null;
    }

    /**
     * {@inheritDoc}
     *
     * @return PostgreSqlTypeMapper
     */
    public function getTypeMapper(): TypeMapper
    {
        return new PostgreSqlTypeMapper($this, $this->customTypes);
    }
}
