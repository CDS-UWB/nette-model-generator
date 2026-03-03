<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\GeneratorContext;
use Cds\NetteModelGenerator\Utils;
use Nette\Database\Table\ActiveRow;

class TablesGenerator extends Generator
{
    /** @var list<string> list of defined properties in the Nette\Database\Table\ActiveRow */
    protected array $activeRowProperties;

    public function __construct(GeneratorContext $context)
    {
        parent::__construct($context);

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
            $name = Utils::snakeToCamelCase($column->name);
            $name = in_array($name, $this->activeRowProperties) ? $name . '_' : $name;

            $property = $class->addProperty($name)->setType($type);

            if ($column->comment) {
                $property->addComment($column->comment);
            }

            if (str_contains($type, 'array')) {
                $property->addComment('@phpstan-ignore missingType.iterableValue');
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
}
