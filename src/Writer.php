<?php

namespace Cds\NetteModelGenerator;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;

interface Writer
{
    public function writeFile(string $path, string|PhpFile $content, Printer $printer): bool;
}
