<?php

declare(strict_types=1);

namespace Tests\Integration\MySql;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\FileWriter;
use Cds\NetteModelGenerator\GeneratorContext;
use Cds\NetteModelGenerator\ModelGenerator;
use Cds\NetteModelGenerator\Psr4FileManager;
use Cds\NetteModelGenerator\Reflections\MySqlReflection;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\Trait\CheckResults;
use Tests\Integration\Trait\OutputDir;

/**
 * @internal
 */
class MySqlModelGeneratorTest extends MySqlDatabaseTestCase
{
    use CheckResults;
    use OutputDir;

    #[Test]
    public function generateModelDefault(): void
    {
        $context = new GeneratorContext(
            reflection: new MySqlReflection($this->connection, $this->dbName),
            fileManager: new Psr4FileManager(
                rootDir: $this->outputDir,
                namespace: ['App', 'Model'],
                includeSchema: false,
            ),
            fileWriter: new FileWriter(),
            printer: new PsrPrinter(),
        );

        $generator = new ModelGenerator();

        $files = iterator_to_array($generator->runDefault($context), false);

        $dir = implode(DIRECTORY_SEPARATOR, $this->outputDir);

        $this->checkColumns($dir, $this->getColumnTypes());
        $this->checkEnums($dir);
        $this->checkManagersBase($dir);
        $this->checkRowsBase($dir);
    }

    #[Test]
    public function generateModelWithCustomTypes(): void
    {
        $customTypes = [
            new CustomType(
                dbType: 'date',
                phpType: '\\' . \DateTime::class,
                annotations: ['@phpstan-ignore property.unusedType'],
                castValueCallback: static fn (Column $column): string => '$this[\'' . $column->name . '\'] !== null ? (new \\DateTimeImmutable((string) $this[\'' . $column->name . '\'])) : null',
            ),
        ];

        $context = new GeneratorContext(
            reflection: new MySqlReflection($this->connection, $this->dbName, $customTypes),
            fileManager: new Psr4FileManager(
                rootDir: $this->outputDir,
                namespace: ['App', 'Model'],
                includeSchema: false,
            ),
            fileWriter: new FileWriter(),
            printer: new PsrPrinter(),
        );

        $generator = new ModelGenerator();

        $files = iterator_to_array($generator->runDefault($context), false);

        $dir = implode(DIRECTORY_SEPARATOR, $this->outputDir);

        $this->checkColumns($dir, $this->getColumnTypes());
        $this->checkEnums($dir);
        $this->checkManagersBase($dir);
        $this->checkRowsBaseWithCustomTypes($dir);
    }

    /**
     * @return array{
     *     basic: array{id: string, text_value: string, optional_text: string, bool_value: string, created_at: string},
     *     date_time: array{id: string, date_value: string, time_value: string, datetime_value: string, timestamp_value: string},
     *     enum: array{id: string, status: string, priority: string},
     *     json_and_binary: array{id: string, json_value: string, blob_value: string, long_text_value: string},
     *     number: array{id: string, tiny_value: string, small_value: string, int_value: string, big_value: string, decimal_value: string, float_value: string, double_value: string}
     * }
     */
    private function getColumnTypes(): array
    {
        return [
            'basic' => [
                'id' => 'int',
                'text_value' => 'varchar',
                'optional_text' => 'text',
                'bool_value' => 'tinyint',
                'created_at' => 'timestamp',
            ],
            'date_time' => [
                'id' => 'int',
                'date_value' => 'date',
                'time_value' => 'time',
                'datetime_value' => 'datetime',
                'timestamp_value' => 'timestamp',
            ],
            'enum' => [
                'id' => 'int',
                'status' => 'enum',
                'priority' => 'enum',
            ],
            'json_and_binary' => [
                'id' => 'int',
                'json_value' => 'longtext',
                'blob_value' => 'blob',
                'long_text_value' => 'longtext',
            ],
            'number' => [
                'id' => 'int',
                'tiny_value' => 'tinyint',
                'small_value' => 'smallint',
                'int_value' => 'int',
                'big_value' => 'bigint',
                'decimal_value' => 'decimal',
                'float_value' => 'float',
                'double_value' => 'double',
            ],
        ];
    }
}
