<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/assets',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Skip vendor and cache directories
        __DIR__ . '/vendor',
        __DIR__ . '/var',
        // Skip if you want to exclude specific rules
        // ExplicitNullableParamTypeRector::class,
    ])
    // PHASE 1: Configuration conservatrice (ACTUELLE)
    // Activez les suggestions pour utiliser les fonctionnalités modernes de PHP
    ->withPhpSets(php84: true)

    // Type Coverage: Ajoute des types manquants (propriétés, retours, paramètres)
    // Niveau 10 = suggestions de base, non intrusives
    ->withTypeCoverageLevel(10)

    // Dead Code: Supprime le code mort (variables non utilisées, imports inutiles)
    // Niveau 10 = nettoyage basique et sûr
    ->withDeadCodeLevel(10)

    // Code Quality: Améliorations de la qualité (simplifications, modernisations)
    // Niveau 10 = améliorations conservatrices
    ->withCodeQualityLevel(10)

    // Support des frameworks utilisés
    ->withComposerBased(
        symfony: true,
        doctrine: true,
        phpunit: true,
    )
    ->withSymfonyContainerPhp(
        __DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.php'
    )

    // Ensure strict types are declared (you already have this everywhere)
    ->withPreparedSets(
        deadCode: false,          // Géré par withDeadCodeLevel
        codeQuality: false,       // Géré par withCodeQualityLevel
        typeDeclarations: false,  // Géré par withTypeCoverageLevel
        privatization: true,      // Rendre les méthodes/propriétés privées quand possible
        earlyReturn: true,        // Transformer les if imbriqués en early returns
        strictBooleans: false,    // Peut être trop strict au début
    );

// PHASE 2: Configuration intermédiaire (à activer après correction Phase 1)
// ->withTypeCoverageLevel(20)
// ->withDeadCodeLevel(20)
// ->withCodeQualityLevel(20)
// ->withPreparedSets(strictBooleans: true)

// PHASE 3: Configuration agressive (pour projet mature)
// ->withTypeCoverageLevel(30)
// ->withDeadCodeLevel(30)
// ->withCodeQualityLevel(30)
// ->withPreparedSets(
//     strictBooleans: true,
//     rectorPreset: true,
//     phpunit: true,
// )
