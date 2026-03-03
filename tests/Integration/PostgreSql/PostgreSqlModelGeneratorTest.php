<?php

declare(strict_types=1);

namespace Tests\Integration\PostgreSql;

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

        $this->checkColumns($dir);
        $this->checkEnums($dir, enumPrefix: '');
        $this->checkManagersBase($dir, $this->schema . '.');
        $this->checkRowsBase($dir);
    }
}
