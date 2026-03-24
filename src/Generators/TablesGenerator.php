<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\GeneratorContext;
use Closure;
use Nette\Database\Table\ActiveRow;

class TablesGenerator extends Generator
{
    /** @var list<string> list of defined properties in the Nette\Database\Table\ActiveRow */
    protected array $activeRowProperties;

    /**
     * @param Closure(string, bool): string|null $varNameSanitizer
     */
    public function __construct(GeneratorContext $context, Closure|null $varNameSanitizer = null)
    {
        parent::__construct($context, $varNameSanitizer);

        $this->activeRowProperties = $this->getActiveRowDefinedProperties();
    }

    /**
     * @return \Generator<string>
     */
    public function generate(): \Generator
    {
        $this->log('Generating ActiveRows:');

        foreach ($this->context->reflection->getTables() as $table) {
            $this->log("\tTable '{$table->name}':");

            yield from $this->generateForTable($table);
        }
    }

    /**
     * @return array<string> list of changed files
     */
    public function generateForTable(Table $table): array
    {
        $files = [];

        // Base ActiveRows
        $files = array_merge($files, $this->generateActiveRow($table));

        return array_merge($files, $this->generateUserActiveRow($table));
    }

    /**
     * @return array<string>
     */
    public function generateActiveRow(Table $table): array
    {
        $className = $this->context->fileManager->getActiveRowName($table);
        $filePath = $this->context->fileManager->getActiveRowPath($table);

        $this->log("\t\t- {$className}");

        $file = $this->createGeneratedPhpFile();

        $class = $file->addClass($className);
        $class->setAbstract();
        $class->setExtends(ActiveRow::class);
        $class->getNamespace()?->addUse(ActiveRow::class);

        foreach ($this->context->reflection->getColumns($table) as $column) {
            if (!preg_match('/^[a-zA-Z]\w*/', $column->name)) {
                throw new \InvalidArgumentException("Invalid column name: {$column->name}");
            }

            $type = $this->context->reflection->getTypeMapper()->toPhp($column->type, $column->size);

            if ($column->nullable) {
                $type = "{$type}|null";
            }

            // Some properties are already defined in Nette\Database\Table\ActiveRow
            $name = $this->sanitizeVariable($column->name, isConstOrEnum: false);
            $name = in_array($name, $this->activeRowProperties) ? $name . '_' : $name;

            $property = $class->addProperty($name)->setType($type);

            if ($column->comment) {
                $property->addComment($column->comment);
            }

            if (str_contains($type, 'array')) {
                $property->addComment('@phpstan-ignore missingType.iterableValue');
            }

            // Handle custom types
            $customType = $this->getCustomType($column);
            foreach ($customType->annotations ?? [] as $annotation) {
                $property->addComment($annotation);
            }

            // If the cast value callback is defined, we use it in the getter hook.
            if ($customType?->castValueCallback !== null) {
                $property->addHook('get', ($customType->castValueCallback)($column));

                continue;
            }

            if ($type === 'bool') {
                $property->addHook('get', '(bool) $this[\'' . $column->name . '\']');

                continue;
            }

            if ($type === 'bool|null') {
                $property->addHook('get', '$this[\'' . $column->name . '\'] !== null ? (bool) $this[\'' . $column->name . '\'] : null');

                continue;
            }

            $property->addHook('get', '$this[\'' . $column->name . '\']');
        }

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    public function generateUserActiveRow(Table $table): array
    {
        $classNameBase = $this->context->fileManager->getActiveRowName($table);
        $className = $this->context->fileManager->getUserActiveRowName($table);
        $filePath = $this->context->fileManager->getUserActiveRowPath($table);

        // Skip if the file already exists, we do not want to overwrite user changes.
        if (file_exists($this->context->fileManager->getUserActiveRowPath($table))) {
            $this->log("\t\t- Skipping {$className}, file already exists");

            return [];
        }

        $this->log("\t\t- {$className}");

        $file = $this->createPhpFile();

        $class = $file->addClass($className);
        $class->setExtends($classNameBase);
        $class->getNamespace()?->addUse($classNameBase);

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * Returns a list of defined properties in the Nette\Database\Table\ActiveRow class.
     *
     * @return list<string>
     */
    private function getActiveRowDefinedProperties(): array
    {
        $reflection = new \ReflectionClass(ActiveRow::class);

        return array_map(static fn (\ReflectionProperty $prop) => $prop->name, $reflection->getProperties());
    }

    /**
     * Returns the custom type for the given column, or null if no custom type is defined for it.
     */
    private function getCustomType(Column $column): CustomType|null
    {
        return current(array_filter(
            array: $this->context->reflection->getCustomTypes(),
            callback: static fn (CustomType $customType) => $customType->dbType === $column->type,
        )) ?: null;
    }
}
