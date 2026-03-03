<?php

namespace Cds\NetteModelGenerator;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;

readonly class Psr4FileManager implements FileManager
{
    private const GeneratedName = 'Generated';

    /**
     * @param array<string> $rootDir
     * @param array<string> $namespace
     */
    public function __construct(
        private array $rootDir,
        private array $namespace,
        private bool $includeSchema = false
    ) {
        if (empty($rootDir)) {
            throw new \InvalidArgumentException('Root directory must not be empty.');
        }
    }

    // -------------------------------------------------------------------------
    // Paths
    // -------------------------------------------------------------------------
    public function getEnumPath(Enum $enum): string
    {
        return $this->buildPath($enum, self::GeneratedName, 'Enums') . '.php';
    }

    public function getColumnsPath(Table $table): string
    {
        return $this->buildPath($table, self::GeneratedName, 'Columns') . '.php';
    }

    public function getActiveRowPath(Table $table): string
    {
        return $this->buildPath($table, self::GeneratedName, 'Rows') . 'ActiveRowBase.php';
    }

    public function getUserActiveRowPath(Table $table): string
    {
        return $this->buildPath($table, 'Rows') . 'ActiveRow.php';
    }

    public function getManagerPath(): string
    {
        return $this->buildPath(null, self::GeneratedName) . '/Manager.php';
    }

    public function getBaseManagerPath(): string
    {
        return $this->buildPath(null, 'Managers') . '/ManagerBase.php';
    }

    public function getBaseManagerForTablePath(Table $table): string
    {
        return $this->buildPath($table, self::GeneratedName, 'Managers') . 'ManagerBase.php';
    }

    public function getUserManagerForTablePath(Table $table): string
    {
        return $this->buildPath($table, 'Managers') . 'Manager.php';
    }

    public function getExplorerPath(): string
    {
        return $this->buildPath(null, self::GeneratedName) . '/Explorer.php';
    }

    // -------------------------------------------------------------------------
    // Names
    // -------------------------------------------------------------------------
    public function getEnumName(Enum $enum): string
    {
        return $this->buildName($enum, self::GeneratedName, 'Enums');
    }

    public function getColumnsName(Table $table): string
    {
        return $this->buildName($table, self::GeneratedName, 'Columns');
    }

    public function getActiveRowName(Table $table): string
    {
        return $this->buildName($table, self::GeneratedName, 'Rows') . 'ActiveRowBase';
    }

    public function getUserActiveRowName(Table $table): string
    {
        return $this->buildName($table, 'Rows') . 'ActiveRow';
    }

    public function getManagerName(): string
    {
        return $this->joinNameParts(...$this->namespace, ...[self::GeneratedName, 'Manager']);
    }

    public function getBaseManagerName(): string
    {
        return $this->joinNameParts(...$this->namespace, ...['Managers', 'ManagerBase']);
    }

    public function getBaseManagerForTableName(Table $table): string
    {
        return $this->buildName($table, self::GeneratedName, 'Managers') . 'ManagerBase';
    }

    public function getUserManagerForTableName(Table $table): string
    {
        return $this->buildName($table, 'Managers') . 'Manager';
    }

    public function getExplorerName(): string
    {
        return $this->joinNameParts(...$this->namespace, ...[self::GeneratedName, 'Explorer']);
    }

    public function getActiveRowNamespace(): string
    {
        return $this->joinNameParts(...$this->namespace, ...['Rows']);
    }

    private function buildPath(Table|Column|Enum|null $element, string ...$path): string
    {
        $parts = [];

        if ($element !== null) {
            $schema = match (true) {
                $element instanceof Table, $element instanceof Enum => $element->schema,
                default => $element->table->schema
            };
            $name = Utils::snakeToPascalCase($element->name);

            if ($this->includeSchema && $schema !== null) {
                $parts = [Utils::snakeToPascalCase($schema), $name];
            } else {
                $parts = [$name];
            }
        }

        $namespace = $this->adjustNamespacePath($this->namespace);

        return $this->joinPathParts(...$this->rootDir, ...$namespace, ...$path, ...$parts);
    }

    private function joinPathParts(string ...$parts): string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function buildName(Table|Column|Enum $element, string ...$path): string
    {
        $schema = match (true) {
            $element instanceof Table, $element instanceof Enum => $element->schema,
            default => $element->table->schema
        };
        $name = Utils::snakeToPascalCase($element->name);

        if ($this->includeSchema && $schema !== null) {
            $parts = [Utils::snakeToPascalCase($schema), $name];
        } else {
            $parts = [$name];
        }

        return $this->joinNameParts(...$this->namespace, ...$path, ...$parts);
    }

    private function joinNameParts(string ...$parts): string
    {
        return implode('\\', $parts);
    }

    /**
     * If the root directory is equal to the first namespace part, remove it from the namespace.
     *
     * @param array<string> $namespace
     *
     * @return array<string>
     */
    private function adjustNamespacePath(array $namespace): array
    {
        if (empty($this->namespace)) {
            return $namespace;
        }

        if (strtolower($this->rootDir[count($this->rootDir) - 1]) === strtolower($this->namespace[0])) {
            return array_slice($namespace, 1);
        }

        return $namespace;
    }
}
