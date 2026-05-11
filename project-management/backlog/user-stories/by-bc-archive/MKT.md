# Module: Content & Marketing

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.14 (FR-MKT-01..04). Generated 2026-05-04.

---

## US-075 — Pages publiques marketing

> INFERRED from `PublicController`, `HomeController`, `AboutController` + access_control PUBLIC paths.

- **Implements**: FR-MKT-01 — **Persona**: P-007 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** visiteur
**I want** consulter pages d'accueil, features, pricing, about, contact, légal
**So that** je découvre HotOnes avant inscription.

### Acceptance Criteria
```
When GET / /features /pricing /about /contact /legal
Then 200 sans authentification
```
```
Given visite tracée (analytics)
Then événement collecté (selon CookieConsent)
```

---

## US-076 — Blog public

> INFERRED from `BlogPost`, `BlogCategory`, `BlogTag`, `BlogController`, `BlogPostCrudController`, `BlogCategoryCrudController`, `BlogTagCrudController`.

- **Implements**: FR-MKT-02 — **Persona**: P-005, P-007 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** admin (publication) et visiteur (lecture)
**I want** publier et lire des articles avec catégories + tags
**So that** la SEO et le branding sont nourris.

### Acceptance Criteria
```
Given admin
When POST /admin/blog/posts {title, body, category, tags}
Then BlogPost créé statut "draft"
```
```
Given publication
When publish
Then visible sur /blog, /blog/category/{slug}, /blog/tag/{slug}
```
```
Given visite
Then SEO tags présents (title, og:image, schema.org)
```

### Technical Notes
- Markdown via league/commonmark
- Sanitisation HTML

---

## US-077 — ⛔ MERGED INTO US-012

> Décision atelier 2026-05-15: fusion avec US-012 en une story unique "Lead funnel" couvrant capture publique + pipeline backend.
>
> Voir `backlog/user-stories/CRM.md` US-012.

- **Statut**: MERGED → US-012
- **FR consolidée**: FR-CRM-03 + FR-MKT-03
- **Pts retransférés** sur US-012 (5 → 8 pts)

---

## US-078 — Sitemap public

> INFERRED from `presta/sitemap-bundle`.

- **Implements**: FR-MKT-04 — **Persona**: système, P-007 — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** moteur de recherche
**I want** consulter `/sitemap.xml` à jour
**So that** mon indexation est optimale.

### Acceptance Criteria
```
When GET /sitemap.xml
Then sitemap valide listant pages publiques + blog
```

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-075 | Pages marketing | FR-MKT-01 | 5 | Should |
| US-076 | Blog public | FR-MKT-02 | 5 | Should |
| ~~US-077~~ | MERGED → US-012 (CRM) | — | 0 | — |
| US-078 | Sitemap | FR-MKT-04 | 2 | Should |
| **Total** | | | **12** (-5 fusion) | |
