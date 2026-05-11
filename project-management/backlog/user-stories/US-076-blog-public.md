# US-076 — Blog public

> **BC**: MKT  |  **Source**: archived MKT.md (split 2026-05-11)

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

