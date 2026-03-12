<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreUnreadableDirs()
    ->exclude('vendor')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules(Cds\PhpCodeStyle\rules())
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
