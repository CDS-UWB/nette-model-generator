<?php

declare(strict_types=1);

namespace Tests\Integration;

use Nette\Database\Connection;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function yaml_parse_file;

/**
 * @internal
 *
 * @phpstan-type DatabaseConfig array{host: string, user: string, password: string}
 */
abstract class DatabaseTestCase extends TestCase
{
    public const DbConfigFile = __DIR__ . '/db.yaml';

    protected string $dbName;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbName = $this->generateDatabaseName();

        $this->setUpDatabase($this->getSetUpConnection());
    }

    protected function tearDown(): void
    {
        $this->dropDatabase($this->getSetUpConnection());

        parent::tearDown();
    }

    /**
     * @param DatabaseConfig $config
     */
    protected function createConnection(array $config): Connection
    {
        return new Connection(
            dsn: $config['host'],
            user: $config['user'],
            password: $config['password']
        );
    }

    /**
     * @return DatabaseConfig
     */
    protected function parseDatabaseConfig(string $yamlPath): array
    {
        $data = yaml_parse_file(self::DbConfigFile);

        $parts = explode('.', $yamlPath);

        $current = $data;
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                throw new \InvalidArgumentException("Path '{$yamlPath}' not found in YAML file.");
            }

            $current = $current[$part];
        }

        return $current;
    }

    protected function generateDatabaseName(): string
    {
        return 't_' . bin2hex(random_bytes(20));
    }

    protected function setUpDatabase(Connection $connection): void
    {
        $structureFile = $this->getStructureFile();

        $sql = file_get_contents($structureFile);
        if ($sql === false) {
            throw new \RuntimeException("Failed to read SQL structure file: {$structureFile}");
        }

        $connection->query('CREATE DATABASE ?name', $this->dbName);

        $this->afterSetupDatabase($connection);

        $this->execFile($connection, $structureFile);
    }

    abstract protected function getSetUpConnection(): Connection;

    abstract protected function getStructureFile(): string;

    protected function afterSetupDatabase(Connection $connection): void
    {
    }

    protected function execFile(Connection $connection, string $filePath): void
    {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new \RuntimeException("Failed to read SQL file: {$filePath}");
        }

        $connection->getPdo()->exec($sql);
    }

    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                if (!rmdir($file->getPathname())) {
                    return;
                }
            } else {
                if (!unlink($file->getPathname())) {
                    return;
                }
            }
        }

        rmdir($dir);
    }

    private function dropDatabase(Connection $connection): void
    {
        $connection->query('DROP DATABASE IF EXISTS ?name', $this->dbName);
    }
}
