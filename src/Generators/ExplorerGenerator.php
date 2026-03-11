<?php

namespace Cds\NetteModelGenerator\Generators;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\PhpGenerator\Parameter;

class ExplorerGenerator extends Generator
{
    public function generate(): \Generator
    {
        yield from $this->generateExplorer();
    }

    /**
     * @return array<string>
     */
    public function generateExplorer(): array
    {
        $file = $this->createGeneratedPhpFile();

        $className = $this->context->fileManager->getExplorerName();
        $path = $this->context->fileManager->getExplorerPath();
        $activeRowNamespace = $this->context->fileManager->getActiveRowNamespace();

        $this->log("Generating {$className} class.");

        $class = $file->addClass($className);
        $class->setExtends(Explorer::class);
        $class->getNamespace()
            ?->addUse(ActiveRow::class)
            ?->addUse(Selection::class)
        ;
        $class->addConstant('ActiveRowNamespace', $activeRowNamespace)
            ->setPrivate()
            ->setType('string')
        ;

        $class->addMethod('createActiveRow')
            ->setParameters([
                (new Parameter('data'))->setType('array'),
                (new Parameter('selection'))->setType(Selection::class),
            ])
            ->setReturnType(ActiveRow::class)
            ->setBody(
                <<<'PHP'
                $class = $this->tableToClass($selection->getName());
                $row = new $class($data, $selection);
                
                if (!$row instanceof ActiveRow) {
                    throw new \LogicException('ActiveRow must be instance of ' . ActiveRow::class);
                }
                
                return $row;
                PHP
            )
            ->addComment('@param array<string|int, mixed> $data')
            ->addComment('@phpstan-ignore missingType.generics')
        ;

        $class->addMethod('tableToClass')
            ->setParameters([
                (new Parameter('tableName'))->setType('string'),
            ])
            ->setVisibility('protected')
            ->setReturnType('string')
            ->setBody(
                <<<'PHP'
                $possibleClasses = $this->getPossibleClassNames($tableName);

                foreach ($possibleClasses as $class) {
                    if (class_exists($class)) {
                        return $class;
                    }
                }
                
                return ActiveRow::class;
                PHP
            )
        ;

        $class->addMethod('snakeToPascalCase')
            ->setParameters([
                (new Parameter('input'))->setType('string'),
            ])
            ->setVisibility('protected')
            ->setReturnType('string')
            ->setBody(
                <<<'PHP'
                return str_replace('_', '', mb_convert_case($input, MB_CASE_TITLE, 'UTF-8'));
                PHP
            )
        ;

        $class->addMethod('getPossibleClassNames')
            ->setParameters([
                (new Parameter('tableName'))->setType('string'),
            ])
            ->setVisibility('protected')
            ->setReturnType('array')
            ->setBody(
                <<<'PHP'
                if (!str_contains($tableName, '.')){
                    return [self::ActiveRowNamespace . '\\' . $this->snakeToPascalCase($tableName) . 'ActiveRow'];
                }

                $parts = explode('.', $tableName);
                $className = array_pop($parts);
                $classNameWithSchema = str_replace('.', '\\', $tableName);

                return [
                    self::ActiveRowNamespace . '\\' . $this->snakeToPascalCase($classNameWithSchema) . 'ActiveRow',
                    self::ActiveRowNamespace . '\\' . $this->snakeToPascalCase($className) . 'ActiveRow',
                ];
                PHP
            )
            ->addComment('@return list<string>')
        ;

        if ($this->writeFile($path, $file)) {
            return [$path];
        }

        return [];
    }
}
