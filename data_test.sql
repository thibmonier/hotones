-- Technologies
INSERT INTO technologies (name, category, color, active) VALUES
('Symfony', 'framework', '#000000', 1),
('Laravel', 'framework', '#FF2D20', 1),
('Vue.js', 'framework', '#4FC08D', 1),
('React', 'framework', '#61DAFB', 1),
('Nuxt.js', 'framework', '#00DC82', 1),
('WordPress', 'cms', '#21759B', 1),
('Drupal', 'cms', '#0073BA', 1),
('Bootstrap', 'library', '#7952B3', 1),
('Tailwind CSS', 'library', '#38B2AC', 1),
('MySQL', 'database', '#4479A1', 1),
('PostgreSQL', 'database', '#336791', 1),
('Docker', 'tool', '#2496ED', 1),
('Varnish', 'tool', '#FF4500', 1),
('CloudFlare', 'hosting', '#F38020', 1);

-- Service Categories
INSERT INTO service_categories (name, description, color, active) VALUES
('Brand', 'Création de sites vitrine et institutionnels', '#6C5CE7', 1),
('E-commerce', 'Boutiques en ligne et solutions marchandes', '#00B894', 1),
('Application métier', 'Applications sur mesure et outils internes', '#0984E3', 1),
('Maintenance', 'Maintenance corrective et évolutive', '#FDCB6E', 1),
('SEO/SEA', 'Référencement naturel et payant', '#E17055', 1),
('Hébergement', 'Solutions d\'hébergement et infogérance', '#A29BFE', 1),
('Licences', 'Licences logicielles et outils', '#FD79A8', 1);

-- Profiles
INSERT INTO profiles (name, description, default_daily_rate, color, active) VALUES
('Développeur', 'Développement frontend et backend', 400.00, '#0984E3', 1),
('Lead Développeur', 'Développeur senior avec responsabilités techniques', 600.00, '#6C5CE7', 1),
('Chef de projet', 'Gestion de projet et relation client', 500.00, '#00B894', 1),
('Product Owner', 'Définition des besoins et coordination produit', 550.00, '#E17055', 1),
('Designer', 'Création graphique et UX/UI', 450.00, '#FDCB6E', 1);