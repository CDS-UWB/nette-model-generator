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
            ->setReturnType('string')
            ->setBody(
                <<<'PHP'
                $class = self::ActiveRowNamespace . '\\' . $this->snakeToPascalCase($tableName) . 'ActiveRow';
                
                return class_exists($class) ? $class : ActiveRow::class;
                PHP
            )
        ;

        $class->addMethod('snakeToPascalCase')
            ->setParameters([
                (new Parameter('input'))->setType('string'),
            ])
            ->setReturnType('string')
            ->setBody(
                <<<'PHP'
                return str_replace('_', '', mb_convert_case($input, MB_CASE_TITLE, 'UTF-8'));
                PHP
            )
        ;

        if ($this->writeFile($path, $file)) {
            return [$path];
        }

        return [];
    }
}
