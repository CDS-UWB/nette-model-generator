<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Table;
use Nette\Database\ResultSet;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PromotedParameter;

class ManagerGenerator extends Generator
{
    public function generate(): \Generator
    {
        $this->log('Generating Managers:');

        $files = [];

        $files = array_merge($files, $this->generateManager());

        $files = array_merge($files, $this->generateBaseManager());

        foreach ($this->context->reflection->getTables() as $table) {
            $this->log("\tTable '{$table->name}':");

            $files = array_merge($files, $this->generateBaseManagerForTable($table));

            $files = array_merge($files, $this->generateUserManagerForTable($table));
        }

        yield from $files;
    }

    /**
     * @return array<string>
     */
    public function generateManager(): array
    {
        $name = $this->context->fileManager->getManagerName();
        $filePath = $this->context->fileManager->getManagerPath();

        $this->log("\t- {$name}");

        $file = $this->createGeneratedPhpFile();

        $class = $file->addClass($name);
        $class->addComment("The Manager class contains basic CRUD operations for the table.\n");
        $class->addComment('@template T of ActiveRow');
        $class->setAbstract();
        $class->getNamespace()
            ?->addUse(ActiveRow::class)
            ?->addUse(Selection::class)
            ?->addUse(ResultSet::class)
        ;

        $class->addMethod('__construct')
            ->setParameters([
                (new PromotedParameter('explorer'))
                    ->setType($this->context->fileManager->getExplorerName())
                    ->setVisibility('public')
                    ->setReadOnly(),
            ])
        ;

        $class->addMethod('query')
            ->setReturnType(ResultSet::class)
            ->setParameters([
                (new Parameter('sql'))->setType('string'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody('return $this->explorer->query($sql, ...$params);')
            ->addComment("Executes a raw SQL query and returns the result set.\n")
            ->addComment('@param literal-string $sql')
        ;

        $class->addMethod('getTable')
            ->setReturnType(Selection::class)
            ->setBody('return $this->table();')
            ->addComment("Returns the table selection.\n")
            ->addComment('@return Selection<T>')
        ;

        $class->addMethod('getAll')
            ->setReturnType(Selection::class)
            ->setBody('return $this->table();')
            ->addComment("Returns selection with all rows from the table.\n")
            ->addComment('@return Selection<T>')
        ;

        $class->addMethod('find')
            ->setReturnType(Selection::class)
            ->setParameters([
                (new Parameter('primary'))->setType('mixed'),
            ])
            ->setBody('return $this->table()->wherePrimary($primary);')
            ->addComment("Returns selection for row by primary key value.\n")
            ->addComment('@return Selection<T>')
        ;

        $class->addMethod('findWhere')
            ->setReturnType(Selection::class)
            ->setParameters([
                (new Parameter('where'))->setType('string|array'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody('return $this->table()->where($where, ...$params);')
            ->addComment("Returns a selection of rows that match the given conditions.\n")
            ->addComment(
                <<<'TEXT'
            Example usage: 
            ```
            $manager->findWhere('name = ? AND surname ?', 'John', 'Doe');
            $manager->findWhere(['name' => 'John', 'surname' => 'Doe']);
            ```

            TEXT
            )
            ->addComment("@param array<string, mixed>|string \$where \n")
            ->addComment('@return Selection<T>')
        ;

        $class->addMethod('get')
            ->setReturnType(ActiveRow::class)
            ->setReturnNullable()
            ->setParameters([
                (new Parameter('primary'))->setType('mixed'),
            ])
            ->setBody(<<<'PHP'
             return $this->find($primary)->fetch();
             PHP)
            ->addComment("Fetches a single row by primary key value.\n")
            ->addComment('@return T|null')
        ;

        $class->addMethod('getStrict')
            ->setReturnType(ActiveRow::class)
            ->setParameters([
                (new Parameter('primary'))->setType('mixed'),
            ])
            ->setBody(<<<'PHP'
            $row = $this->find($primary)->fetch();

            if ($row === null) {
                $this->throwStrict('Row not found', $primary);
            }

            return $row;
            PHP)
            ->addComment("Fetches a single row by primary key value, throws exception if not found.\n")
            ->addComment("@return T\n")
            ->addComment('@throws \RuntimeException')
        ;

        $class->addMethod('getWhere')
            ->setReturnType(ActiveRow::class)
            ->setReturnNullable()
            ->setParameters([
                (new Parameter('where'))->setType('string|array'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody(<<<'PHP'
            return $this->findWhere($where, ...$params)->limit(1)->fetch();
            PHP)
            ->addComment("Fetches a single row that matches the given conditions.\n")
            ->addComment("Usage is similar to `findWhere` method.\n")
            ->addComment("@param array<string, mixed> \$where \n")
            ->addComment('@return T|null')
        ;

        $class->addMethod('getWhereStrict')
            ->setReturnType(ActiveRow::class)
            ->setParameters([
                (new Parameter('where'))->setType('string|array'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody(<<<'PHP'
            $row = $this->findWhere($where, ...$params)->limit(1)->fetch();

            if ($row === null) {
                $this->throwStrict('Row not found', $where);
            }

            return $row;
            PHP)
            ->addComment("Fetches a single row that matches the given conditions, throws exception if not found.\n")
            ->addComment("@param array<string, mixed> \$where \n")
            ->addComment("@return T\n")
            ->addComment('@throws \RuntimeException')
        ;

        $class->addMethod('insert')
            ->setReturnType(ActiveRow::class)
            ->setParameters([
                (new Parameter('data'))->setType('array'),
            ])
            ->setBody(<<<'PHP'
            $data = $this->table()->insert($data);
            assert($data instanceof ActiveRow);
            
            return $data;
            PHP)
            ->addComment("Inserts a single row into the table and returns the record.\n")
            ->addComment("@param array<string, mixed> \$data \n")
            ->addComment('@return T')
        ;

        $class->addMethod('insertMultiple')
            ->setReturnType(ActiveRow::class . '|iterable')
            ->setParameters([
                (new Parameter('data'))->setType('iterable'),
            ])
            ->setBody(<<<'PHP'
            $data = $this->table()->insert($data);
            assert($data instanceof ActiveRow || is_iterable($data));
            
            return $data;
            PHP)
            ->addComment("Inserts multiple rows into the table and returns the first ActiveRow if table has primary key, or original input data if table doesn't have primary key.\n")
            ->addComment("@param iterable<array<string, mixed>> \$data \n")
            ->addComment('@return T|iterable<array<string, mixed>>')
        ;

        $class->addMethod('update')
            ->setReturnType('int')
            ->setParameters([
                (new Parameter('primary'))->setType('mixed'),
                (new Parameter('data'))->setType('array'),
            ])
            ->setBody('return $this->find($primary)->update($data);')
            ->addComment("Updates a single row by primary key value.\n")
            ->addComment('@param array<string, mixed> $data')
        ;

        $class->addMethod('updateWhere')
            ->setReturnType('int')
            ->setParameters([
                (new Parameter('where'))->setType('string|array'),
                (new Parameter('data'))->setType('iterable'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody('return $this->findWhere($where, ...$params)->update($data);')
            ->addComment("Updates rows that match the given conditions.\nUsage is similar to `findWhere` method.\n")
            ->addComment("@param array<string, mixed>|string \$where \n")
            ->addComment('@param array<string, mixed> $data')
        ;

        $class->addMethod('updateAll')
            ->setReturnType('int')
            ->setParameters([
                (new Parameter('data'))->setType('iterable'),
            ])
            ->setBody('return $this->table()->update($data);')
            ->addComment("Updates all rows in the table.\n")
            ->addComment('@param iterable<array<string, mixed>> $data')
        ;

        $class->addMethod('delete')
            ->setReturnType('int')
            ->setParameters([
                (new Parameter('primary'))->setType('mixed'),
            ])
            ->setBody('return $this->find($primary)->delete();')
            ->addComment("Deletes a single row by primary key value.\n")
            ->addComment('@return int Number of affected rows')
        ;

        $class->addMethod('deleteWhere')
            ->setReturnType('int')
            ->setParameters([
                (new Parameter('where'))->setType('string|array'),
                (new Parameter('params'))->setType('mixed'),
            ])
            ->setVariadic()
            ->setBody('return $this->findWhere($where, ...$params)->delete();')
            ->addComment("Deletes rows that match the given conditions.\nUsage is similar to `findWhere` method.\n")
            ->addComment("@param array<string, mixed>|string \$where \n")
            ->addComment('@return int Number of affected rows')
        ;

        $class->addMethod('deleteAll')
            ->setReturnType('int')
            ->setBody('return $this->table()->delete();')
            ->addComment("Deletes all rows in the table.\n")
            ->addComment('@return int Number of affected rows')
        ;

        $class->addMethod('fetchPairs')
            ->setReturnType('array')
            ->setParameters([
                (new Parameter('key'))->setType('string|\Closure|int|null')->setDefaultValue(null),
                (new Parameter('value'))->setType('string|int|null')->setDefaultValue(null),
            ])
            ->setBody('return $this->getAll()->fetchPairs($key, $value);')
            ->addComment("Fetches pairs of key and value from the table.\n")
            ->addComment("@param string|\\Closure(T): array{0: mixed, 1?: mixed}|int|null \$key\n")
            ->addComment('@return array<string, mixed>')
        ;

        $class->addMethod('transaction')
            ->setReturnType('mixed')
            ->setParameters([
                (new Parameter('function'))->setType('callable'),
            ])
            ->setBody('return $this->explorer->transaction($function);')
            ->addComment("Executes function in a transaction.\n")
            ->addComment('@param callable(Explorer): mixed $function')
        ;

        $class->addMethod('beginTransaction')
            ->setReturnType('void')
            ->setBody('$this->explorer->beginTransaction();')
            ->addComment("Begins a transaction.\n")
        ;

        $class->addMethod('commit')
            ->setReturnType('void')
            ->setBody('$this->explorer->commit();')
            ->addComment("Commits a transaction.\n")
        ;

        $class->addMethod('rollBack')
            ->setReturnType('void')
            ->setBody('$this->explorer->rollBack();')
            ->addComment("Rolls back a transaction.\n")
        ;

        $class->addMethod('throwStrict')
            ->setReturnType('never')
            ->setParameters([
                (new Parameter('message'))->setType('string'),
                (new Parameter('context'))->setType('mixed'),
            ])
            ->setBody('throw new \RuntimeException($message . \'(\' . var_export($context, true) . \')\');')
            ->addComment("Throws an exception with a message and context information.\n")
            ->addComment("@throws \\RuntimeException\n")
            ->setVisibility('protected')
        ;

        $class->addMethod('getTableName')
            ->setReturnType('string')
            ->setAbstract()
            ->addComment('Returns the name of the table.')
        ;

        $class->addMethod('table')
            ->setReturnType(Selection::class)
            ->setVisibility('private')
            ->setBody(<<<'PHP'
            // @phpstan-ignore return.type
            return $this->explorer->table($this->getTableName());
            PHP)
            ->addComment("Returns table selection.\n")
            ->addComment('@return Selection<T>')
        ;

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    public function generateBaseManager(): array
    {
        $name = $this->context->fileManager->getBaseManagerName();
        $filePath = $this->context->fileManager->getBaseManagerPath();

        $this->log("\t- {$name}");

        if (file_exists($filePath)) {
            $this->log("\t\t\t- Skipping, file already exists");

            return [];
        }

        $file = $this->createPhpFile();

        $class = $file->addClass($name);
        $class->setAbstract();
        $class->addComment('@template T of ActiveRow');
        $class->addComment('@extends Manager<T>');
        $class->getNamespace()
            ?->addUse(ActiveRow::class)
            ?->addUse($this->context->fileManager->getManagerName())
        ;
        $class->setExtends($this->context->fileManager->getManagerName());

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    public function generateBaseManagerForTable(Table $table): array
    {
        $className = $this->context->fileManager->getBaseManagerForTableName($table);
        $filePath = $this->context->fileManager->getBaseManagerForTablePath($table);
        $baseManagerName = $this->context->fileManager->getBaseManagerName();

        $this->log("\t\t- {$className}");

        $rowType = $this->context->fileManager->getUserActiveRowName($table);
        $rowTypeSplit = explode('\\', $rowType);
        $rowTypeWONamespace = end($rowTypeSplit);

        $file = $this->createGeneratedPhpFile();

        $class = $file->addClass($className);
        $class->addComment("@extends ManagerBase<{$rowTypeWONamespace}>");
        $class->setAbstract();
        $class->setExtends($baseManagerName);
        $class->getNamespace()
            ?->addUse($rowType)
            ?->addUse($baseManagerName)
        ;

        $class->addMethod('getTableName')
            ->setReturnType('string')
            ->setBody("return '{$table->getFullName()}';")
        ;

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    public function generateUserManagerForTable(Table $table): array
    {
        $name = $this->context->fileManager->getUserManagerForTableName($table);
        $filePath = $this->context->fileManager->getUserManagerForTablePath($table);
        $generatedName = $this->context->fileManager->getBaseManagerForTableName($table);

        $this->log("\t\t- {$name}");

        if (file_exists($filePath)) {
            $this->log("\t\t\t- Skipping, file already exists");

            return [];
        }

        $file = $this->createPhpFile();

        $class = $file->addClass($name);
        $class->setExtends($generatedName);
        $class->getNamespace()?->addUse($generatedName);

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }
}
