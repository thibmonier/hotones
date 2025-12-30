<?php

declare(strict_types=1);

namespace App\AI\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Tool pour rechercher dans la documentation technique du projet.
 *
 * Permet aux agents IA d'accéder à la documentation pour répondre
 * aux questions techniques avec un contexte précis.
 *
 * Note: Version simplifiée sans RAG vectoriel pour Sprint 3.
 * Amélioration future: Migration vers vector store avec embeddings.
 */
#[AsTool('search_documentation', 'Recherche dans la documentation technique du projet (docs/, CLAUDE.md, etc.)')]
final readonly class DocumentationSearchTool
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array{
     *     query: string,
     *     total_results: int,
     *     results: array<int, array{file: string, preview: string, relevance: string}>
     * }
     */
    public function __invoke(string $query, int $limit = 3): array
    {
        $results = [];

        // Fichiers de documentation à rechercher
        $docsToSearch = [
            'CLAUDE.md',
            'README.md',
            'WARP.md',
            'docs/architecture.md',
            'docs/entities.md',
            'docs/features.md',
            'docs/profitability.md',
            'docs/analytics.md',
            'docs/time-planning.md',
            'docs/tests.md',
            'docs/good-practices.md',
        ];

        foreach ($docsToSearch as $docFile) {
            $filePath = $this->projectDir.'/'.$docFile;

            if (!file_exists($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
            if (false === $content) {
                continue;
            }

            // Recherche case-insensitive
            if (false !== stripos($content, $query)) {
                // Extraire un extrait autour du terme recherché
                $preview = $this->extractRelevantPreview($content, $query);

                $results[] = [
                    'file'      => $docFile,
                    'preview'   => $preview,
                    'relevance' => $this->calculateRelevance($content, $query),
                ];
            }
        }

        // Trier par pertinence (high > medium > low)
        usort($results, function ($a, $b) {
            $relevanceOrder = ['high' => 3, 'medium' => 2, 'low' => 1];

            return ($relevanceOrder[$b['relevance']] ?? 0) <=> ($relevanceOrder[$a['relevance']] ?? 0);
        });

        // Limiter les résultats
        $results = array_slice($results, 0, $limit);

        return [
            'query'         => $query,
            'total_results' => count($results),
            'results'       => $results,
        ];
    }

    /**
     * Extrait un aperçu pertinent autour du terme recherché.
     */
    private function extractRelevantPreview(string $content, string $query): string
    {
        $pos = stripos($content, $query);
        if (false === $pos) {
            return substr($content, 0, 200).'...';
        }

        // Extraire 150 caractères avant et après
        $start  = max(0, $pos - 150);
        $length = 300;

        $preview = substr($content, $start, $length);

        // Nettoyer (enlever les sauts de ligne multiples)
        $preview = preg_replace('/\n{2,}/', ' ', $preview) ?? $preview;
        $preview = trim($preview);

        return '...'.$preview.'...';
    }

    /**
     * Calcule la pertinence basée sur la fréquence et la position du terme.
     */
    private function calculateRelevance(string $content, string $query): string
    {
        $occurrences = substr_count(strtolower($content), strtolower($query));
        $firstPos    = stripos($content, $query);

        // High: 3+ occurrences OU présent dans les 500 premiers caractères
        if ($occurrences >= 3 || ($firstPos !== false && $firstPos < 500)) {
            return 'high';
        }

        // Medium: 2 occurrences
        if ($occurrences >= 2) {
            return 'medium';
        }

        return 'low';
    }
}
