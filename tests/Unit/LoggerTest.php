<?php

namespace Tests\Unit;

use Cds\NetteModelGenerator\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Logger::class)]
class LoggerTest extends TestCase
{
    #[Test]
    public function logSingleArgument(): void
    {
        ob_start();
        $logger = new Logger();
        $logger->log('test message');
        $output = ob_get_clean();

        $this->assertEquals('test message' . PHP_EOL, $output);
    }

    #[Test]
    public function logMultipleArguments(): void
    {
        ob_start();
        $logger = new Logger();
        $logger->log('message', ' ', 'with', ' ', 'multiple', ' ', 'parts');
        $output = ob_get_clean();

        $this->assertEquals('message with multiple parts' . PHP_EOL, $output);
    }
}
