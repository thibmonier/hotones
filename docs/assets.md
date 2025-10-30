# 🎨 Gestion des Assets

## Build local (recommandé)
```bash
yarn install
yarn dev
yarn build
yarn watch

# Scripts pratiques
./build-assets.sh dev
./build-assets.sh prod
./build-assets.sh watch
```

## Build dans Docker
```bash
./docker-build-assets.sh dev
./docker-build-assets.sh prod
./docker-build-assets.sh watch

# Manuellement
docker compose exec app apk add --no-cache nodejs npm yarn
docker compose exec app yarn install
docker compose exec app yarn dev
```

## Multi-stage build Docker (production)
```bash
docker compose build --no-cache app
```

## Configuration Webpack Encore
- Output : `public/assets/`
- Entrypoints : `app.scss`, `bootstrap.scss`, `icons.scss`
- Features : Support RTL, copie des fonts/images/libs
- Problème résolu : Exclusion de `pdfmake/build-vfs.js` (renommé en `.backup`)
