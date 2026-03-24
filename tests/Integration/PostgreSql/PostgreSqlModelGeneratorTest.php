<?php

declare(strict_types=1);

namespace Tests\Integration\PostgreSql;

use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\FileWriter;
use Cds\NetteModelGenerator\GeneratorContext;
use Cds\NetteModelGenerator\ModelGenerator;
use Cds\NetteModelGenerator\Psr4FileManager;
use Cds\NetteModelGenerator\Reflections\PostgreSqlReflection;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\Trait\CheckResults;
use Tests\Integration\Trait\OutputDir;

/**
 * @internal
 */
class PostgreSqlModelGeneratorTest extends PostgreSqlDatabaseTestCase
{
    use CheckResults;
    use OutputDir;

    #[Test]
    public function generateModelDefault(): void
    {
        $context = new GeneratorContext(
            reflection: new PostgreSqlReflection($this->connection, $this->dbName, schemas: [$this->schema]),
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
        $this->checkEnums($dir, enumPrefix: '');
        $this->checkManagersBase($dir, $this->schema . '.');
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
                castValueCallback: static fn (string $column): string => '$this[\'' . $column . '\'] !== null ? (new \\DateTimeImmutable((string) $this[\'' . $column . '\'])) : null',
            ),
        ];

        $context = new GeneratorContext(
            reflection: new PostgreSqlReflection($this->connection, $this->dbName, schemas: [$this->schema], customTypes: $customTypes),
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
        $this->checkEnums($dir, enumPrefix: '');
        $this->checkManagersBase($dir, $this->schema . '.');
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
                'id' => 'integer',
                'text_value' => 'character varying',
                'optional_text' => 'text',
                'bool_value' => 'boolean',
                'created_at' => 'timestamp without time zone',
            ],
            'date_time' => [
                'id' => 'integer',
                'date_value' => 'date',
                'time_value' => 'time without time zone',
                'datetime_value' => 'timestamp without time zone',
                'timestamp_value' => 'timestamp without time zone',
            ],
            'enum' => [
                'id' => 'integer',
                'status' => 'columns_status',
                'priority' => 'columns_priority',
            ],
            'json_and_binary' => [
                'id' => 'integer',
                'json_value' => 'jsonb',
                'blob_value' => 'bytea',
                'long_text_value' => 'text',
            ],
            'number' => [
                'id' => 'integer',
                'tiny_value' => 'smallint',
                'small_value' => 'smallint',
                'int_value' => 'integer',
                'big_value' => 'bigint',
                'decimal_value' => 'numeric',
                'float_value' => 'real',
                'double_value' => 'double precision',
            ],
        ];
    }
}
