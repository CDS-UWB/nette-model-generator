<?php

namespace Tests\Unit\Data;

use Cds\NetteModelGenerator\Data\Table;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Table::class)]
class TableTest extends TestCase
{
    #[Test]
    public function getFullName(): void
    {
        $tableContext = new Table('table', 'schema');
        $this->assertEquals('schema.table', $tableContext->getFullName());
    }
}
