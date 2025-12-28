#!/bin/bash
# Scripts d'aide pour le Sprint PHP 8.5 Optimizations
# Usage: ./scripts/php85-optimization-helpers.sh [command]

set -e

# Couleurs pour output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Fonction pour afficher les métriques baseline
baseline_metrics() {
    echo -e "${YELLOW}=== Métriques Baseline ===${NC}"

    echo -e "\n${GREEN}1. Lignes de code:${NC}"
    cloc src/Entity/ src/Service/ --quiet

    echo -e "\n${GREEN}2. Tests coverage:${NC}"
    docker compose exec app composer test -- --coverage-text --colors=never | grep "Lines:"

    echo -e "\n${GREEN}3. PHPStan niveau actuel:${NC}"
    docker compose exec app composer phpstan 2>&1 | grep "^\[" | head -1

    echo -e "\n${GREEN}4. Performance métriques (temps d'exécution):${NC}"
    echo "Lancement du calcul des métriques..."
    time docker compose exec app php bin/console app:metrics:dispatch --year=2025 -q

    echo -e "\n${YELLOW}Baseline sauvegardée dans .metrics-baseline.txt${NC}"
    date > .metrics-baseline.txt
    echo "Voir ci-dessus pour les valeurs" >> .metrics-baseline.txt
}

