<?php

declare(strict_types=1);

namespace App\Application\Contributor\Query\ListActiveContributors;

/**
 * Sprint-018 Phase 3 strangler fig Contributor BC :
 *   - Phase 1 ✅ : DDD entity + interfaces
 *   - Phase 2 ✅ : ACL translators flat↔DDD + DoctrineDddContributorRepository
 *   - **Phase 3 (cette story)** : controller route lecture utilise UC DDD
 *   - Phase 4 (futur) : mutations via UC + migration write
 *
 * Query DTO immutable — pas de paramètre côté Phase 3 (lecture simple liste
 * active). Étend en Phase 4 avec filtres (companyId, search, etc.).
 *
 * @see ADR-0008 ACL pattern
 */
final readonly class ListActiveContributorsQuery
{
}
