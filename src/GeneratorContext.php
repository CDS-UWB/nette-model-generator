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
     * @param Closure(string, bool): string $varNameSanitizer   global variable name sanitizer
     * @param class-string                  $managerClass       custom Manager class; when provided, the generated manager extends it and omits method generation
     * @param class-string                  $explorerClass      custom Explorer class; when provided, the generated explorer extends it and omits method generation
     * @param class-string                  $dbConventionsClass custom DatabaseConventions class; when provided, the generated conventions extends it
     */
    public function __construct(
        public Reflection $reflection,
        public FileManager $fileManager,
        public Writer $fileWriter = new FileWriter(),
        public Printer $printer = new PsrPrinter(),
        public Logger $logger = new Logger(),
        public Closure|null $varNameSanitizer = null,
        public PhpVersion $targetPhpVersion = PhpVersion::PHP_84,
        public string|null $managerClass = null,
        public string|null $explorerClass = null,
        public string|null $dbConventionsClass = null,
    ) {
    }
}
