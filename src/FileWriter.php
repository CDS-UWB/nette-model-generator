<?php

namespace Cds\NetteModelGenerator;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;

class FileWriter implements Writer
{
    public function writeFile(string $path, PhpFile|string $content, Printer $printer): bool
    {
        if ($content instanceof PhpFile) {
            $content = $printer->printFile($content);
        }

        $dirName = dirname($path);
        if (!is_dir($dirName) && !mkdir($dirName, 0755, true) && !is_dir($dirName)) {
            throw new \RuntimeException("Directory \"{$dirName}\" was not created");
        }

        if (file_exists($path) && md5_file($path) === md5($content)) {
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }
}
