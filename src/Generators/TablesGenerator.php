<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\CustomType;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Enum\PhpVersion;
use Cds\NetteModelGenerator\GeneratorContext;
use Closure;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;

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
        $class->getNamespace()
            ?->addUse(ActiveRow::class)
        ;

        $castValuesBody = [];

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

            $customType = $this->getCustomType($column);

            if ($customType?->castValueCallback !== null) {
                $castValuesBody[] = "\$data['{$column->name}'] = " . ($customType->castValueCallback)($column) . ';';
            }

            if ($this->context->targetPhpVersion->isFeatureSupported(PhpVersion::PHP_84)) {
                $name = in_array($name, $this->activeRowProperties) ? $name . '_' : $name;

                $this->addPropertyHook(
                    class: $class,
                    name: $name,
                    type: $type,
                    column: $column,
                    customType: $customType
                );
            } else {
                $this->addClassAnnotation(
                    class: $class,
                    name: $name,
                    type: $type,
                    column: $column,
                    customType: $customType
                );
            }
        }

        if (!empty($castValuesBody)) {
            $class->getNamespace()?->addUse(Selection::class);
            $class->addMethod('__construct')
                ->setParameters([
                    (new Parameter('data'))->setType('array'),
                    (new Parameter('selection'))->setType(Selection::class),
                ])
                ->setBody(
                    <<<'PHP'
                $data = $this->castValues($data);

                parent::__construct($data, $selection);
                PHP
                )
                ->addComment('@param array<string, mixed> $data')
                ->addComment("@param Selection<covariant \\{$className}> \$selection")
            ;

            $class->addMethod('castValues')
                ->setVisibility('private')
                ->setParameters([
                    (new Parameter('data'))->setType('array'),
                ])
                ->setReturnType('array')
                ->setBody(implode("\n", $castValuesBody) . "\n\nreturn \$data;")
                ->addComment("@param array<string, mixed> \$data\n")
                ->addComment('@return array<string, mixed>')
            ;
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

    /**
     * Adds a property hook to the class for a specific column.
     */
    private function addPropertyHook(ClassType $class, string $name, string $type, Column $column, CustomType|null $customType): void
    {
        $property = $class->addProperty($name)->setType($type);

        if ($column->comment) {
            $property->addComment($column->comment);
        }

        if (str_contains($type, 'array')) {
            $property->addComment('@phpstan-ignore missingType.iterableValue');
        }

        foreach ($customType->annotations ?? [] as $annotation) {
            $property->addComment($annotation);
        }

        if ($type === 'bool') {
            $property->addHook('get', '(bool) $this[\'' . $column->name . '\']');

            return;
        }

        if ($type === 'bool|null') {
            $property->addHook('get', '$this[\'' . $column->name . '\'] !== null ? (bool) $this[\'' . $column->name . '\'] : null');

            return;
        }

        $property->addHook('get', '$this[\'' . $column->name . '\']');
    }

    /**
     * Adds a class annotation with the column properties.
     */
    private function addClassAnnotation(ClassType $class, string $name, string $type, Column $column, CustomType|null $customType): void
    {
        $comment = "@property-read {$type} \${$name}";
        if ($column->comment) {
            $comment .= " {$column->comment}";
        }

        $class->addComment($comment);
    }
}
