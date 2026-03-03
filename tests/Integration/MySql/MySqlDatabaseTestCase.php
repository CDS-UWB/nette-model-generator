<?php

declare(strict_types=1);

namespace Tests\Integration\MySql;

use Nette\Database\Connection;
use Tests\Integration\DatabaseTestCase;

/**
 * @internal
 *
 * @phpstan-import-type DatabaseConfig from DatabaseTestCase
 */
class MySqlDatabaseTestCase extends DatabaseTestCase
{
    protected Connection $rootConnection;
    protected Connection $connection;

    /**
     * @var DatabaseConfig
     */
    protected array $testConfig;

    public function setUp(): void
    {
        $this->testConfig = $this->parseDatabaseConfig('database.mariadb');

        $this->rootConnection = $this->createConnection($this->parseDatabaseConfig('database.mariadb_root'));

        parent::setUp();

        $this->connection = $this->createConnection($this->testConfig);
    }

    protected function getSetUpConnection(): Connection
    {
        return $this->rootConnection;
    }

    protected function getStructureFile(): string
    {
        return __DIR__ . '/db/structure.sql';
    }

    protected function afterSetupDatabase(Connection $connection): void
    {
        $connection->query(
            'GRANT ALL PRIVILEGES ON ?name.* TO ?',
            $this->dbName,
            $this->testConfig['user']
        );

        $connection->query('USE ?name', $this->dbName);
    }
}
