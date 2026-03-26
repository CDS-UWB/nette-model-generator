<?php

namespace Cds\NetteModelGenerator;

use Cds\NetteModelGenerator\Enum\PhpVersion;
use Cds\NetteModelGenerator\Reflections\Reflection;
use Closure;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;

final readonly class GeneratorContext
{
    /**
     * @param Closure(string, bool): string $varNameSanitizer
     */
    public function __construct(
        public Reflection $reflection,
        public FileManager $fileManager,
        public Writer $fileWriter = new FileWriter(),
        public Printer $printer = new PsrPrinter(),
        public Logger $logger = new Logger(),
        public Closure|null $varNameSanitizer = null,
        public PhpVersion $targetPhpVersion = PhpVersion::PHP_84
    ) {
    }
}
