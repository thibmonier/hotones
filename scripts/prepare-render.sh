#!/bin/bash
# Script de préparation pour le déploiement Render
# Génère les clés JWT et affiche le checklist

set -e

echo "========================================="
echo "Préparation au déploiement Render"
echo "========================================="
echo ""

# 1. Vérifier que nous sommes dans le bon répertoire
if [ ! -f "composer.json" ]; then
    echo "❌ Erreur: Exécutez ce script depuis la racine du projet HotOnes"
    exit 1
fi

echo "✅ Répertoire du projet détecté"
echo ""

# 2. Générer les clés JWT si elles n'existent pas
echo "🔐 Génération des clés JWT..."
if [ -f "config/jwt/private.pem" ] && [ -f "config/jwt/public.pem" ]; then
    echo "⚠️  Les clés JWT existent déjà"
    read -p "   Voulez-vous les régénérer ? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f config/jwt/*.pem
        php bin/console lexik:jwt:generate-keypair
        echo "✅ Nouvelles clés JWT générées"
    else
        echo "   Clés existantes conservées"
    fi
else
    php bin/console lexik:jwt:generate-keypair
    echo "✅ Clés JWT générées"
fi

echo ""

# 3. Récupérer la passphrase
if [ -f "config/jwt/private.pem" ]; then
    echo "🔑 Passphrase JWT à configurer dans Render:"
    echo "──────────────────────────────────────"
    JWT_PASSPHRASE=$(grep -A1 "ENCRYPTED" config/jwt/private.pem | tail -n1 | head -c 32 || echo "Voir config/jwt/private.pem")
    # Essayer de lire depuis .env si disponible
    if [ -f ".env" ]; then
        ENV_PASSPHRASE=$(grep "^JWT_PASSPHRASE=" .env | cut -d '=' -f2)
        if [ ! -z "$ENV_PASSPHRASE" ]; then
            echo "$ENV_PASSPHRASE"
        fi
    else
        echo "⚠️  Vérifiez JWT_PASSPHRASE dans votre fichier .env"
    fi
    echo "──────────────────────────────────────"
fi

echo ""
echo "========================================="
echo "📋 CHECKLIST DÉPLOIEMENT RENDER"
echo "========================================="
echo ""

echo "1️⃣  Base de données MySQL"
echo "   □ Créer une DB sur PlanetScale / Railway / DigitalOcean"
echo "   □ Récupérer la DATABASE_URL"
echo ""

echo "2️⃣  Repository Git"
echo "   □ Pousser le code sur GitHub/GitLab"
echo "   □ Vérifier que render.yaml est à la racine"
echo ""

echo "3️⃣  Render Dashboard (https://dashboard.render.com)"
echo "   □ New → Blueprint"
echo "   □ Connecter le repository"
echo "   □ Sélectionner la branche 'main'"
echo ""

echo "4️⃣  Variables d'environnement à configurer"
echo "   □ DATABASE_URL (depuis votre provider MySQL)"
echo "   □ JWT_PASSPHRASE (voir ci-dessus)"
echo "   □ MAILER_DSN (SMTP configuration)"
echo "   □ DEFAULT_URI (ex: https://votre-app.onrender.com)"
echo "   □ OPENAI_API_KEY (optionnel)"
echo "   □ ANTHROPIC_API_KEY (optionnel)"
echo ""

echo "5️⃣  Après le premier déploiement"
echo "   □ Ouvrir le Shell Render"
echo "   □ Créer le premier utilisateur:"
echo "      php bin/console app:user:create email@example.com password \"Prénom\" \"Nom\""
echo ""

echo "========================================="
echo ""
echo "📖 Documentation complète:"
echo "   docs/deployment-render.md"
echo "   DEPLOYMENT.md"
echo ""
echo "🎉 Vous êtes prêt à déployer !"
echo ""
