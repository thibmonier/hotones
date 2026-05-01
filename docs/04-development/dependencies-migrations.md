# Dépendances — notes de migration

> Pour chaque montée de version qui demande une adaptation côté projet (config, code, tests), tracer ici la procédure et le PR de référence.

## endroid/qr-code-bundle v5 → v6.1 (DEPS-001, sprint-004)

### Contexte

`composer.lock` indique `endroid/qr-code 6.1.3` + `endroid/qr-code-bundle 6.1.0` installés mais le projet hébergeait encore l'ancienne config v5 dans `config/packages/endroid_qr_code.yaml`. Avec la v6, le bundle ignore le bloc racine `default:` et tombe sur ses **valeurs par défaut hardcoded** (`Endroid\QrCode\Writer\PngWriter`, size 300, etc.) — l'application fonctionnait par chance mais ne respectait plus la config attendue.

### Breaking changes appliqués

| v5 (avant) | v6.1 (après) |
|---|---|
| `endroid_qr_code.default.writer: ...` | `endroid_qr_code.builders.default.writer: ...` (clé `builders` ajoutée) |
| `endroid_qr_code.default.size: 300` | `endroid_qr_code.builders.default.size: 300` |
| _absent_ | `endroid_qr_code.route_enabled` (top-level, défaut `true`) |
| _absent_ | `endroid_qr_code.route_prefix: /qr-code` (top-level) |
| `config/routes/endroid_qr_code.yaml` référence `@EndroidQrCodeBundle/Resources/config/routes.yaml` | Fichier **supprimé** : v6 utilise `RouteLoaderCompilerPass` qui auto-enregistre les routes via le DI |

### Migration appliquée

- `config/packages/endroid_qr_code.yaml` réécrit avec la map `builders:`
- `config/routes/endroid_qr_code.yaml` supprimé (auto-routing v6)
- Aucun changement dans `templates/profile/2fa_setup.html.twig` : la fonction Twig `qr_code_data_uri()` reste compatible (resservie par `QrCodeRuntime`)

### Vérification post-migration

Routes attendues côté `bin/console debug:router` :

```
endroid_qr_code  ANY  /qr-code/{builder}.{extension}
```

Smoke test 2FA : login → page `/profile/2fa-setup` doit afficher un QR scannable Google Authenticator.

### Si rollback nécessaire

```bash
composer require endroid/qr-code-bundle:^5.0
git checkout HEAD~1 -- config/packages/endroid_qr_code.yaml config/routes/endroid_qr_code.yaml
```

### Référence

- [endroid/qr-code-bundle 6.x README](https://github.com/endroid/qr-code-bundle)
- Sprint-004 DEPS-001
