<?php

namespace Tests\Unit\Generators;

use ArrayObject;
use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Enum\PhpVersion;
use Cds\NetteModelGenerator\Generators\TablesGenerator;
use Cds\NetteModelGenerator\Utils;
use DateTimeImmutable;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
class TablesGeneratorTest extends GeneratorTestCase
{
    #[Test]
    public function generate(): void
    {
        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRow($path, $content, $printer);
                }
                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $generator = new TablesGenerator($this->createMysqlGeneratorContext());
        $processedFiles = iterator_to_array($generator->generate());
        self::assertCount(2, $processedFiles);
    }

    #[Test]
    #[DataProvider('providePhpVersionsLowerThan84')]
    public function generateForPhpLowerThan84(PhpVersion $phpVersion): void
    {
        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRowPhpLowerThan84($path, $content, $printer);
                }
                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $generator = new TablesGenerator($this->createMysqlGeneratorContext(targetPhpVersion: $phpVersion));
        $processedFiles = iterator_to_array($generator->generate());
        self::assertCount(2, $processedFiles);
    }

    /**
     * @return list<array{PhpVersion}>
     */
    public static function providePhpVersionsLowerThan84(): array
    {
        return [
            [PhpVersion::PHP_82],
            [PhpVersion::PHP_83],
        ];
    }

    #[Test]
    public function generateSanitizerOverride(): void
    {
        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRowOverride($path, $content, $printer);
                }
                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $context = $this->createMysqlGeneratorContext(static fn (string $text): string => 'CTX_' . Utils::sanitizeVariableName($text, false));
        $generator = new TablesGenerator(
            $context,
            static fn (string $text): string => 'GEN_' . Utils::sanitizeVariableName($text, false)
        );

        $processedFiles = iterator_to_array($generator->generate());
        self::assertCount(2, $processedFiles);
    }

    #[Test]
    public function generateWithCustomTypes(): void
    {
        $customType = new CustomType(
            dbType: 'datetime',
            phpType: '\\' . \DateTime::class,
            annotations: ["custom note\n", '@phpstan-ignore property.unusedType'],
            castValueCallback: static fn (Column $column): string => '(new \\DateTimeImmutable((string) $this[\'' . $column->name . '\']))',
        );

        $context = $this->createMysqlGeneratorContext();
        $this->mysqlReflection->method('getCustomTypes')->willReturn([$customType]);

        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRowCustomTypes($path, $content, $printer);
                }
                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $processedFiles = iterator_to_array((new TablesGenerator($context))->generate());

        self::assertCount(2, $processedFiles);
    }

    #[Test]
    public function generateWithGenericCustomTypesAddsVarAnnotation(): void
    {
        $phpType = '\\' . ArrayObject::class . '<string, \\' . DateTimeImmutable::class . '>';
        $customType = new CustomType(
            dbType: 'datetime',
            phpType: $phpType,
            annotations: ["custom note\n", '@phpstan-ignore property.unusedType'],
            castValueCallback: static fn (Column $column): string => '$this[\'' . $column->name . '\'] !== null ? new \\ArrayObject([$this[\'' . $column->name . '\']]) : null',
        );

        $context = $this->createMysqlGeneratorContext();
        $this->mysqlReflection->method('getCustomTypes')->willReturn([$customType]);

        $expectedBaseRowPath = self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php';

        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRowGenericCustomTypes($path, $content, $printer);
                }

                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $processedFiles = iterator_to_array((new TablesGenerator($context))->generate());

        self::assertCount(2, $processedFiles);
    }

    #[Test]
    public function generateWithGenericCustomTypesAddsVarAnnotationPhpLowerThan84(): void
    {
        $phpType = '\\' . ArrayObject::class . '<string, \\' . DateTimeImmutable::class . '>';
        $customType = new CustomType(
            dbType: 'datetime',
            phpType: $phpType,
            annotations: ['custom note'],
            castValueCallback: static fn (Column $column): string => '$this[\'' . $column->name . '\'] !== null ? new \\ArrayObject([$this[\'' . $column->name . '\']]) : null',
        );

        $context = $this->createMysqlGeneratorContext(targetPhpVersion: PhpVersion::PHP_82);
        $this->mysqlReflection->method('getCustomTypes')->willReturn([$customType]);

        $expectedBaseRowPath = self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php';

        $this->fileWriter->method('writeFile')
            ->willReturnCallback(static function (string $path, PhpFile $content, Printer $printer) {
                static $invokedCount = 0;

                if ($invokedCount === 0) {
                    self::checkTableBaseRowGenericCustomTypesPhpLowerThan84($path, $content, $printer);
                }

                if ($invokedCount === 1) {
                    self::checkTableRow($path, $content, $printer);
                }

                ++$invokedCount;

                return true;
            })
        ;

        $processedFiles = iterator_to_array((new TablesGenerator($context))->generate());

        self::assertCount(2, $processedFiles);
    }

    protected static function checkTableRow(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Rows/TestTableActiveRow.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Model\Rows;

            use App\Model\Generated\Rows\TestTableActiveRowBase;

            class TestTableActiveRow extends TestTableActiveRowBase
            {
            }

            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRow(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;

            abstract class TestTableActiveRowBase extends ActiveRow
            {
                /** id column comment */
                public int $id {
                    get => $this['id'];
                }

                /** text column comment */
                public string|null $textColumn {
                    get => $this['text_column'];
                }

                /** date column comment */
                public \DateTime|null $dateColumn {
                    get => $this['date_column'];
                }

                /** bool column comment */
                public bool $boolColumn {
                    get => (bool) $this['bool_column'];
                }

                /** nullable bool column comment */
                public bool|null $nullableBoolColumn {
                    get => $this['nullable_bool_column'] !== null ? (bool) $this['nullable_bool_column'] : null;
                }

                /** float column comment */
                public float $floatColumn {
                    get => $this['float_column'];
                }

                public string $columnWithoutComment {
                    get => $this['column_without_comment'];
                }
            }
            
            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRowOverride(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;

            abstract class TestTableActiveRowBase extends ActiveRow
            {
                /** id column comment */
                public int $GEN_id {
                    get => $this['id'];
                }

                /** text column comment */
                public string|null $GEN_textColumn {
                    get => $this['text_column'];
                }

                /** date column comment */
                public \DateTime|null $GEN_dateColumn {
                    get => $this['date_column'];
                }

                /** bool column comment */
                public bool $GEN_boolColumn {
                    get => (bool) $this['bool_column'];
                }

                /** nullable bool column comment */
                public bool|null $GEN_nullableBoolColumn {
                    get => $this['nullable_bool_column'] !== null ? (bool) $this['nullable_bool_column'] : null;
                }

                /** float column comment */
                public float $GEN_floatColumn {
                    get => $this['float_column'];
                }

                public string $GEN_columnWithoutComment {
                    get => $this['column_without_comment'];
                }
            }

            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRowCustomTypes(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;
            use Nette\Database\Table\Selection;

            abstract class TestTableActiveRowBase extends ActiveRow
            {
                /** id column comment */
                public int $id {
                    get => $this['id'];
                }

                /** text column comment */
                public string|null $textColumn {
                    get => $this['text_column'];
                }

                /**
                 * date column comment
                 * custom note
                 *
                 * @phpstan-ignore property.unusedType
                 */
                public \DateTime|null $dateColumn {
                    get => $this['date_column'];
                }

                /** bool column comment */
                public bool $boolColumn {
                    get => (bool) $this['bool_column'];
                }

                /** nullable bool column comment */
                public bool|null $nullableBoolColumn {
                    get => $this['nullable_bool_column'] !== null ? (bool) $this['nullable_bool_column'] : null;
                }

                /** float column comment */
                public float $floatColumn {
                    get => $this['float_column'];
                }

                public string $columnWithoutComment {
                    get => $this['column_without_comment'];
                }

                /**
                 * @param array<string, mixed> $data
                 * @param Selection<covariant \App\Model\Generated\Rows\TestTableActiveRowBase> $selection
                 */
                public function __construct(array $data, Selection $selection)
                {
                    $data = $this->castValues($data);

                    parent::__construct($data, $selection);
                }

                /**
                 * @param array<string, mixed> $data
                 *
                 * @return array<string, mixed>
                 */
                private function castValues(array $data): array
                {
                    if (array_key_exists('date_column', $data)) {
                        $data['date_column'] = (new \DateTimeImmutable((string) $this['date_column']));
                    }

                    return $data;
                }
            }

            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRowPhpLowerThan84(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;

            /**
             * @property-read int $id id column comment
             * @property-read string|null $textColumn text column comment
             * @property-read \DateTime|null $dateColumn date column comment
             * @property-read bool $boolColumn bool column comment
             * @property-read bool|null $nullableBoolColumn nullable bool column comment
             * @property-read float $floatColumn float column comment
             * @property-read string $columnWithoutComment
             */
            abstract class TestTableActiveRowBase extends ActiveRow
            {
            }

            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRowGenericCustomTypes(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;
            use Nette\Database\Table\Selection;

            abstract class TestTableActiveRowBase extends ActiveRow
            {
                /** id column comment */
                public int $id {
                    get => $this['id'];
                }

                /** text column comment */
                public string|null $textColumn {
                    get => $this['text_column'];
                }

                /**
                 * date column comment
                 * custom note
                 *
                 * @phpstan-ignore property.unusedType
                 * @var \ArrayObject<string, \DateTimeImmutable>|null
                 */
                public \ArrayObject|null $dateColumn {
                    get => $this['date_column'];
                }

                /** bool column comment */
                public bool $boolColumn {
                    get => (bool) $this['bool_column'];
                }

                /** nullable bool column comment */
                public bool|null $nullableBoolColumn {
                    get => $this['nullable_bool_column'] !== null ? (bool) $this['nullable_bool_column'] : null;
                }

                /** float column comment */
                public float $floatColumn {
                    get => $this['float_column'];
                }

                public string $columnWithoutComment {
                    get => $this['column_without_comment'];
                }

                /**
                 * @param array<string, mixed> $data
                 * @param Selection<covariant \App\Model\Generated\Rows\TestTableActiveRowBase> $selection
                 */
                public function __construct(array $data, Selection $selection)
                {
                    $data = $this->castValues($data);

                    parent::__construct($data, $selection);
                }

                /**
                 * @param array<string, mixed> $data
                 *
                 * @return array<string, mixed>
                 */
                private function castValues(array $data): array
                {
                    if (array_key_exists('date_column', $data)) {
                        $data['date_column'] = $this['date_column'] !== null ? new \ArrayObject([$this['date_column']]) : null;
                    }

                    return $data;
                }
            }

            PHP,
            $printer->printFile($content)
        );
    }

    protected static function checkTableBaseRowGenericCustomTypesPhpLowerThan84(string $path, PhpFile $content, Printer $printer): void
    {
        self::assertEquals(self::GeneratedDir . '/App/Model/Generated/Rows/TestTableActiveRowBase.php', $path);
        self::assertEquals(
            <<<'PHP'
            <?php

            /**
             * This file is automatically generated using `cds/nette-model-generator`.
             *
             * Do not edit!
             */

            declare(strict_types=1);

            namespace App\Model\Generated\Rows;

            use Nette\Database\Table\ActiveRow;
            use Nette\Database\Table\Selection;

            /**
             * @property-read int $id id column comment
             * @property-read string|null $textColumn text column comment
             * @property-read \ArrayObject<string, \DateTimeImmutable>|null $dateColumn date column comment, custom note
             * @property-read bool $boolColumn bool column comment
             * @property-read bool|null $nullableBoolColumn nullable bool column comment
             * @property-read float $floatColumn float column comment
             * @property-read string $columnWithoutComment
             */
            abstract class TestTableActiveRowBase extends ActiveRow
            {
                /**
                 * @param array<string, mixed> $data
                 * @param Selection<covariant \App\Model\Generated\Rows\TestTableActiveRowBase> $selection
                 */
                public function __construct(array $data, Selection $selection)
                {
                    $data = $this->castValues($data);

                    parent::__construct($data, $selection);
                }

                /**
                 * @param array<string, mixed> $data
                 *
                 * @return array<string, mixed>
                 */
                private function castValues(array $data): array
                {
                    if (array_key_exists('date_column', $data)) {
                        $data['date_column'] = $this['date_column'] !== null ? new \ArrayObject([$this['date_column']]) : null;
                    }

                    return $data;
                }
            }

            PHP,
            $printer->printFile($content)
        );
    }
}
