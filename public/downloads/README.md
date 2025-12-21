# Guide des 15 KPIs Essentiels pour Agences Web

## À FAIRE : Créer le vrai PDF

Ce répertoire contient les ressources téléchargeables pour le lead magnet.

### Fichier à créer :
`guide-kpis-agences-web.pdf`

### Contenu recommandé du guide (25 pages) :

#### Page 1-2 : Introduction
- Pourquoi les KPIs sont essentiels pour les agences
- Comment utiliser ce guide
- Structure du document

#### Pages 3-20 : Les 15 KPIs détaillés

**KPIs Financiers (4 KPIs)**
1. Chiffre d'affaires récurrent (ARR/MRR)
   - Formule : Somme des contrats récurrents mensuels × 12
   - Interprétation : Stabilité financière
   - Objectif : > 60% du CA total

2. Marge brute par projet
   - Formule : (CA projet - Coûts directs) / CA projet × 100
   - Interprétation : Rentabilité par projet
   - Objectif : > 40%

3. Taux de marge nette
   - Formule : (Résultat net / CA) × 100
   - Interprétation : Rentabilité globale
   - Objectif : 15-25%

4. CAC (Coût d'Acquisition Client)
   - Formule : Coûts marketing + Coûts commerciaux / Nombre de nouveaux clients
   - Interprétation : Efficacité commerciale
   - Objectif : < 20% de la LTV

**KPIs Opérationnels (4 KPIs)**
5. Taux de staffing
   - Formule : (Heures staffées / Heures disponibles) × 100
   - Interprétation : Utilisation des ressources
   - Objectif : 80-90%

6. TACE (Taux d'Activité Congés Exclus)
   - Formule : (Jours productifs / Jours ouvrés hors congés) × 100
   - Interprétation : Productivité réelle
   - Objectif : > 85%

7. Taux d'utilisation
   - Formule : (Heures facturables / Heures totales) × 100
   - Interprétation : Efficacité productive
   - Objectif : > 70%

8. Temps moyen par projet
   - Formule : Somme des heures / Nombre de projets
   - Interprétation : Complexité moyenne
   - Objectif : Selon type de projets

**KPIs Commerciaux (4 KPIs)**
9. Taux de conversion devis
   - Formule : (Devis signés / Devis envoyés) × 100
   - Interprétation : Efficacité commerciale
   - Objectif : > 30%

10. Valeur moyenne des projets
    - Formule : CA total / Nombre de projets
    - Interprétation : Montée en gamme
    - Objectif : Croissance annuelle > 10%

11. Cycle de vente moyen
    - Formule : Temps moyen entre premier contact et signature
    - Interprétation : Efficacité du processus
    - Objectif : < 30 jours

12. Taux de rétention clients
    - Formule : (Clients N qui reviennent en N+1 / Clients N) × 100
    - Interprétation : Satisfaction et fidélité
    - Objectif : > 70%

**KPIs Satisfaction (3 KPIs)**
13. NPS (Net Promoter Score)
    - Formule : % Promoteurs - % Détracteurs
    - Interprétation : Satisfaction globale
    - Objectif : > 50

14. Satisfaction équipe
    - Formule : Enquête interne (échelle 1-10)
    - Interprétation : Bien-être des collaborateurs
    - Objectif : > 7.5/10

15. Turnover collaborateurs
    - Formule : (Départs / Effectif moyen) × 100
    - Interprétation : Rétention des talents
    - Objectif : < 15% annuel

#### Pages 21-23 : Tableaux de bord Excel
- Template Excel téléchargeable
- Instructions de personnalisation
- Exemples de visualisations

#### Pages 24-25 : Cas pratiques
- Agence A : Comment réduire le CAC de 40%
- Agence B : De 50% à 80% de taux de staffing
- Check-list PDF pour auditer vos KPIs

### Outils pour créer le PDF :

1. **Design professionnel** : Canva, Adobe InDesign, Figma
2. **Génération automatique** : LaTeX, Markdown to PDF
3. **Templates** : Beacon (getbeacon.com), Designrr

### Une fois créé, placer le fichier :
`public/downloads/guide-kpis-agences-web.pdf`

---

## Créer un PDF Placeholder pour les Tests

En attendant la création du vrai PDF, vous pouvez créer un placeholder simple :

### Option 1 : Avec LibreOffice/OpenOffice (Gratuit)

```bash
# 1. Créer un document texte simple avec :
cat > /tmp/guide-placeholder.txt << 'EOF'
Guide des 15 KPIs Essentiels pour Agences Web
==============================================

Version placeholder pour tests

Ce document est un placeholder temporaire.
Le vrai guide de 25 pages sera créé prochainement avec :

- 15 KPIs détaillés avec formules
- Tableaux de bord Excel
- Cas pratiques d'agences
- Check-list PDF pour auditer vos KPIs

Pour toute question : contact@hotones.io
EOF

# 2. Convertir en PDF avec pandoc (si installé)
pandoc /tmp/guide-placeholder.txt -o public/downloads/guide-kpis-agences-web.pdf
```

### Option 2 : Avec Python (si disponible)

```bash
python3 << 'EOF'
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.units import cm

pdf_path = "public/downloads/guide-kpis-agences-web.pdf"
c = canvas.Canvas(pdf_path, pagesize=A4)
width, height = A4

# Title
c.setFont("Helvetica-Bold", 24)
c.drawString(2*cm, height - 4*cm, "Guide des 15 KPIs")
c.drawString(2*cm, height - 5*cm, "pour Agences Web")

# Subtitle
c.setFont("Helvetica", 14)
c.drawString(2*cm, height - 7*cm, "Version placeholder pour tests")

# Content
c.setFont("Helvetica", 12)
y = height - 10*cm
lines = [
    "Ce document est un placeholder temporaire.",
    "",
    "Le vrai guide de 25 pages contiendra :",
    "  - 15 KPIs détaillés avec formules de calcul",
    "  - Tableaux de bord Excel téléchargeables",
    "  - Cas pratiques d'agences performantes",
    "  - Check-list PDF pour auditer vos KPIs",
]

for line in lines:
    c.drawString(2*cm, y, line)
    y -= 0.7*cm

c.save()
print(f"PDF créé : {pdf_path}")
EOF
```

### Option 3 : Télécharger un exemple

```bash
# Créer un PDF minimal avec wkhtmltopdf (si installé)
echo '<h1>Guide KPIs - Placeholder</h1><p>Version test</p>' | \
  wkhtmltopdf - public/downloads/guide-kpis-agences-web.pdf
```

### Option 4 : Copier un PDF existant

```bash
# Copier n'importe quel PDF comme placeholder
cp /chemin/vers/un/pdf.pdf public/downloads/guide-kpis-agences-web.pdf
```

### Vérifier le PDF

```bash
# Vérifier que le fichier existe et est lisible
ls -lh public/downloads/guide-kpis-agences-web.pdf

# Vérifier que c'est bien un PDF
file public/downloads/guide-kpis-agences-web.pdf
```
