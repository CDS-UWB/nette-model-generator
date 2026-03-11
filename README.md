<div align="center">
<a href="https://cds.zcu.cz">
  <img src="https://cds.zcu.cz/images/logo.svg" width="200">
</a>
</div>

# Nette Model Generator

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

  -h, --help      Display this help message.\n
```
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
```

### Usage in code

#### Managers
Managers provide methods for common database operations such as fetching, inserting, updating, and deleting records.
See the table below for available methods:

|Method|Description|
|---|---|
| `getAll(): Selection` | Returns selection with all rows from the table. |
| `find(mixed $primary): Selection` | Returns selection for row by primary key value. |
| `findWhere(string\|array $where, mixed ...$params): Selection` | Returns a selection of rows that match the given conditions. |
| `get(mixed $primary): ?ActiveRow` | Fetches a single row by primary key value. |
| `getWhere(string\|array $where, mixed ...$params): ?ActiveRow` | Fetches a single row that matches the given conditions. |
| `insert(array $data): ?ActiveRow` | Inserts a single row into the table and returns the record. |
| `insertMultiple(iterable $data): iterable\|ActiveRow\|null` | Inserts multiple rows into the table and returns the first ActiveRow if table has primary key, or original input data if table doesn't have primary key. |
| `update(mixed $primary, array $data): bool` | Updates a single row by primary key value. |
| `updateWhere(string\|array $where, iterable $data, mixed ...$params): int` | Updates rows that match the given conditions. |
| `updateAll(iterable $data): int` | Updates all rows in the table. |
| `delete(mixed $primary): bool` | Deletes a single row by primary key value. |
| `deleteWhere(string\|array $where, mixed ...$params): int` | Deletes rows that match the given conditions. |
| `deleteAll(): int` | Deletes all rows in the table. |
| `transaction(callable $function): mixed` | Executes function in a transaction. |
| `beginTransaction(): void` | Begins a transaction. |
| `commit(): void` | Commits a transaction. |
| `rollBack(): void` | Rolls back a transaction. |

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
