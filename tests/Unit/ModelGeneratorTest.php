<?php

namespace Tests\Unit;

use Cds\NetteModelGenerator\ModelGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ModelGenerator::class)]
class ModelGeneratorTest extends TestCase
{
    #[Test]
    public function addGeneratorAddsGeneratorToArray(): void
    {
        $modelGenerator = new ModelGenerator();
        $generator = new TestGenerator(['First']);

        $modelGenerator->addGenerator($generator);

        // Verify by calling run and checking output
        $results = iterator_to_array($modelGenerator->run(), false);
        $this->assertCount(1, $results);
        $this->assertEquals('First', $results[0]);
    }

    #[Test]
    public function addGeneratorMultipleGenerators(): void
    {
        $modelGenerator = new ModelGenerator();
        $generator1 = new TestGenerator(['First']);
        $generator2 = new TestGenerator(['Second']);
        $generator3 = new TestGenerator(['Third']);

        $modelGenerator->addGenerator($generator1);
        $modelGenerator->addGenerator($generator2);
        $modelGenerator->addGenerator($generator3);

        $results = iterator_to_array($modelGenerator->run(), false);
        $this->assertCount(3, $results);
        $this->assertEquals('First', $results[0]);
        $this->assertEquals('Second', $results[1]);
        $this->assertEquals('Third', $results[2]);
    }

    #[Test]
    public function runYieldsFromAllGenerators(): void
    {
        $modelGenerator = new ModelGenerator();
        $generator1 = new TestGenerator(['Output1', 'Output2']);
        $generator2 = new TestGenerator(['Output3']);

        $modelGenerator->addGenerator($generator1);
        $modelGenerator->addGenerator($generator2);

        $results = iterator_to_array($modelGenerator->run(), false);
        $this->assertCount(3, $results);
        $this->assertEquals('Output1', $results[0]);
        $this->assertEquals('Output2', $results[1]);
        $this->assertEquals('Output3', $results[2]);
    }

    #[Test]
    public function runWithoutGeneratorsYieldsNothing(): void
    {
        $modelGenerator = new ModelGenerator();

        $results = iterator_to_array($modelGenerator->run(), false);

        $this->assertCount(0, $results);
    }
}
