<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PER-CS3x0' => true,
        'php_unit_test_case_static_method_calls' => true
    ])
    ->setFinder($finder)
;
