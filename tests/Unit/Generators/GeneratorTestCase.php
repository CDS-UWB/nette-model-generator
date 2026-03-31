<?php

namespace Tests\Unit\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Enum\PhpVersion;
use Cds\NetteModelGenerator\FileWriter;
use Cds\NetteModelGenerator\GeneratorContext;
use Cds\NetteModelGenerator\Logger;
use Cds\NetteModelGenerator\Psr4FileManager;
use Cds\NetteModelGenerator\Reflections\MySqlReflection;
use Cds\NetteModelGenerator\TypeMappers\MySqlTypeMapper;
use Closure;
use Iterator;
use PHPUnit\Framework\TestCase;

abstract class GeneratorTestCase extends TestCase
{
    protected const GeneratedDir = __DIR__ . '/generated';

    /** @var MySqlReflection&\PHPUnit\Framework\MockObject\Stub */
    protected MySqlReflection $mysqlReflection;

    /**
     * @var FileWriter&\PHPUnit\Framework\MockObject\Stub
     */
    protected FileWriter $fileWriter;

    public function setUp(): void
    {
        parent::setUp();

        $this->mysqlReflection = $this->createStub(MySqlReflection::class);
        $this->fileWriter = $this->createStub(FileWriter::class);
    }

    /**
     * @param Closure(string, bool): string $varNameSanitizer
     * @param class-string|null             $managerClass
     * @param class-string|null             $explorerClass
     * @param class-string|null             $dbConventionsClass
     */
    protected function createMysqlGeneratorContext(
        Closure|null $varNameSanitizer = null,
        PhpVersion $targetPhpVersion = PhpVersion::PHP_84,
        string|null $managerClass = null,
        string|null $explorerClass = null,
        string|null $dbConventionsClass = null,
    ): GeneratorContext {
        $this->mysqlReflection->method('getTables')->willReturn($this->getTables());
        $this->mysqlReflection->method('getColumns')->willReturnCallback(fn (Table $table) => $this->getColumns($table));
        $this->mysqlReflection->method('getTypeMapper')->willReturn(new MySqlTypeMapper($this->mysqlReflection));

        return new GeneratorContext(
            reflection: $this->mysqlReflection,
            fileManager: new Psr4FileManager(explode('/', static::GeneratedDir), ['App', 'Model'], false),
            fileWriter: $this->fileWriter,
            logger: $this->createStub(Logger::class),
            varNameSanitizer: $varNameSanitizer,
            targetPhpVersion: $targetPhpVersion,
            managerClass: $managerClass,
            explorerClass: $explorerClass,
            dbConventionsClass: $dbConventionsClass,
        );
    }

    protected function getTables(): Iterator
    {
        $data = new \ArrayObject([new Table('test_table', false, null)]);

        return $data->getIterator();
    }

    protected function getColumns(Table|null $table = null): Iterator
    {
        $table ??= new Table('test_table', false, null);

        $data = new \ArrayObject([
            new Column(
                table: $table,
                name: 'id',
                type: 'int',
                primary: true,
                nullable: false,
                autoIncrement: true,
                comment: 'id column comment'
            ),
            new Column(
                table: $table,
                name: 'text_column',
                type: 'text',
                primary: false,
                nullable: true,
                autoIncrement: false,
                comment: 'text column comment'
            ),
            new Column(
                table: $table,
                name: 'date_column',
                type: 'datetime',
                primary: false,
                nullable: true,
                autoIncrement: false,
                comment: 'date column comment'
            ),
            new Column(
                table: $table,
                name: 'bool_column',
                type: 'boolean',
                primary: false,
                nullable: false,
                autoIncrement: false,
                comment: 'bool column comment'
            ),
            new Column(
                table: $table,
                name: 'nullable_bool_column',
                type: 'boolean',
                primary: false,
                nullable: true,
                autoIncrement: false,
                comment: 'nullable bool column comment'
            ),
            new Column(
                table: $table,
                name: 'float_column',
                type: 'float',
                primary: false,
                nullable: false,
                autoIncrement: false,
                comment: 'float column comment'
            ),
            new Column(
                table: $table,
                name: 'column_without_comment',
                type: 'varchar',
                primary: false,
                nullable: false,
                autoIncrement: false,
                comment: null
            ),
        ]);

        return $data->getIterator();
    }
}
