# SEC-VOTERS-001 — Tasks

> Voters entité pour `Project`, `Order`, `Invoice`, `Timesheet`.
> 5 pts / 5 tasks / ~8-10h.

| ID | Type | Description | Estimate | Status |
|----|------|-------------|---------:|--------|
| T-SV1-01 | [BE] | `ProjectVoter` (VIEW/EDIT/DELETE) avec check tenant + role + ownership | 2h | 🔲 |
| T-SV1-02 | [BE] | `OrderVoter` (VIEW/EDIT/SIGN) — règles métier sur `OrderStatus` lifecycle | 2h | 🔲 |
| T-SV1-03 | [BE] | `InvoiceVoter` (VIEW/EDIT/CANCEL) — verrou si `status = SENT` | 1.5h | 🔲 |
| T-SV1-04 | [BE] | `TimesheetVoter` (VIEW/EDIT/VALIDATE) — owner + manager validation | 1.5h | 🔲 |
| T-SV1-05 | [TEST] | Tests cross-tenant + cross-role pour les 4 voters | 2-3h | 🔲 |

## Acceptance Criteria

- [ ] Chaque voter implémente `Voter` Symfony avec `supports()` + `voteOnAttribute()`
- [ ] Vérification triplet : (a) tenant match (b) role grant (c) ownership/assignment
- [ ] `denyAccessUnlessGranted` ajouté sur controllers concernés sur les routes mutables
- [ ] Tests cross-tenant : Alice tenant A ne peut pas EDIT Project tenant B (même si même role)
- [ ] Tests cross-role : ROLE_USER ne peut pas DELETE même si owner
- [ ] PHPStan max OK
- [ ] Documentation `docs/06-security/voters-pattern.md` créée

## Pattern type voter

```php
final class ProjectVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) return false;

        // (a) tenant match
        if ($subject->getCompany()->getId() !== $user->getCompany()->getId()) {
            return false;
        }

        // (b) role grant + (c) ownership
        return match ($attribute) {
            self::VIEW => true, // any user of the tenant
            self::EDIT => in_array('ROLE_CHEF_PROJET', $user->getRoles()) || $subject->getProjectManager() === $user,
            self::DELETE => in_array('ROLE_ADMIN', $user->getRoles()),
        };
    }
}
```
