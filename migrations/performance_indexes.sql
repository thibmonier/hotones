-- ==========================================
-- Script d'optimisation des index
-- Améliore les performances des requêtes courantes
-- ==========================================

-- Index sur timesheet pour recherches par contributeur et date
CREATE INDEX IF NOT EXISTS idx_timesheet_contributor_date 
ON timesheet(contributor_id, date);

-- Index sur timesheet pour recherches par projet et date
CREATE INDEX IF NOT EXISTS idx_timesheet_project_date 
ON timesheet(project_id, date);

-- Index composite sur timesheet pour agrégations
CREATE INDEX IF NOT EXISTS idx_timesheet_contributor_project_date 
ON timesheet(contributor_id, project_id, date);

-- Index sur project pour filtres par statut et client
CREATE INDEX IF NOT EXISTS idx_project_status_client 
ON project(status, client_id);

-- Index sur project pour recherche par type
CREATE INDEX IF NOT EXISTS idx_project_type 
ON project(type);

-- Index sur order (devis) pour filtres par statut et date
CREATE INDEX IF NOT EXISTS idx_order_status_created 
ON `order`(status, created_at);

-- Index sur order pour filtres par client
CREATE INDEX IF NOT EXISTS idx_order_client 
ON `order`(client_id);

-- Index sur invoice pour filtres par statut et échéance
CREATE INDEX IF NOT EXISTS idx_invoice_status_due 
ON invoice(status, due_date);

-- Index sur invoice pour recherche par client
CREATE INDEX IF NOT EXISTS idx_invoice_client 
ON invoice(client_id);

-- Index sur planning pour recherches par contributeur et dates
CREATE INDEX IF NOT EXISTS idx_planning_contributor_dates 
ON planning(contributor_id, start_date, end_date);

-- Index sur planning pour recherches par projet
CREATE INDEX IF NOT EXISTS idx_planning_project 
ON planning(project_id);

-- Index sur vacation pour recherches par contributeur et statut
CREATE INDEX IF NOT EXISTS idx_vacation_contributor_status 
ON vacation(contributor_id, status);

-- Index sur vacation pour recherches par dates
CREATE INDEX IF NOT EXISTS idx_vacation_dates 
ON vacation(start_date, end_date);

-- Index sur employment_period pour recherches par contributeur
CREATE INDEX IF NOT EXISTS idx_employment_period_contributor 
ON employment_period(contributor_id);

-- Index sur notification pour recherches par utilisateur et statut
CREATE INDEX IF NOT EXISTS idx_notification_user_read 
ON notification(user_id, is_read, created_at DESC);

-- Index sur contributor pour recherches par statut actif
CREATE INDEX IF NOT EXISTS idx_contributor_active 
ON contributor(active);

-- Index fulltext pour recherche dans projects (nom, description)
-- ALTER TABLE project ADD FULLTEXT INDEX idx_project_fulltext (name, description);

-- Index fulltext pour recherche dans clients
-- ALTER TABLE client ADD FULLTEXT INDEX idx_client_fulltext (name, siret);

-- ==========================================
-- Analyse des tables pour optimiser le query planner
-- ==========================================
ANALYZE TABLE timesheet;
ANALYZE TABLE project;
ANALYZE TABLE `order`;
ANALYZE TABLE invoice;
ANALYZE TABLE planning;
ANALYZE TABLE vacation;
ANALYZE TABLE contributor;
ANALYZE TABLE employment_period;
ANALYZE TABLE notification;
