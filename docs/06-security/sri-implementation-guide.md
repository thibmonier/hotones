# Subresource Integrity (SRI) - Guide d'Implémentation

**Date:** 31 décembre 2025
**Statut:** ✅ Stratégie documentée, implémentation sélective

## 📋 Vue d'ensemble

Le **Subresource Integrity (SRI)** est un mécanisme de sécurité qui permet aux navigateurs de vérifier que les fichiers chargés depuis des CDN externes n'ont pas été modifiés de manière malveillante.

## 🎯 Objectifs

- **Sécurité** : Protéger contre les attaques sur les CDN tiers (compromission, injection de code)
- **Intégrité** : Garantir que les ressources chargées sont exactement celles attendues
- **Conformité** : Renforcer la posture de sécurité globale (OWASP A08 - Software Integrity)

## 📊 Ressources CDN Externes Identifiées

### Ressources Critiques (SRI OBLIGATOIRE)

| Ressource | URL | Version | Pages | Criticité |
|-----------|-----|---------|-------|-----------|
| **Bootstrap CSS** | cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css | 5.3.0 | Toutes | 🔴 Critique |
| **Bootstrap JS** | cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js | 5.3.0 | Toutes | 🔴 Critique |
| **Chart.js** | cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js | 4.4.0 | Analytics, NPS | 🟠 Haute |
| **Boxicons** | cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css | 2.0.9 | Toutes | 🟡 Moyenne |

### Ressources Secondaires (SRI RECOMMANDÉ)

| Ressource | URL | Version | Pages | Criticité |
|-----------|-----|---------|-------|-----------|
| **Choices.js CSS** | cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css | latest | Planning, Projects | 🟢 Basse |
| **Choices.js JS** | cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js | latest | Planning, Projects | 🟢 Basse |
| **FullCalendar** | cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.10/index.global.min.js | 6.1.10 | Planning | 🟡 Moyenne |
| **FullCalendar Locale** | cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/fr.global.min.js | 6.1.10 | Planning | 🟢 Basse |

## ✅ Stratégie d'Implémentation

### Phase 1 : Ressources Critiques (À IMPLÉMENTER)

**Ressources à protéger immédiatement :**
1. Bootstrap CSS et JS (utilisé partout)
2. Chart.js (utilisé pour les dashboards analytics)

**Hashs SRI à utiliser :**

```html
<!-- Bootstrap 5.3.0 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-9ndCyUa+A7ec5R/3e+7l5UlvKhMhXdJwbcY7hqjkhR2k9HTfGwRp/gN6ykJ3qJ0Z"
      crossorigin="anonymous">

<!-- Bootstrap 5.3.0 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>

<!-- Chart.js 4.4.0 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha384-3B8lTlqKdSFn+LWw4d7O1e6qNEbPRR5i0ULlC5C5I5eqJvCdXP7FveWBIU1YaHUl"
        crossorigin="anonymous"></script>
```

**Note** : Ces hash sont des exemples. Les vrais hash doivent être générés pour chaque version spécifique.

### Phase 2 : Ressources Secondaires (OPTIONNEL)

Pour les ressources secondaires (Choices.js, FullCalendar), l'implémentation SRI peut être faite dans un second temps.

## 🛠️ Comment Générer des Hash SRI

### Méthode 1 : En ligne de commande (recommandé)

```bash
# Télécharger la ressource
curl -o resource.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js

# Générer le hash SHA-384
openssl dgst -sha384 -binary resource.js | openssl base64 -A

# Output : sha384-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

### Méthode 2 : Outil en ligne

- **SRI Hash Generator** : https://www.srihash.org/
- Coller l'URL de la ressource
- Copier le code généré avec l'attribut `integrity`

### Méthode 3 : Utiliser jsDelivr directement

jsDelivr fournit automatiquement les hash SRI :

```
https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?sri=true
```

Retourne le hash SRI dans les headers de la réponse.

## 📝 Processus de Mise à Jour

### Quand mettre à jour les hash SRI ?

1. **Mise à jour de version d'une bibliothèque** : Générer nouveau hash
2. **Changement de CDN** : Régénérer tous les hash
3. **Modification du fichier distant** : Le navigateur bloquera automatiquement (protection)

### Checklist de mise à jour

- [ ] Identifier la nouvelle version de la ressource
- [ ] Générer le nouveau hash SRI (méthode 1, 2 ou 3)
- [ ] Mettre à jour le template avec le nouveau hash
- [ ] Tester en local (le navigateur doit charger la ressource)
- [ ] Vérifier la console navigateur (pas d'erreur SRI)
- [ ] Déployer en production

## ⚠️ Considérations Importantes

### Avantages

- ✅ **Protection contre CDN compromis** : Si le CDN est piraté, le navigateur bloque la ressource modifiée
- ✅ **Conformité sécurité** : Améliore le score OWASP A08 (Software Integrity)
- ✅ **Pas d'impact performance** : Vérification côté client, pas de requête supplémentaire

### Inconvénients

- ⚠️ **Maintenance** : Nécessite de mettre à jour les hash à chaque changement de version
- ⚠️ **Versions dynamiques** : Ne fonctionne pas avec `@latest` (nécessite version fixe)
- ⚠️ **Blocage en cas d'erreur** : Si hash incorrect, ressource bloquée → site cassé

### Recommandations

1. **Toujours utiliser des versions fixes** : `@5.3.0` au lieu de `@latest`
2. **Tester en local après mise à jour** : Vérifier que les hash sont corrects
3. **Documentation** : Maintenir ce document à jour avec les hash actuels
4. **Monitoring** : Surveiller la console navigateur pour détecter les erreurs SRI

## 🔒 Niveau de Protection Actuel

| Aspect | Statut | Note |
|--------|--------|------|
| **CSP configuré** | ✅ | Limite les sources autorisées |
| **Versions fixes** | ⚠️ Partiel | Bootstrap fixe, autres à vérifier |
| **SRI implémenté** | 🔄 À faire | Phase 1 à implémenter |
| **Monitoring CDN** | ❌ | Pas de surveillance active |

## 🎯 Prochaines Étapes

### Immédiat (Phase 11bis.4)

1. ✅ Identifier toutes les ressources CDN (FAIT)
2. ✅ Documenter la stratégie SRI (FAIT)
3. ⏳ Générer les hash SRI pour Bootstrap et Chart.js
4. ⏳ Implémenter SRI sur les ressources critiques
5. ⏳ Tester en environnement de développement

### Court terme (Post 11bis)

6. Ajouter SRI aux ressources secondaires (Choices.js, FullCalendar)
7. Créer un script de génération automatique des hash
8. Documenter le processus dans le guide développeur

### Moyen terme

9. Évaluer la migration vers des assets auto-hébergés (pas de CDN externe)
10. Configurer un monitoring des ressources CDN

## 📚 Références

- **MDN Web Docs - SRI** : https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity
- **OWASP - Software Integrity** : https://owasp.org/Top10/A08_2021-Software_and_Data_Integrity_Failures/
- **SRI Hash Generator** : https://www.srihash.org/
- **jsDelivr SRI** : https://www.jsdelivr.com/features#sri

---

**Dernière mise à jour** : 31 décembre 2025
**Responsable** : Équipe sécurité
**Statut** : 🔄 Phase 1 à implémenter
