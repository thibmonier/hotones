-- Script SQL pour ajouter les colonnes manquantes à project_task
-- À exécuter manuellement si la migration automatique ne fonctionne pas

-- Vérifier et ajouter la colonne estimated_hours_sold
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND COLUMN_NAME = 'estimated_hours_sold') > 0,
    'SELECT "Column estimated_hours_sold already exists"',
    'ALTER TABLE project_task ADD COLUMN estimated_hours_sold INT DEFAULT NULL COMMENT "Heures vendues estimées"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne estimated_hours_revised
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND COLUMN_NAME = 'estimated_hours_revised') > 0,
    'SELECT "Column estimated_hours_revised already exists"',
    'ALTER TABLE project_task ADD COLUMN estimated_hours_revised INT DEFAULT NULL COMMENT "Heures révisées estimées"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne progress_percentage
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND COLUMN_NAME = 'progress_percentage') > 0,
    'SELECT "Column progress_percentage already exists"',
    'ALTER TABLE project_task ADD COLUMN progress_percentage INT DEFAULT 0 COMMENT "Pourcentage d\'avancement (0-100)"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne assigned_contributor_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND COLUMN_NAME = 'assigned_contributor_id') > 0,
    'SELECT "Column assigned_contributor_id already exists"',
    'ALTER TABLE project_task ADD COLUMN assigned_contributor_id INT DEFAULT NULL COMMENT "ID du contributeur assigné"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter la contrainte de clé étrangère si elle n'existe pas
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND CONSTRAINT_NAME = 'FK_project_task_assigned_contributor') > 0,
    'SELECT "Foreign key FK_project_task_assigned_contributor already exists"',
    'ALTER TABLE project_task ADD CONSTRAINT FK_project_task_assigned_contributor FOREIGN KEY (assigned_contributor_id) REFERENCES contributor (id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter l'index si il n'existe pas
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'project_task' 
     AND INDEX_NAME = 'IDX_project_task_assigned_contributor') > 0,
    'SELECT "Index IDX_project_task_assigned_contributor already exists"',
    'CREATE INDEX IDX_project_task_assigned_contributor ON project_task (assigned_contributor_id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;