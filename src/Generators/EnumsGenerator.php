<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Utils;
use Nette\PhpGenerator\EnumCase;

class EnumsGenerator extends Generator
{
    public function generate(): \Generator
    {
        $this->log('Generating enums:');

        foreach ($this->context->reflection->getEnums() as $enum) {
            yield from $this->generateEnum($enum);
        }
    }

    /**
     * @return array<string>
     */
    private function generateEnum(Enum $enum): array
    {
        $name = $this->context->fileManager->getEnumName($enum);
        $filePath = $this->context->fileManager->getEnumPath($enum);

        $this->log("\t- {$name}");

        $file = $this->createGeneratedPhpFile();

        $sanitizeCallback = $this->context->varNameSanitizer ?? Utils::sanitizeVariableName(...);

        $cases = [];

        $enumValues = [];
        foreach ($enum->values as $value) {
            $case = $sanitizeCallback($value);
            if (in_array($case, $cases)) {
                $this->log("WARNING: Case ({$case}) for value '{$value}' already exists. Consider using custom variable name sanitizer.");

                continue;
            }

            $enumValues[] = (new EnumCase($case))->setValue($value);

            $cases[] = $case;
        }

        $enum = $file->addEnum($name)
            ->setType('string')
            ->setCases($enumValues)
        ;

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }
}
