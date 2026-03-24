<?php

namespace Tests\Unit\TypeMappers;

use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Reflections\PostgreSqlReflection;
use Cds\NetteModelGenerator\TypeMappers\PostgreSqlTypeMapper;
use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PostgreSqlTypeMapper::class)]
class PostgreSqlTypeMapperTest extends TestCase
{
    /** @var PostgreSqlReflection&\PHPUnit\Framework\MockObject\Stub */
    private PostgreSqlReflection $postgreSqlReflection;

    public function setUp(): void
    {
        parent::setUp();

        $this->postgreSqlReflection = $this->createStub(PostgreSqlReflection::class);
    }

    /**
     * @param string|class-string $expected
     */
    #[Test]
    #[DataProvider('baseTypeProvider')]
    public function baseType(string $postgreSqlType, string $expected): void
    {
        $mapper = new PostgreSqlTypeMapper($this->postgreSqlReflection);

        $this->assertEquals($expected, $mapper->toPhp($postgreSqlType));
    }

    /**
     * @return array<int, array<int, string|class-string>>
     */
    public static function baseTypeProvider(): array
    {
        return [
            ['integer', 'int'],
            ['decimal', 'int'],
            ['float', 'float'],
            ['float8', 'float'],
            ['text', 'string'],
            ['boolean', 'bool'],
            ['date', '\\' . \DateTime::class],
            ['interval', '\\' . DateInterval::class],
        ];
    }

    #[Test]
    public function customType(): void
    {
        $mapper = new PostgreSqlTypeMapper(
            reflection: $this->postgreSqlReflection,
            customTypes: [
                new CustomType(
                    dbType: 'int',
                    phpType: '\\' . \stdClass::class
                ),
            ]
        );

        $this->assertEquals('\stdClass', $mapper->toPhp('int'));
    }

    #[Test]
    public function unknownType(): void
    {
        $mapper = new PostgreSqlTypeMapper($this->postgreSqlReflection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: unknown');

        $mapper->toPhp('unknown');
    }
}
