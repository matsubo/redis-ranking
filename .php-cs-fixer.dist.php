<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/sample'])
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS3x0' => true,
        '@PER-CS3x0:risky' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one', 'const' => 'one']],
        'declare_strict_types' => true,
        'no_extra_blank_lines' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'strict_comparison' => true,
        'strict_param' => true,
    ])
    ->setFinder($finder);
