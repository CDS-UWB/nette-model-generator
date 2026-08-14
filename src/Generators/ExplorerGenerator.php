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

        $this->log("Generating {$className} class.");

        $class = $file->addClass($className);
        $class->setExtends($this->context->explorerClass ?? Explorer::class);

        if ($this->context->explorerClass !== null) {
            if ($this->writeFile($path, $file)) {
                return [$path];
            }

            return [];
        }

        $class->getNamespace()
            ?->addUse(ActiveRow::class)
            ?->addUse(Selection::class)
        ;

        $class->addProperty('tables')
            ->setValue([])
            ->setType('array')
            ->addComment('@var array{string, class-string<ActiveRow>}|array{}')
            ->setVisibility('protected')
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
            ->addComment("@param array<string|int, mixed> \$data\n")
        ;

        $class->addMethod('registerTable')
            ->setParameters(
                [
                    (new Parameter('table'))->setType('string'),
                    (new Parameter('activeRowClass'))->setType('string'),
                ],
            )
            ->setBody(<<<'PHP'
            if (!array_key_exists($table, $this->tables)) {
                $this->tables[$table] = $activeRowClass;
            }
            PHP)
            ->setReturnType('void')
        ;

        $class->addMethod('tableToClass')
            ->setParameters([
                (new Parameter('tableName'))->setType('string'),
            ])
            ->setVisibility('protected')
            ->setReturnType('string')
            ->setBody(
                <<<'PHP'
                return $this->tables[$tableName] ?? ActiveRow::class;
                PHP
            )
        ;

        if ($this->writeFile($path, $file)) {
            return [$path];
        }

        return [];
    }
}
