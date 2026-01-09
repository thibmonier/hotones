<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\BlogCategory;
use App\Entity\BlogPost;
use App\Entity\BlogTag;
use App\Entity\Company;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-blog',
    description: 'Seed the blog with sample articles about project management, KPIs, and best practices',
)]
class SeedBlogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('company-id', 'c', InputOption::VALUE_REQUIRED, 'Company ID to assign posts to');
        $this->addOption('author-id', 'a', InputOption::VALUE_REQUIRED, 'Author User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get company and author
        $companyId = $input->getOption('company-id');
        $authorId  = $input->getOption('author-id');

        if (!$companyId || !$authorId) {
            $io->error('Please specify --company-id and --author-id options');
            $io->note('Example: php bin/console app:seed-blog --company-id=1 --author-id=1');

            return Command::FAILURE;
        }

        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        $author  = $this->entityManager->getRepository(User::class)->find($authorId);

        if (!$company || !$author) {
            $io->error('Company or Author not found');

            return Command::FAILURE;
        }

        $io->title('Seeding Blog Data for HotOnes');

        // Create categories
        $io->section('Creating Categories...');
        $categories = $this->createCategories($io);

        // Create tags
        $io->section('Creating Tags...');
        $tags = $this->createTags($io);

        // Reload company and author (they might be detached after clear())
        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        $author  = $this->entityManager->getRepository(User::class)->find($authorId);

        // Create blog posts
        $io->section('Creating Blog Posts...');
        $this->createBlogPosts($io, $company, $author, $categories, $tags);

        $io->success('Blog seeded successfully with 5 articles!');
        $io->note('Visit /blog to see the published articles');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, BlogCategory>
     */
    private function createCategories(SymfonyStyle $io): array
    {
        $categoriesData = [
            'gestion-projet' => ['name' => 'Gestion de projet', 'color' => '#6366f1', 'description' => 'Méthodes et outils pour optimiser la gestion de vos projets'],
            'rentabilite'    => ['name' => 'Rentabilité', 'color' => '#10b981', 'description' => 'Maximiser la profitabilité de votre agence'],
            'kpis'           => ['name' => 'KPIs & Métriques', 'color' => '#f59e0b', 'description' => 'Suivre et améliorer vos indicateurs de performance'],
            'planning'       => ['name' => 'Planning & Resources', 'color' => '#8b5cf6', 'description' => 'Optimiser l\'allocation des ressources et le planning'],
            'best-practices' => ['name' => 'Best Practices', 'color' => '#ec4899', 'description' => 'Bonnes pratiques pour les agences web'],
        ];

        $categories = [];

        foreach ($categoriesData as $slug => $data) {
            $existing = $this->entityManager->getRepository(BlogCategory::class)->findOneBy(['name' => $data['name']]);

            if ($existing) {
                $io->text("  - Category '{$data['name']}' already exists (skipped)");
                $categories[$slug] = $existing;
                continue;
            }

            $category = new BlogCategory();
            $category->setName($data['name']);
            $category->setSlug($slug); // Set slug manually
            $category->setDescription($data['description']);
            $category->setColor($data['color']);
            $category->setActive(true);

            $this->entityManager->persist($category);
            $categories[$slug] = $category;

            $io->text("  ✓ Created category: {$data['name']}");
        }

        $this->entityManager->flush();
        $this->entityManager->clear(); // Clear to avoid issues

        return $categories;
    }

    /**
     * @return array<string, BlogTag>
     */
    private function createTags(SymfonyStyle $io): array
    {
        $tagsData = [
            'symfony', 'php', 'agile', 'scrum', 'kanban',
            'productivite', 'rentabilite', 'timesheet',
            'facturation', 'taux-journalier', 'marge',
            'planning', 'ressources', 'staffing',
        ];

        $tags = [];

        foreach ($tagsData as $tagName) {
            $existing = $this->entityManager->getRepository(BlogTag::class)->findOneBy(['name' => $tagName]);

            if ($existing) {
                $tags[$tagName] = $existing;
                continue;
            }

            $tag = new BlogTag();
            $tag->setName($tagName);

            $this->entityManager->persist($tag);
            $tags[$tagName] = $tag;
        }

        $this->entityManager->flush();
        $this->entityManager->clear(); // Clear to avoid issues
        $io->text('  ✓ Created/found '.count($tags).' tags');

        return $tags;
    }

    /**
     * @param array<string, BlogCategory> $categories
     * @param array<string, BlogTag>      $tags
     */
    private function createBlogPosts(SymfonyStyle $io, Company $company, User $author, array $categories, array $tags): void
    {
        $posts = [
            [
                'title'       => '5 KPIs essentiels pour piloter votre agence web',
                'category'    => 'kpis',
                'tags'        => ['rentabilite', 'marge', 'taux-journalier'],
                'excerpt'     => 'Découvrez les 5 indicateurs de performance clés que toute agence web devrait suivre pour optimiser sa rentabilité et prendre des décisions éclairées.',
                'content'     => $this->getArticleContent1(),
                'publishedAt' => new DateTimeImmutable('-10 days'),
                'image'       => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200',
            ],
            [
                'title'       => 'Comment optimiser le taux de charge de vos équipes',
                'category'    => 'planning',
                'tags'        => ['planning', 'ressources', 'staffing', 'productivite'],
                'excerpt'     => 'Le taux de charge est un indicateur critique pour la rentabilité. Voici nos conseils pour maintenir un équilibre optimal entre disponibilité et saturation.',
                'content'     => $this->getArticleContent2(),
                'publishedAt' => new DateTimeImmutable('-7 days'),
                'image'       => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200',
            ],
            [
                'title'       => 'Forfait vs Régie : quelle facturation choisir ?',
                'category'    => 'rentabilite',
                'tags'        => ['facturation', 'rentabilite', 'best-practices'],
                'excerpt'     => 'Comprendre les avantages et inconvénients de chaque mode de facturation pour maximiser votre marge tout en satisfaisant vos clients.',
                'content'     => $this->getArticleContent3(),
                'publishedAt' => new DateTimeImmutable('-5 days'),
                'image'       => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200',
            ],
            [
                'title'       => 'Méthodologie Agile : implémenter Scrum dans votre agence',
                'category'    => 'gestion-projet',
                'tags'        => ['agile', 'scrum', 'productivite', 'best-practices'],
                'excerpt'     => 'Guide pratique pour adopter la méthodologie Scrum et améliorer la collaboration, la transparence et la livraison de valeur dans vos projets.',
                'content'     => $this->getArticleContent4(),
                'publishedAt' => new DateTimeImmutable('-3 days'),
                'image'       => 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?w=1200',
            ],
            [
                'title'       => 'Timesheet : 7 astuces pour un suivi du temps efficace',
                'category'    => 'best-practices',
                'tags'        => ['timesheet', 'productivite', 'rentabilite'],
                'excerpt'     => 'Le suivi du temps est crucial pour la rentabilité. Découvrez 7 bonnes pratiques pour encourager vos équipes à saisir leurs heures régulièrement et précisément.',
                'content'     => $this->getArticleContent5(),
                'publishedAt' => new DateTimeImmutable('-1 day'),
                'image'       => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200',
            ],
        ];

        foreach ($posts as $postData) {
            // Check if post already exists
            $existing = $this->entityManager->getRepository(BlogPost::class)
                ->createQueryBuilder('p')
                ->where('p.title = :title')
                ->andWhere('p.company = :company')
                ->setParameter('title', $postData['title'])
                ->setParameter('company', $company)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing) {
                $io->text("  - Post '{$postData['title']}' already exists (skipped)");
                continue;
            }

            $post = new BlogPost();
            $post->setCompany($company);
            $post->setAuthor($author);
            $post->setTitle($postData['title']);
            // Generate slug manually (Gedmo will handle it too, but we set it to avoid empty slug issues)
            $post->setSlug($this->generateSlug($postData['title']));
            $post->setExcerpt($postData['excerpt']);
            $post->setContent($postData['content']);
            $post->setFeaturedImage($postData['image']);
            $post->setStatus(BlogPost::STATUS_PUBLISHED);
            $post->setPublishedAt($postData['publishedAt']);

            // Set category (reload from DB to avoid detached entity issues)
            $categoryEntity = $this->entityManager->getRepository(BlogCategory::class)
                ->findOneBy(['name' => $categories[$postData['category']]->getName()]);
            if ($categoryEntity) {
                $post->setCategory($categoryEntity);
            }

            // Add tags (reload from DB to avoid detached entity issues)
            foreach ($postData['tags'] as $tagName) {
                $tagEntity = $this->entityManager->getRepository(BlogTag::class)
                    ->findOneBy(['name' => $tagName]);
                if ($tagEntity) {
                    $post->addTag($tagEntity);
                }
            }

            $this->entityManager->persist($post);
            $io->text("  ✓ Created post: {$postData['title']}");
        }

        $this->entityManager->flush();
    }

    /**
     * Generate a URL-friendly slug from a string.
     */
    private function generateSlug(string $text): string
    {
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim
        $text = trim($text, '-');

        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    private function getArticleContent1(): string
    {
        return <<<'HTML'
<h2>Introduction</h2>
<p>Dans le monde compétitif des agences web, piloter son activité avec des données objectives est devenu indispensable. Les KPIs (Key Performance Indicators) vous permettent de mesurer la santé de votre agence et d'identifier rapidement les axes d'amélioration.</p>

<h2>1. Le Taux de Marge Brut</h2>
<p>Le taux de marge brut est sans doute l'indicateur le plus important pour évaluer la rentabilité de vos projets. Il se calcule ainsi :</p>
<pre><code>Taux de Marge = (Chiffre d'affaires - Coûts directs) / Chiffre d'affaires × 100</code></pre>
<p>Un taux de marge sain pour une agence web se situe généralement entre <strong>40% et 60%</strong>. En dessous de 40%, votre rentabilité est en danger.</p>

<h3>Comment l'améliorer ?</h3>
<ul>
<li>Augmentez vos tarifs progressivement</li>
<li>Optimisez le temps passé sur chaque projet</li>
<li>Réduisez les coûts indirects (outils, sous-traitance)</li>
<li>Privilégiez les projets à forte valeur ajoutée</li>
</ul>

<h2>2. Le Taux de Charge Effectif (TACE)</h2>
<p>Le TACE mesure le pourcentage de temps facturable de vos collaborateurs sur leur temps de travail total.</p>
<pre><code>TACE = Heures facturables / Heures travaillées × 100</code></pre>
<p>Un TACE optimal se situe entre <strong>75% et 85%</strong>. Au-delà, vous risquez le burn-out de vos équipes. En deçà, vous perdez en rentabilité.</p>

<h2>3. Le Taux de Conversion Commercial</h2>
<p>Combien de devis envoyés se transforment en projets signés ? Ce KPI révèle l'efficacité de votre process commercial.</p>
<pre><code>Taux de Conversion = Devis signés / Devis envoyés × 100</code></pre>
<p>Un bon taux de conversion se situe autour de <strong>30% à 40%</strong> pour une agence web établie.</p>

<h2>4. Le Délai Moyen de Paiement (DSO)</h2>
<p>Le Days Sales Outstanding mesure le délai moyen entre l'émission d'une facture et son encaissement.</p>
<pre><code>DSO = (Créances clients / CA annuel) × 365 jours</code></pre>
<p>Un DSO inférieur à <strong>45 jours</strong> est excellent pour la trésorerie.</p>

<h2>5. Le Chiffre d'Affaires par Collaborateur</h2>
<p>Cet indicateur mesure la productivité globale de votre agence :</p>
<pre><code>CA/Collaborateur = CA annuel / Nombre de collaborateurs</code></pre>
<p>Pour une agence web, visez un CA par collaborateur entre <strong>80K€ et 120K€</strong> selon votre positionnement.</p>

<h2>Conclusion</h2>
<p>Ces 5 KPIs constituent le socle d'un pilotage efficace. HotOnes vous permet de suivre automatiquement ces indicateurs grâce à son tableau de bord analytique. Commencez par mesurer, puis optimisez progressivement chaque métrique pour améliorer durablement la performance de votre agence.</p>
HTML;
    }

    private function getArticleContent2(): string
    {
        return <<<'HTML'
<h2>Qu'est-ce que le taux de charge ?</h2>
<p>Le taux de charge (ou taux d'occupation) représente le ratio entre le temps facturable d'un collaborateur et son temps de travail théorique. C'est un indicateur crucial pour équilibrer rentabilité et bien-être des équipes.</p>

<h2>Les différents types de taux de charge</h2>

<h3>Taux de charge prévisionnel</h3>
<p>Calculé sur la base du planning prévisionnel, il permet d'anticiper les périodes de sous-charge ou de surcharge.</p>

<h3>Taux de charge effectif (TACE)</h3>
<p>Basé sur les timesheets réellement saisis, il reflète la réalité du terrain. C'est le KPI à suivre en priorité.</p>

<h2>Les zones de taux de charge</h2>

<div style="background: var(--dark-card); padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
<ul>
<li><strong>&lt; 60%</strong> : 🔴 Sous-charge critique - Risque financier</li>
<li><strong>60-75%</strong> : 🟡 Sous-charge acceptable - Marge d'amélioration</li>
<li><strong>75-85%</strong> : 🟢 Zone optimale - Équilibre rentabilité/bien-être</li>
<li><strong>85-95%</strong> : 🟡 Surcharge modérée - Attention au burn-out</li>
<li><strong>&gt; 95%</strong> : 🔴 Surcharge critique - Action immédiate requise</li>
</ul>
</div>

<h2>5 leviers pour optimiser le taux de charge</h2>

<h3>1. Anticiper avec le planning ressources</h3>
<p>Utilisez un outil de planning pour visualiser la charge de chaque collaborateur sur les 4 à 8 semaines à venir. HotOnes offre une vue Gantt qui facilite l'identification des trous de charge.</p>

<h3>2. Constituer un backlog de petites missions</h3>
<p>Gardez toujours en réserve des tâches courtes (refonte de pages, optimisations SEO, maintenance) pour combler les périodes creuses.</p>

<h3>3. Mettre en place du staffing dynamique</h3>
<p>Privilégiez la polyvalence de vos équipes pour pouvoir réaffecter rapidement les ressources d'un projet à un autre selon les besoins.</p>

<h3>4. Négocier des périodes de creux avec les clients</h3>
<p>Pour les projets au forfait, étalez la charge sur plusieurs mois plutôt que de concentrer tout le développement sur 2-3 semaines intenses.</p>

<h3>5. Suivre le TACE hebdomadairement</h3>
<p>Organisez un point planning hebdomadaire avec vos chefs de projet pour ajuster en temps réel l'allocation des ressources.</p>

<h2>Gérer les périodes de sous-charge</h2>
<p>Plutôt que de paniquer face à une période creuse, profitez-en pour :</p>
<ul>
<li>Former vos équipes sur de nouvelles technologies</li>
<li>Développer des projets internes (refonte du site, outils internes)</li>
<li>Investir dans du contenu marketing (blog, études de cas)</li>
<li>Prendre de l'avance sur la prospection commerciale</li>
</ul>

<h2>Gérer les périodes de surcharge</h2>
<p>Si le taux de charge dépasse 85% sur plusieurs semaines :</p>
<ul>
<li>Refusez de nouveaux projets ou décalez leur démarrage</li>
<li>Recrutez un freelance pour absorber le surplus</li>
<li>Renégociez les délais avec vos clients</li>
<li>Identifiez les tâches non essentielles à reporter</li>
</ul>

<h2>Conclusion</h2>
<p>Un taux de charge optimal est la clé d'une agence rentable ET où il fait bon travailler. L'objectif n'est pas de maximiser à tout prix le taux de charge, mais de trouver le bon équilibre pour votre équipe. HotOnes vous aide à visualiser et optimiser ce KPI crucial au quotidien.</p>
HTML;
    }

    private function getArticleContent3(): string
    {
        return <<<'HTML'
<h2>Les deux modèles de facturation</h2>

<h3>Le forfait (ou prix fixe)</h3>
<p>Vous vendez un projet avec un périmètre et un prix définis à l'avance. Le client sait exactement ce qu'il va payer, indépendamment du temps réellement passé.</p>

<h3>La régie (ou time & material)</h3>
<p>Vous facturez le temps passé au taux journalier ou horaire. Le montant final dépend du nombre de jours réellement travaillés.</p>

<h2>Avantages et inconvénients</h2>

<h3>Forfait</h3>
<div style="background: var(--dark-card); padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
<p><strong>✅ Avantages :</strong></p>
<ul>
<li>Marge prévisible si bien estimé</li>
<li>Récompense l'efficacité et l'expertise</li>
<li>Rassure le client (budget fixe)</li>
<li>Valorise mieux les projets innovants</li>
</ul>

<p><strong>❌ Inconvénients :</strong></p>
<ul>
<li>Risque de dépassement et perte de marge</li>
<li>Scope creep difficile à gérer</li>
<li>Nécessite une excellente estimation</li>
<li>Conflits potentiels sur le périmètre</li>
</ul>
</div>

<h3>Régie</h3>
<div style="background: var(--dark-card); padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
<p><strong>✅ Avantages :</strong></p>
<ul>
<li>Zéro risque financier (temps passé = temps facturé)</li>
<li>Flexibilité maximale sur le scope</li>
<li>Pas besoin d'estimation précise</li>
<li>Gestion simplifiée des imprévus</li>
</ul>

<p><strong>❌ Inconvénients :</strong></p>
<ul>
<li>Marge plus faible (pas de prime à l'efficacité)</li>
<li>Budget final incertain pour le client</li>
<li>Nécessite une relation de confiance forte</li>
<li>L'inefficacité est facturée au client</li>
</ul>
</div>

<h2>Quel modèle choisir selon le contexte ?</h2>

<h3>Choisissez le FORFAIT si :</h3>
<ul>
<li>Le périmètre est bien défini et stable</li>
<li>Vous avez déjà réalisé des projets similaires</li>
<li>Le client veut un budget garanti</li>
<li>Vous cherchez à valoriser votre expertise</li>
<li>Vous visez une forte marge</li>
</ul>

<h3>Choisissez la RÉGIE si :</h3>
<ul>
<li>Le périmètre est flou ou évolutif</li>
<li>Le projet est exploratoire (R&D, POC)</li>
<li>Le client veut garder le contrôle total</li>
<li>Vous manquez d'expertise sur le sujet</li>
<li>Vous privilégiez la sécurité financière</li>
</ul>

<h2>Le modèle hybride : la régie plafonnée</h2>
<p>Pour combiner les avantages des deux approches, proposez une <strong>régie avec un plafond maximum</strong> :</p>
<ul>
<li>Facturation au temps passé (comme la régie)</li>
<li>Avec un montant maximum garanti (comme le forfait)</li>
<li>Partage du risque entre agence et client</li>
</ul>

<h2>Comment bien estimer un forfait ?</h2>
<ol>
<li><strong>Décomposez</strong> le projet en tâches élémentaires</li>
<li><strong>Estimez</strong> chaque tâche en heures (pessimiste + optimiste)</li>
<li><strong>Additionnez</strong> et appliquez un coefficient de sécurité (+20%)</li>
<li><strong>Ajoutez</strong> la gestion de projet (15% du temps dev)</li>
<li><strong>Multipliez</strong> par votre TJM pour obtenir le prix</li>
<li><strong>Arrondissez</strong> à un chiffre psychologique</li>
</ol>

<h2>Maximiser votre marge en forfait</h2>
<ul>
<li><strong>Utilisez des composants réutilisables</strong> pour accélérer le dev</li>
<li><strong>Facturez la valeur, pas le temps</strong> (augmentez vos tarifs)</li>
<li><strong>Cadrez fermement le périmètre</strong> dans le devis</li>
<li><strong>Prévoyez des avenants payants</strong> pour toute demande hors scope</li>
<li><strong>Suivez le temps passé</strong> pour apprendre et affiner vos estimations</li>
</ul>

<h2>Suivi de la rentabilité avec HotOnes</h2>
<p>Que vous travailliez en forfait ou en régie, HotOnes vous permet de :</p>
<ul>
<li>Comparer le budget vendu vs le temps passé réel</li>
<li>Calculer automatiquement la marge de chaque projet</li>
<li>Identifier les projets déficitaires avant qu'il ne soit trop tard</li>
<li>Analyser vos performances d'estimation sur le long terme</li>
</ul>

<h2>Conclusion</h2>
<p>Il n'y a pas de modèle universellement meilleur. Le forfait maximise la marge mais comporte des risques. La régie sécurise le CA mais limite le potentiel de profit. L'idéal est d'adapter votre approche projet par projet, en fonction de votre niveau de confiance dans l'estimation et de la relation avec le client.</p>
HTML;
    }

    private function getArticleContent4(): string
    {
        return <<<'HTML'
<h2>Qu'est-ce que Scrum ?</h2>
<p>Scrum est un framework Agile qui organise le travail en cycles courts appelés <strong>Sprints</strong> (généralement 2 semaines). L'objectif : livrer régulièrement de la valeur au client tout en s'adaptant aux changements.</p>

<h2>Les 3 piliers de Scrum</h2>
<ul>
<li><strong>Transparence</strong> : Tout le monde voit où en est le projet</li>
<li><strong>Inspection</strong> : On vérifie régulièrement l'avancement</li>
<li><strong>Adaptation</strong> : On ajuste le plan en fonction des apprentissages</li>
</ul>

<h2>Les rôles Scrum</h2>

<h3>Product Owner (PO)</h3>
<p>Le PO représente le client et définit les priorités. Il maintient le <strong>Product Backlog</strong> (liste priorisée des fonctionnalités à développer).</p>

<h3>Scrum Master</h3>
<p>Le garant de la méthode. Il facilite les cérémonies, résout les blocages et protège l'équipe des perturbations externes.</p>

<h3>Dev Team</h3>
<p>L'équipe de développement (développeurs, designers, testeurs). Auto-organisée et pluridisciplinaire.</p>

<h2>Les cérémonies Scrum</h2>

<h3>1. Sprint Planning (2h pour un sprint de 2 semaines)</h3>
<p>L'équipe sélectionne les user stories du backlog qu'elle s'engage à livrer pendant le sprint. On définit le <strong>Sprint Goal</strong> (objectif du sprint).</p>

<h3>2. Daily Standup (15 min, chaque jour)</h3>
<p>Debout, chacun répond à 3 questions :</p>
<ul>
<li>Qu'ai-je fait hier ?</li>
<li>Que vais-je faire aujourd'hui ?</li>
<li>Ai-je des blocages ?</li>
</ul>

<h3>3. Sprint Review (1h)</h3>
<p>Démonstration des fonctionnalités développées au Product Owner et aux stakeholders. Recueil de feedback.</p>

<h3>4. Sprint Retrospective (1h)</h3>
<p>L'équipe réfléchit sur le sprint passé :</p>
<ul>
<li>Qu'est-ce qui a bien fonctionné ?</li>
<li>Qu'est-ce qui peut être amélioré ?</li>
<li>Quelles actions concrètes pour le prochain sprint ?</li>
</ul>

<h2>Les artefacts Scrum</h2>

<h3>Product Backlog</h3>
<p>Liste priorisée de toutes les fonctionnalités à développer, maintenue par le PO.</p>

<h3>Sprint Backlog</h3>
<p>Sous-ensemble du Product Backlog sélectionné pour le sprint en cours.</p>

<h3>Increment</h3>
<p>Le produit fonctionnel livré à la fin du sprint (potentiellement déployable).</p>

<h2>Implémenter Scrum dans votre agence</h2>

<h3>Étape 1 : Former l'équipe</h3>
<p>Organisez une formation Scrum de 2 jours avec un coach certifié. Tout le monde doit comprendre les principes et les bénéfices.</p>

<h3>Étape 2 : Démarrer avec un projet pilote</h3>
<p>Choisissez un projet en cours, idéalement avec un client ouvert et une équipe volontaire. Ne transformez pas tous les projets d'un coup.</p>

<h3>Étape 3 : Mettre en place les outils</h3>
<ul>
<li>Tableau Kanban (physique ou Jira/Trello/HotOnes)</li>
<li>Burndown chart pour suivre l'avancement du sprint</li>
<li>Espace de réunion dédié aux cérémonies</li>
</ul>

<h3>Étape 4 : Définir la durée des sprints</h3>
<p>Pour une agence web, <strong>2 semaines</strong> est généralement optimal. Assez court pour rester agile, assez long pour livrer de vraies fonctionnalités.</p>

<h3>Étape 5 : Écrire des User Stories</h3>
<p>Format : <em>"En tant que [rôle], je veux [action] afin de [bénéfice]"</em></p>
<p>Exemple : "En tant qu'administrateur, je veux pouvoir exporter les données en CSV afin d'analyser les statistiques dans Excel."</p>

<h3>Étape 6 : Estimer avec le Planning Poker</h3>
<p>L'équipe estime chaque user story en points de complexité (suite de Fibonacci : 1, 2, 3, 5, 8, 13...). Pas en heures !</p>

<h2>Les pièges à éviter</h2>

<h3>❌ Faire du "Scrum But"</h3>
<p>"On fait du Scrum, mais sans le Daily", "On fait du Scrum, mais avec des sprints de 1 mois"... Respectez le framework ou ne l'appelez pas Scrum.</p>

<h3>❌ Négliger la rétrospective</h3>
<p>C'est LA cérémonie d'amélioration continue. Ne la sautez jamais.</p>

<h3>❌ Ajouter des tâches en cours de sprint</h3>
<p>Le sprint backlog est figé. Les nouvelles demandes vont dans le prochain sprint.</p>

<h3>❌ Ne pas protéger l'équipe</h3>
<p>Le Scrum Master doit empêcher les interruptions et les sollicitations directes du client vers les développeurs.</p>

<h2>Scrum dans HotOnes</h2>
<p>HotOnes facilite l'adoption de Scrum grâce à :</p>
<ul>
<li>Tableau Kanban avec workflow personnalisable</li>
<li>Suivi du temps par user story</li>
<li>Vue planning pour visualiser les sprints</li>
<li>Burndown chart automatique</li>
<li>Vélocité d'équipe calculée automatiquement</li>
</ul>

<h2>Conclusion</h2>
<p>Scrum n'est pas une solution miracle, mais un framework éprouvé pour améliorer la collaboration et la prévisibilité. Commencez petit, expérimentez, adaptez. L'essentiel est de respecter les principes Agiles : transparence, adaptation et livraison continue de valeur.</p>
HTML;
    }

    private function getArticleContent5(): string
    {
        return <<<'HTML'
<h2>Pourquoi le timesheet est crucial</h2>
<p>Le suivi du temps n'est pas qu'une contrainte administrative. C'est la base de :</p>
<ul>
<li>La <strong>facturation clients</strong> (en régie)</li>
<li>Le <strong>calcul de la rentabilité</strong> (en forfait)</li>
<li>L'<strong>estimation de futurs projets</strong></li>
<li>L'<strong>optimisation de vos process</strong></li>
<li>Le <strong>pilotage de votre agence</strong></li>
</ul>
<p>Sans timesheet fiable, vous pilotez à l'aveugle.</p>

<h2>Le problème universel : la saisie irrégulière</h2>
<p>Dans 80% des agences, les développeurs saisissent leurs temps :</p>
<ul>
<li>🔴 Le vendredi en fin de journée (reconstitution approximative)</li>
<li>🔴 Le lundi matin (mémoire encore plus floue)</li>
<li>🔴 À la fin du mois (sous pression de la compta)</li>
</ul>
<p>Résultat : données peu fiables, frustration généralisée, et décisions basées sur des approximations.</p>

<h2>Les 7 astuces pour un suivi du temps efficace</h2>

<h3>1. Rendre la saisie quotidienne (et non hebdomadaire)</h3>
<p>Plus on attend, plus on oublie. Encouragez vos équipes à saisir chaque soir avant de partir, ou le lendemain matin à 9h.</p>
<p><strong>Astuce</strong> : Intégrez la saisie dans un rituel quotidien, par exemple juste après le daily standup.</p>

<h3>2. Simplifier au maximum l'interface</h3>
<p>L'outil de timesheet doit être :</p>
<ul>
<li>✅ Accessible en 1 clic (pas de menu à 3 niveaux)</li>
<li>✅ Rapide (saisie en moins de 2 minutes)</li>
<li>✅ Intuitif (pas besoin de formation)</li>
<li>✅ Mobile-friendly (pour les jours de télétravail)</li>
</ul>
<p>HotOnes propose une interface de saisie hebdomadaire en grille : 1 ligne = 1 projet, 7 colonnes = 7 jours. Vous remplissez toute la semaine en quelques clics.</p>

<h3>3. Activer les notifications automatiques</h3>
<p>Mettez en place des rappels :</p>
<ul>
<li>📧 Email à 17h si rien n'est saisi aujourd'hui</li>
<li>📱 Notification Slack à 18h30 pour les retardataires</li>
<li>📅 Récapitulatif le vendredi midi des jours manquants</li>
</ul>

<h3>4. Donner du sens à la démarche</h3>
<p>Expliquez à vos équipes <strong>pourquoi</strong> vous avez besoin de ces données :</p>
<ul>
<li>"Pour facturer correctement nos clients en régie"</li>
<li>"Pour savoir si on est rentable sur ce projet"</li>
<li>"Pour mieux estimer les prochains projets"</li>
<li>"Pour ajuster vos primes de fin d'année"</li>
</ul>
<p>Les gens saisissent mieux quand ils comprennent l'impact.</p>

<h3>5. Montrer les données en transparence</h3>
<p>Organisez un point mensuel où vous partagez :</p>
<ul>
<li>Le taux de saisie de l'équipe (objectif : 95%)</li>
<li>Les projets rentables vs déficitaires</li>
<li>Le TACE moyen de l'agence</li>
</ul>
<p>La transparence crée de la responsabilisation.</p>

<h3>6. Imputer au minimum par demi-journée</h3>
<p>Ne demandez pas une précision à la minute près (15:34 - 16:47). Autorisez les imputations par :</p>
<ul>
<li>🕐 <strong>0.5 jour</strong> = demi-journée (4h)</li>
<li>🕐 <strong>1 jour</strong> = journée complète (8h)</li>
<li>🕐 <strong>0.25 jour</strong> = pour les très courtes tâches (2h)</li>
</ul>
<p>C'est plus rapide à saisir et amplement suffisant pour piloter.</p>

<h3>7. Gamifier la saisie</h3>
<p>Créez une émulation positive :</p>
<ul>
<li>🏆 Classement mensuel du meilleur saisi (avec un lot symbolique)</li>
<li>📊 Dashboard en temps réel affiché dans l'open space</li>
<li>✅ Badge "Timesheet Hero" pour 100% de saisie sur le mois</li>
</ul>

<h2>Les erreurs à éviter</h2>

<h3>❌ Utiliser Excel</h3>
<p>Excel est un cauchemar pour le timesheet : pas de contrôle, pas de workflow, difficile à consolider, source d'erreurs. Investissez dans un vrai outil.</p>

<h3>❌ Demander trop de détails</h3>
<p>Ne multipliez pas les champs obligatoires : "Tâche", "Sous-tâche", "Type d'activité", "Livrable", "Client", "Tag"... Vous découragez les gens.</p>

<h3>❌ Sanctionner les retardataires</h3>
<p>La pression négative ne fonctionne pas sur le long terme. Privilégiez la pédagogie et l'encouragement positif.</p>

<h3>❌ Ne pas donner l'exemple</h3>
<p>Si les managers ne saisissent pas leurs propres temps, pourquoi l'équipe le ferait ?</p>

<h2>Le timer : l'arme secrète</h2>
<p>HotOnes intègre un timer qui vous permet de :</p>
<ul>
<li>▶️ Démarrer un chronomètre sur une tâche</li>
<li>⏸️ Le mettre en pause quand on vous interrompt</li>
<li>✅ Convertir automatiquement en imputation à la fin</li>
</ul>
<p>Vous n'avez plus à vous souvenir de rien : le timer fait le travail pour vous.</p>

<h2>Les bénéfices d'un bon suivi du temps</h2>
<p>Avec un timesheet bien tenu, vous pouvez :</p>
<ul>
<li>📈 <strong>Améliorer vos estimations</strong> de 30% en moyenne</li>
<li>💰 <strong>Identifier les projets non rentables</strong> avant qu'il ne soit trop tard</li>
<li>📊 <strong>Facturer 100% du temps</strong> en régie (sans oubli)</li>
<li>🎯 <strong>Optimiser l'allocation</strong> des ressources</li>
<li>💡 <strong>Détecter les process inefficaces</strong> (où perd-on du temps ?)</li>
</ul>

<h2>Conclusion</h2>
<p>Le timesheet n'est pas l'ennemi. C'est un outil de pilotage essentiel pour toute agence qui veut être rentable. La clé du succès : rendre la saisie simple, quotidienne et valorisée. Avec les bons outils et les bonnes pratiques, vous passerez d'un taux de saisie de 60% à 95%+ en quelques semaines.</p>
HTML;
    }
}
