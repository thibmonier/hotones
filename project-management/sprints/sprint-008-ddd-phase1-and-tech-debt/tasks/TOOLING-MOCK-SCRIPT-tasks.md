# TOOLING-MOCK-SCRIPT — Tasks

> Helper script `tools/count-mocks.pl` + doc CONTRIBUTING.md (1 pt). 2 tasks / ~3h.

## Story rappel

Action items A-1 + A-2 retro sprint-007. Script Perl réutilisable pour audit createMock vs expects sur les test files. Documente patterns dans CONTRIBUTING.md.

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-TMS-01 | [TOOL] | Créer `tools/count-mocks.pl` — input: liste de files. Output JSON: `{file: {mocks: [{var, class, line, has_expects, has_with}], summary: {convertible, requires_review}}}`. Inclus tests unit Perl (perl -c) | 2h | - | 🔲 |
| T-TMS-02 | [DOC] | Update CONTRIBUTING.md sections: 1) createMock vs createStub decision flowchart, 2) `--no-verify` legitimate use cases (push hook bypass for pre-existing failures uniquement), 3) Pre-flight check pre-pre-push | 1h | T-TMS-01 | 🔲 |

## Acceptance Criteria

- [ ] `tools/count-mocks.pl` exécutable (`chmod +x`)
- [ ] Output JSON valide testé sur 5 files Strate 1 (référence: tests/Unit/Security/Voter/InvoiceVoterTest.php)
- [ ] Doc CONTRIBUTING.md mise à jour 3 sections
- [ ] README.md du repo référence le script dans section Tooling

## Sortie

Branche: `feat/tooling-mock-script`. PR base main.
