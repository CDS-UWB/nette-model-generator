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

        $class->addProperty('namespaces')
            ->setValue([])
            ->setType('array')
            ->addComment('@var list<string>')
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
            ->addComment('@param array<string|int, mixed> $data')
            ->addComment('@phpstan-ignore missingType.generics')
        ;

        $class->addMethod('registerNamespace')
            ->setParameters(
                [(new Parameter('namespace'))->setType('string')],
            )
            ->setBody(<<<'PHP'
            if (!in_array($namespace, $this->namespaces)){
                $this->namespaces[] = $namespace;
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
                $result = [];

                foreach ($this->namespaces as $namespace)
                {
                    if (!str_contains($tableName, '.')) {
                        $result[] = $namespace . '\\' . $this->snakeToPascalCase($tableName) . 'ActiveRow';
                        continue;
                    }

                    $parts = explode('.', $tableName);
                    $className = array_pop($parts);
                    $classNameWithSchema = str_replace('.', '\\', $tableName);

                    $result[] = $namespace . '\\' . $this->snakeToPascalCase($classNameWithSchema) . 'ActiveRow';
                    $result[] = $namespace . '\\' . $this->snakeToPascalCase($className) . 'ActiveRow';
                }

                return $result;
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
