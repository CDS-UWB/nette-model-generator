<?php

declare(strict_types=1);

namespace Tests\Integration\Trait;

trait OutputDir
{
    /** @var list<string> */
    protected array $outputDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->outputDir = [__DIR__, '..', 'output', $this->dbName];
    }

    public function tearDown(): void
    {
        $this->removeDir(implode(DIRECTORY_SEPARATOR, $this->outputDir));

        parent::tearDown();
    }
}
