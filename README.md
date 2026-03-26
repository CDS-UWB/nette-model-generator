<div align="center">
<a href="https://cds.zcu.cz">
  <img src="https://cds.zcu.cz/images/logo.svg" width="200">
</a>
</div>

# Nette Model Generator
[![Tests](https://github.com/CDS-UWB/nette-model-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/CDS-UWB/nette-model-generator/actions/workflows/tests.yml)

This project is a database model generator for the Nette Framework. 
It provides utilities to generate PHP classes for database tables, columns, and enums, following the PSR-4 standard.

## Features

- Generate PHP classes for database tables, columns, and enums.
- Follows PSR-4 autoloading standard.
- Customizable namespace and root directory.

## Installation

```sh
composer require --dev cds-uwb/nette-model-generator
```

## Usage

### Generating models
Use provided `bin/model-generator` script to generate models from your database schema.
```text
Usage: model-generator -d <mysql|pgsql> -H <host> -D <dbname> -U <user> -P <password> [optional options]
Options:
  Required:
    -d, --driver    Database driver (e.g., mysql, pgsql).
    -H, --host      Database host.
    -D, --dbname    Database name.
    -U, --user      Database user.
    -P, --password  Database password.
    -r, --root-dir  Root directory for generated files.

  Optional:
    -p, --port                 Database port.
    -s, --schemas              [pgsql only] Comma-separated list of schemas to include (optional).
    -i, --include-schema       [pgsql only] Include schema name in generated class names and paths (optional).
    -e, --namespace            Namespace for generated classes (default: App\\Model).
    -o, --omit-namespace-root  Namespace part ignored when generating PSR-4 structure for model (optional).
    -v, --php-version          Target PHP version for generated code (default: 84). Allowed values are: 82, 83, 84.

  -h, --help      Display this help message.\n
```
Use `--php-version` when you need the generator to produce code compatible with an older runtime. 
Supported values are `82`, `83`, and `84`; the generator emits property hooks and constant types only when the target version supports them, otherwise it falls back to `@property-read` annotations.

#### Generated Structure

The generator creates the following directory structure:
```
app/
├── Model/
│   ├── Generated/
│   │   ├── Columns/
│   │   │   └── {TableName}.php
│   │   ├── Rows/
│   │   │   └── {TableName}ActiveRowBase.php
│   │   ├── Enums/
│   │   │   └── {EnumName}.php
│   │   ├── Managers/
│   │   │   └── {TableName}ManagerBase.php
│   │   ├── DatabaseConventions.php
│   │   ├── Explorer.php
│   │   └── Manager.php
│   │   
│   ├── Managers/
│   │   ├── ManagerBase.php (user file, all user managers extend this class)
│   │   └── {TableName}Manager.php (user file)
│   └── Rows/
│       └── {TableName}ActiveRow.php (user file)
```

#### Generated and User Files
The generator distinguishes between two types of files:

- **Generated files** (in `Generated/` directory): These are fully regenerated on each run and may be overwritten.
- **User files** (in `Model/` directory): These are created **only if they don't already exist**. Once created, they won't be overwritten by subsequent generator runs, allowing you to customize them with your own logic.

### Nette DI configuration
Register the Explorer and Managers to the DI container in Nette configuration:
```yaml
services:
    database.default.explorer: App\Model\Generated\Explorer
    
search:
    - in: %appDir%/Model
      classes:
        - *Manager

database:
    # Not necessary, but this can prevent fails when fetching data from views without primary keys
    # that has not been cached yet.
    conventions: App\Model\Generated\DatabaseConventions
```

### Usage in code

#### Managers
Managers provide methods for common database operations such as fetching, inserting, updating, and deleting records.
See the table below for available methods:

|Method|Description|
|---|---|
| `query(string $sql, mixed ...$params): ResultSet` | Executes a raw SQL query and returns the result set. |
| `getTable(): Selection` | Returns the table selection. |
| `getAll(): Selection` | Returns selection with all rows from the table. |
| `find(mixed $primary): Selection` | Returns selection for row by primary key value. |
| `findWhere(string\|array $where, mixed ...$params): Selection` | Returns a selection of rows that match the given conditions. |
| `get(mixed $primary): ?ActiveRow` | Fetches a single row by primary key value. |
| `getStrict(mixed $primary): ActiveRow` | Fetches a single row by primary key value, throws exception if not found. |
| `getWhere(string\|array $where, mixed ...$params): ?ActiveRow` | Fetches a single row that matches the given conditions. |
| `getWhereStrict(string\|array $where, mixed ...$params): ActiveRow` | Fetches a single row that matches the given conditions, throws exception if not found. |
| `exists(array\|int\|string $primary): bool` | Checks if any row exists. |
| `existsWhere(array $where): bool` | Checks if any row exists that matches the given conditions. |
| `getUnique(string $column, array $where = []): array` | Fetches a list of unique values of a column from the table. |
| `insert(array $data): ActiveRow\|array\|int` | Inserts a single row into the table and returns the record. |
| `insertMultiple(iterable $data): ActiveRow\|array\|int` | Inserts multiple rows into the table and returns the first ActiveRow if table has primary key, or original input data if table doesn't have primary key. |
| `update(mixed $primary, array $data): int` | Updates a single row by primary key value. |
| `updateWhere(string\|array $where, iterable $data, mixed ...$params): int` | Updates rows that match the given conditions. |
| `updateAll(iterable $data): int` | Updates all rows in the table. |
| `delete(mixed $primary): int` | Deletes a single row by primary key value. |
| `deleteWhere(string\|array $where, mixed ...$params): int` | Deletes rows that match the given conditions. |
| `deleteAll(): int` | Deletes all rows in the table. |
| `fetchPairs(string\|\Closure\|int\|null $key = null, string\|int\|null $value = null): array` | Fetches pairs of key and value from the table. |
| `transaction(callable $function): mixed` | Executes function in a transaction. |
| `beginTransaction(): void` | Begins a transaction. |
| `commit(): void` | Commits a transaction. |
| `rollBack(): void` | Rolls back a transaction. |
| `getColumnNames(): array` | Returns an array of column names in the table. |

```php
use App\Model\Manager\YourTableManager;

class YourService
{
    public function __construct(protected YourTableManager $yourTableManager)
    {
    }

    public function doSomething(): void
    {
        $records = $this->yourTableManager->get();
        // ...
    }
}
```

### Custom types
Use `Cds\NetteModelGenerator\Data\CustomType` to override how database column types are mapped
to generated properties. You pass custom types through your reflection constructor when you
build a `GeneratorContext` inside your build script.

```php
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Generators\ModelGenerator;
use Cds\NetteModelGenerator\GeneratorContext;
use Cds\NetteModelGenerator\Reflections\MySqlReflection;
use Cds\NetteModelGenerator\Psr4FileManager;
use Cds\NetteModelGenerator\FileWriter;
use Nette\PhpGenerator\PsrPrinter;

$customTypes = [
    new CustomType(
        dbType: 'date',
        phpType: '\\' . DateTime::class,
        annotations: ['custom comment', '@annotation'],
        castValueCallback: static fn (string $columnName): string => 
            "\$this['" . $columnName . "'] !== null ? (new \DateTimeImmutable(\$this['" . $columnName . "'])) : null",
    ),
];

$context = new GeneratorContext(
    reflection: new MySqlReflection(connection: $connection, dbName: $dbName, customTypes: $customTypes),
    fileManager: new Psr4FileManager(rootDir: $rootDir, namespace: ['App', 'Model']),
    fileWriter: new FileWriter(),
    printer: new PsrPrinter(),
);

(new ModelGenerator())->runDefault($context);

// The generated base row keeps the property getter untouched and handles casting via constructor hooks:
abstract class YourTableActiveRowBase extends ActiveRow
{
    /**
     * custom comment
     *
     * @annotation
     */
    public \DateTime|null $yourColumnName {
        get => $this['your_column_name'];
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function __construct(array $data, Selection $selection)
    {
        $data = $this->castValues($data);

        parent::__construct($data, $selection);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function castValues(array $data): array
    {
        $data['your_column_name'] = $this['your_column_name'] !== null ? (new \DateTimeImmutable($this['your_column_name'])) : null;

        return $data;
    }
}
```
