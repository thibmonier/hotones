<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->exclude(['var', 'vendor'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        // ADR-0002: alignement vertical désactivé pour éviter les conflits avec Mago.
        // La migration repo-wide est portée par TECH-DEBT-002 (sprint-003) — pas dans cette PR.
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => ['statements' => ['return']],
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => true,
        ],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_order' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments']],
        'yoda_style' => false,
    ])
    ->setFinder($finder);
