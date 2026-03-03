<?php

declare(strict_types=1);

namespace Tests\Integration\PostgreSql;

use Nette\Database\Connection;
use Tests\Integration\DatabaseTestCase;

/**
 * @internal
 */
class PostgreSqlDatabaseTestCase extends DatabaseTestCase
{
    protected Connection $connection;
    protected string $schema;

    public function setUp(): void
    {
        $this->connection = $this->createConnection($this->parseDatabaseConfig('database.postgres'));

        parent::setUp();
    }

    protected function getSetUpConnection(): Connection
    {
        return $this->connection;
    }

    protected function getStructureFile(): string
    {
        return __DIR__ . '/db/structure.sql';
    }

    protected function afterSetupDatabase(Connection $connection): void
    {
        $this->schema = $this->generateDatabaseName();

        $connection->query('CREATE SCHEMA ?name', $this->schema);
        $connection->query('SET SEARCH_PATH TO ?name', $this->schema);
    }
}
