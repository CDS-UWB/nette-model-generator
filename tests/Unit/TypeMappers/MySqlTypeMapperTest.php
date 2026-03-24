<?php

namespace Tests\Unit\TypeMappers;

use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Reflections\MySqlReflection;
use Cds\NetteModelGenerator\TypeMappers\MySqlTypeMapper;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MySqlTypeMapper::class)]
class MySqlTypeMapperTest extends TestCase
{
    /** @var MySqlReflection&\PHPUnit\Framework\MockObject\Stub */
    private MySqlReflection $mysqlReflection;

    public function setUp(): void
    {
        parent::setUp();

        $this->mysqlReflection = $this->createStub(MySqlReflection::class);
    }

    /**
     * @param string|class-string $expected
     */
    #[Test]
    #[DataProvider('baseTypeProvider')]
    public function baseType(string $mysqlType, string $expected): void
    {
        $mapper = new MySqlTypeMapper($this->mysqlReflection);

        $this->assertEquals($expected, $mapper->toPhp($mysqlType));
    }

    /**
     * @return array<int, array<int, string|class-string>>
     */
    public static function baseTypeProvider(): array
    {
        return [
            ['integer', 'int'],
            ['INTEGER', 'int'],
            ['float', 'float'],
            ['varchar', 'string'],
            ['boolean', 'bool'],
            ['date', '\\' . DateTime::class],
            ['json', 'string'],
        ];
    }

    #[Test]
    public function customType(): void
    {
        $mapper = new MySqlTypeMapper(
            reflection: $this->mysqlReflection,
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
        $mapper = new MySqlTypeMapper($this->mysqlReflection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: unknown');

        $mapper->toPhp('unknown');
    }
}
