# Local Environment Mac — Docker alternatives + PHPStan local

> **Sprint-019 sub-epic D** — actions héritées sprint-018 retro A-5 + A-6.
> Cible : éliminer le bypass `--no-verify` git commit récurrent (Docker
> Desktop crash + APCu manquant local).

## Contexte

Sprint-018 a montré 2 frictions répétées :
- **Docker Desktop instable Mac** (crash mid-sprint) → bloque pre-commit hook
  qui teste docker (PHPStan/CS-Fixer/PHPUnit en container)
- **PHPStan local échoue** : extension APCu manquante côté host PHP brew →
  validation déférée CI Docker uniquement

Workaround actuel : `git commit --no-verify` quand Docker down.
Risque : devient norme (sprint-018 retro ST-1 « stop »).

---

## ENV-DOCKER-ALTERNATIVE (sprint-019, 2 pts)

### Comparatif alternatives Docker Desktop sur Mac (Apple Silicon)

| Solution | Coût | Perf | Stabilité Mac | Compatibilité Compose | Friction migration |
|---|---|---|---|---|---|
| **Docker Desktop** | $0 perso / **$5/dev/mois** ≥ 250 employés | Bonne | ⚠️ Crash mid-session observé | 100 % | — |
| **OrbStack** | $0 perso / $8/dev/mois pro | **Excellente** (rosetta off, démarrage 2s) | ✅ Stable consensus communauté Mac | 100 % drop-in `docker compose` | Faible : install + alias |
| **Colima** | **$0 OSS** | Bonne | ✅ Stable (lima VM Apple Silicon) | 100 % drop-in | Faible : `colima start` au boot |
| **Podman Desktop** | $0 OSS | Bonne | ✅ Stable | ⚠️ `podman-compose` ≠ `docker compose` (gotchas) | Moyenne : aliases + tweaks |

### Recommandation

**Choix retenu : OrbStack** pour devs solo. **Fallback Colima** si contrainte
license entreprise / sensibilité OSS.

Justifications OrbStack vs concurrents :
- Drop-in compatibilité `docker compose` (zéro changement workflow)
- Démarrage instantané (~2s vs Docker Desktop ~30s)
- File system Mac-native (volumes mounts perfs ~10x)
- Communauté Mac dev unanime sur stabilité
- Cost = gratuit usage perso (notre cas startup pré-traction)
- Si traction commerciale + équipe > 5 devs : passer Pro $8/dev/mois (vs
  Docker Desktop $5/dev/mois si > 250 employés — mais Docker Desktop
  triggers fragility actuelle non résolue)

Risques OrbStack :
- License Pro requise si revenu entreprise > $1M (clause Free tier)
  → ré-évaluer quand traction commerciale
- Logiciel propriétaire (vs Colima OSS) → si l'éditeur disparaît, fallback
  Colima reste opérationnel (compose YAML compatible)

### Migration step-by-step (Mac)

```bash
# 1. Quitter Docker Desktop
osascript -e 'quit app "Docker"'

# 2. Install OrbStack
brew install --cask orbstack

# 3. Lancer OrbStack (configure auto Docker context)
open -a OrbStack

# 4. Vérifier docker context actif
docker context ls       # doit montrer "orbstack" actif
docker ps               # doit lister containers (peut être vide si non démarré)

# 5. Démarrer le projet
cd ~/Projects/hotones
make up                 # ou docker compose up -d

# 6. Sanity check : pre-commit hook fonctionne
git commit --allow-empty -m "test: orbstack pre-commit" --dry-run
# Doit afficher "[pre-commit] Using Docker (app container)"

# 7. (optionnel) Désinstaller Docker Desktop
brew uninstall --cask docker
```

### Fallback Colima si OrbStack pose problème

```bash
# Install
brew install colima docker docker-compose

# Démarrer (Apple Silicon, 4 CPUs, 8 GB RAM, 100 GB disk)
colima start --arch aarch64 --cpu 4 --memory 8 --disk 100

# Sanity check
docker context ls       # doit montrer "colima" actif
make up

# Auto-start au login
brew services start colima
```

### Trigger réversibilité

Revenir à Docker Desktop si :
- OrbStack/Colima provoque > 2 incidents data-loss / mois
- Compose YAML incompatible apparaît (regression test pipeline)
- Performance dégradée mesurable (`make test` > 2x temps Docker Desktop)

---

## ENV-APCU-LOCAL (sprint-019, 1 pt)

### Constat

PHPStan baseline du projet utilise APCu pour mémorisation cache analyse.
Sur Mac brew PHP 8.5 host, APCu n'est pas activé par défaut → erreur :

```
Internal error: APCu is not enabled. while analysing file
[...src/Command/SyncBoondManagerCommand.php]
Run PHPStan with -v option and post the stack trace [...]
```

→ PHPStan local non utilisable. Validation déférée CI Docker uniquement.

### Option A — Install APCu local (recommandé)

```bash
# Install via PECL (PHP 8.5)
pecl install apcu

# Activer dans config CLI
echo "extension=apcu.so" >> /opt/homebrew/etc/php/8.5/conf.d/20-apcu.ini
echo "apc.enable_cli=1" >> /opt/homebrew/etc/php/8.5/conf.d/20-apcu.ini

# Sanity check
php -r 'var_dump(extension_loaded("apcu"));'   # doit afficher bool(true)
php -r 'var_dump(apcu_store("k","v"));'        # doit afficher bool(true)

# PHPStan local fonctionne désormais
vendor/bin/phpstan analyse --no-progress --memory-limit=2G
```

### Option B — Documenter le bypass (si APCu install bloque)

Si PECL install échoue (incompat PHP 8.5 brew sur ARM, etc.), workflow accepté :

1. **Pre-commit hook** : Docker dispo → utilise Docker. Sinon fallback host PHP
   sans PHPStan (déjà géré par `.githooks/pre-commit`).
2. **Local commit** : `git commit --no-verify` quand Docker down + PHPStan
   skipé. Mention explicite dans commit message :
   ```
   Quality (commit --no-verify : Docker Desktop indisponible localement) :
   - PHPUnit Unit local : N tests OK
   - CS-Fixer : 0 fixable
   - PHPStan : validation déférée CI Docker (APCu manquant local)
   ```
3. **CI Docker** : valide PHPStan max systematic. Toute regression bloque
   merge PR via required check.

**Limite Option B** : repose sur discipline humaine (mention explicite). Pas
de fail-safe automatique.

### Recommandation

**Option A** — install APCu pecl si possible. **Option B** acceptée comme
fallback transitoire jusqu'à install effectif ou changement de stack PHP.

---

## Cleanup post-installation

Si OrbStack + APCu install effectif :

1. Sprint-018 retro ST-1 « stop `--no-verify` comme habitude » → respecté.
2. Pre-commit hook tourne PHPStan + CS-Fixer + PHPUnit Docker à chaque commit.
3. CI rattrape uniquement les cas exceptionnels (env CI Docker isolé du
   local, validation finale).

---

## Liens

- Sprint-018 retro L-1 (Docker Desktop crash mid-sprint) + ST-1 (stop --no-verify)
- Sprint-018 retro A-5 (alternative Docker) + A-6 (APCu)
- `.githooks/pre-commit` : detection Docker / fallback local PHP

---

**Date** : 2026-05-08
**Auteur** : Tech Lead (sprint-019 sub-epic D)
**Status** : ✅ Doc livré — install effectif côté dev par self-service