# Fonction pour chercher les patterns property hooks candidats
find_property_hook_candidates() {
    echo -e "${YELLOW}=== Recherche de candidats pour Property Hooks ===${NC}\n"

    echo -e "${GREEN}Entities avec getters/setters simples:${NC}"

    # Chercher les patterns getter/setter
    for file in src/Entity/*.php; do
        getter_count=$(grep -c "public function get" "$file" 2>/dev/null || echo 0)
        setter_count=$(grep -c "public function set" "$file" 2>/dev/null || echo 0)

        if [ "$getter_count" -gt 5 ] && [ "$setter_count" -gt 5 ]; then
            echo "  📄 $(basename $file): $getter_count getters, $setter_count setters"
        fi
    done

    echo -e "\n${GREEN}Top 5 fichiers avec le plus de boilerplate:${NC}"
    for file in src/Entity/*.php; do
        lines=$(wc -l < "$file")
        getter_count=$(grep -c "public function get" "$file" 2>/dev/null || echo 0)
        setter_count=$(grep -c "public function set" "$file" 2>/dev/null || echo 0)
        total_methods=$((getter_count + setter_count))

        if [ "$total_methods" -gt 0 ]; then
            echo "$total_methods $lines $(basename $file)"
        fi
    done | sort -rn | head -5 | while read methods lines filename; do
        echo "  🎯 $filename: $methods méthodes ($lines lignes total)"
    done
}

# Fonction pour trouver les N+1 queries
find_n_plus_one() {
    echo -e "${YELLOW}=== Recherche de N+1 Query Patterns ===${NC}\n"

    echo -e "${GREEN}Services avec boucles imbriquées:${NC}"
    grep -r "foreach.*foreach" src/Service/*.php | while IFS=: read -r file line; do
        echo "  ⚠️  $(basename $file): $line"
    done

    echo -e "\n${GREEN}Appels de repository dans des boucles:${NC}"
    for file in src/Service/*.php; do
        # Chercher des patterns de N+1
        if grep -A 5 "foreach" "$file" | grep -q "Repository\|find\|->get"; then
            echo "  🔴 $(basename $file): Possible N+1 détecté"
        fi
    done
}

# Fonction pour chercher les enums candidates
find_enum_candidates() {
    echo -e "${YELLOW}=== Recherche de candidats pour Enums ===${NC}\n"

    echo -e "${GREEN}Constantes de classe (candidats enum):${NC}"
    grep -r "public const.*OPTIONS\|public const STATUS" src/Entity/*.php | while IFS=: read -r file line; do
        echo "  📋 $(basename $file): $(echo $line | cut -d' ' -f3-5)"
    done
}

# Fonction pour benchmark une méthode spécifique
benchmark_method() {
    local class=$1
    local method=$2

    echo -e "${YELLOW}=== Benchmark: $class::$method ===${NC}"

    cat > /tmp/benchmark.php <<EOF
<?php
require_once '/var/www/html/vendor/autoload.php';

use Symfony\Component\Stopwatch\Stopwatch;

\$kernel = new App\Kernel('dev', true);
\$kernel->boot();
\$container = \$kernel->getContainer();

\$service = \$container->get('$class');
\$stopwatch = new Stopwatch();

\$stopwatch->start('benchmark');
for (\$i = 0; \$i < 100; \$i++) {
    \$service->$method();
}
\$event = \$stopwatch->stop('benchmark');

echo "Durée totale: " . \$event->getDuration() . "ms\n";
echo "Mémoire: " . round(\$event->getMemory() / 1024 / 1024, 2) . "MB\n";
echo "Moyenne par appel: " . round(\$event->getDuration() / 100, 2) . "ms\n";
EOF

    docker compose exec app php /tmp/benchmark.php
}

# Fonction pour vérifier la compatibilité Doctrine avec property hooks
test_doctrine_compatibility() {
    echo -e "${YELLOW}=== Test compatibilité Doctrine avec Property Hooks ===${NC}"

    cat > /tmp/test-hooks.php <<EOF
<?php
require_once '/var/www/html/vendor/autoload.php';

\$kernel = new App\Kernel('test', true);
\$kernel->boot();
\$em = \$kernel->getContainer()->get('doctrine.orm.entity_manager');

// Test hydratation
echo "Test hydratation entities...\n";
\$user = \$em->getRepository(App\Entity\User::class)->findOneBy([]);
if (\$user) {
    echo "✅ Hydratation User: OK\n";
    echo "  - ID: " . \$user->getId() . "\n";
    echo "  - Email: " . \$user->getEmail() . "\n";
}

\$project = \$em->getRepository(App\Entity\Project::class)->findOneBy([]);
if (\$project) {
    echo "✅ Hydratation Project: OK\n";
    echo "  - ID: " . \$project->getId() . "\n";
    echo "  - Name: " . \$project->getName() . "\n";
}

echo "\nTest sérialisation...\n";
\$serializer = \$kernel->getContainer()->get('serializer');
\$json = \$serializer->serialize(\$project, 'json', ['groups' => ['project:read']]);
echo "✅ Sérialisation: " . strlen(\$json) . " bytes\n";

echo "\nTest désérialisation...\n";
\$newProject = \$serializer->deserialize(\$json, App\Entity\Project::class, 'json');
echo "✅ Désérialisation: OK\n";
EOF

    docker compose exec app php /tmp/test-hooks.php
}

# Fonction pour convertir automatiquement les IDs en asymmetric visibility
convert_ids_to_asymmetric() {
    echo -e "${YELLOW}=== Conversion automatique des IDs en asymmetric visibility ===${NC}"

    for file in src/Entity/*.php; do
        # Backup
        cp "$file" "$file.backup"

        # Remplacement du pattern
        sed -i.tmp '
            /private.*\$id = null;/ {
                N
                N
                /public function getId()/ {
                    s/private \(.*\$id = null;\)/public private(set) \1/
                    s/\n.*public function getId.*\n.*return \$this->id;\n.*}//
                }
            }
        ' "$file"

        rm -f "$file.tmp"

        # Vérifier si changement
        if ! diff -q "$file" "$file.backup" > /dev/null 2>&1; then
            echo "  ✅ Converti: $(basename $file)"
        fi
    done

    echo -e "\n${GREEN}Conversion terminée. Backups: *.backup${NC}"
    echo -e "${YELLOW}⚠️  Exécutez les tests avant de valider!${NC}"
}

# Fonction pour générer un rapport de progression
progress_report() {
    echo -e "${YELLOW}=== Rapport de progression Sprint PHP 8.5 ===${NC}\n"

    echo -e "${GREEN}Phase 1 - Property Hooks:${NC}"
    hooks_count=$(grep -r "public.*{$" src/Entity/*.php 2>/dev/null | wc -l || echo 0)
    echo "  Property hooks implémentés: $hooks_count"

    echo -e "\n${GREEN}Phase 2 - Asymmetric Visibility:${NC}"
    asymmetric_count=$(grep -r "public private(set)" src/Entity/*.php 2>/dev/null | wc -l || echo 0)
    echo "  Propriétés asymétriques: $asymmetric_count"

    echo -e "\n${GREEN}Phase 4 - Enums:${NC}"
    enum_files=$(find src/Enum -name "*.php" 2>/dev/null | wc -l || echo 0)
    echo "  Enums créés: $enum_files"

    echo -e "\n${GREEN}Tests:${NC}"
    docker compose exec app composer test 2>&1 | grep "Tests:" | head -1

    echo -e "\n${GREEN}Qualité:${NC}"
    docker compose exec app composer phpstan 2>&1 | grep "found" | head -1 || echo "  ✅ Aucune erreur"
}

# Menu principal
case "${1:-help}" in
    baseline)
        baseline_metrics
        ;;

    find-hooks)
        find_property_hook_candidates
        ;;

    find-n1)
        find_n_plus_one
        ;;

    find-enums)
        find_enum_candidates
        ;;

    benchmark)
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 benchmark <service_class> <method_name>"
            exit 1
        fi
        benchmark_method "$2" "$3"
        ;;

    test-doctrine)
        test_doctrine_compatibility
        ;;

    convert-ids)
        read -p "⚠️  Ceci va modifier tous les fichiers Entity. Continuer? (y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            convert_ids_to_asymmetric
        fi
        ;;

    progress)
        progress_report
        ;;

    help|*)
        echo -e "${GREEN}Scripts d'aide Sprint PHP 8.5 Optimizations${NC}"
        echo ""
        echo "Usage: $0 [command]"
        echo ""
        echo "Commandes disponibles:"
        echo "  baseline         - Capturer les métriques de base (avant sprint)"
        echo "  find-hooks       - Trouver les candidats pour property hooks"
        echo "  find-n1          - Détecter les possibles N+1 queries"
        echo "  find-enums       - Trouver les constantes convertibles en enums"
        echo "  benchmark        - Benchmarker une méthode spécifique"
        echo "  test-doctrine    - Tester la compatibilité Doctrine"
        echo "  convert-ids      - ⚠️  Convertir automatiquement les IDs (BACKUP d'abord!)"
        echo "  progress         - Afficher le rapport de progression"
        echo "  help             - Afficher cette aide"
        echo ""
        echo "Exemples:"
        echo "  $0 baseline"
        echo "  $0 find-hooks"
        echo "  $0 benchmark App\\\\Service\\\\MetricsCalculationService calculateRevenue"
        ;;
esac
