<?php

namespace Tests\Unit;

use Cds\NetteModelGenerator\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Utils::class)]
class UtilsTest extends TestCase
{
    #[Test]
    #[DataProvider('snakeToPascalProvider')]
    public function snakeToPascalCase(string $input, string $expected): void
    {
        $this->assertEquals($expected, Utils::snakeToPascalCase($input));
    }

    /**
     * @return array<list<string>>
     */
    public static function snakeToPascalProvider(): array
    {
        return [
            ['test', 'Test'],
            ['test_name', 'TestName'],
            ['test_name_2', 'TestName2'],
        ];
    }

    #[Test]
    #[DataProvider('snakeToCamelCaseProvider')]
    public function snakeToCamelCase(string $input, string $expected): void
    {
        $this->assertEquals($expected, Utils::snakeToCamelCase($input));
    }

    /**
     * @return array<list<string>>
     */
    public static function snakeToCamelCaseProvider(): array
    {
        return [
            ['test', 'test'],
            ['test_name', 'testName'],
            ['test_name_2', 'testName2'],
            ['api_key', 'apiKey'],
            ['user_id', 'userId'],
        ];
    }

    #[Test]
    #[DataProvider('convertToAsciiProvider')]
    public function convertToAscii(string $input, string $expected): void
    {
        $this->assertEquals($expected, Utils::convertToAscii($input));
    }

    /**
     * @return array<list<string>>
     */
    public static function convertToAsciiProvider(): array
    {
        return [
            ['test', 'test'],
            ['café', 'cafe'],
            ['naïve', 'naive'],
            ['schöne', 'schone'],
            ['test123', 'test123'],
        ];
    }

    #[Test]
    public function sanitizeVariableNameWithValidInput(): void
    {
        $this->assertEquals('TestName', Utils::sanitizeVariableName('test_name', true));
        $this->assertEquals('TestName', Utils::sanitizeVariableName('test-name', true));
        $this->assertEquals('UserProfile', Utils::sanitizeVariableName('user profile', true));
    }

    #[Test]
    public function sanitizeVariableNameWithNumberAtStart(): void
    {
        $this->assertEquals('_123Test', Utils::sanitizeVariableName('123_test', true));
    }

    #[Test]
    public function sanitizeVariableNameWithMultipleSeparators(): void
    {
        $this->assertEquals('TestName', Utils::sanitizeVariableName('test__name', true));
        $this->assertEquals('TestName', Utils::sanitizeVariableName('test---name', true));
    }

    #[Test]
    public function sanitizeVariableNameWithAccentedCharacters(): void
    {
        $this->assertEquals('Cafe', Utils::sanitizeVariableName('café', true));
    }

    public function sanitizeVariableNameNotEnum(): void
    {
        $this->assertEquals('testName', Utils::sanitizeVariableName('test_name', false));
        $this->assertEquals('testName', Utils::sanitizeVariableName('test-name', false));
        $this->assertEquals('userProfile', Utils::sanitizeVariableName('user profile', false));
    }
}
