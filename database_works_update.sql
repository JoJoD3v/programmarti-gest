-- =====================================================
-- PROGRAMMARTI GESTIONALE - WORKS MANAGEMENT UPDATE
-- =====================================================
-- This file contains the database updates needed to add
-- the "Gestione Lavori" (Work Management) feature
-- =====================================================

-- Create works table
CREATE TABLE IF NOT EXISTS `works` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('Bug','Miglioramenti','Da fare') NOT NULL,
  `assigned_user_id` bigint(20) unsigned NOT NULL,
  `status` enum('In Sospeso','Completato') NOT NULL DEFAULT 'In Sospeso',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `works_project_id_foreign` (`project_id`),
  KEY `works_assigned_user_id_foreign` (`assigned_user_id`),
  CONSTRAINT `works_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `works_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add description column to existing works table (if table already exists)
ALTER TABLE `works` ADD COLUMN `description` text DEFAULT NULL AFTER `name`;

-- Add 'manage works' permission
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) 
VALUES ('manage works', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Get the permission ID for 'manage works'
SET @manage_works_permission_id = (SELECT id FROM permissions WHERE name = 'manage works');

-- Assign 'manage works' permission to admin role
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) 
SELECT @manage_works_permission_id, id FROM roles WHERE name = 'admin'
ON DUPLICATE KEY UPDATE permission_id = permission_id;

-- Assign 'manage works' permission to manager role (if exists)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) 
SELECT @manage_works_permission_id, id FROM roles WHERE name = 'manager'
ON DUPLICATE KEY UPDATE permission_id = permission_id;

-- Assign 'manage works' permission to employee role (if exists)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) 
SELECT @manage_works_permission_id, id FROM roles WHERE name = 'employee'
ON DUPLICATE KEY UPDATE permission_id = permission_id;

-- Sample works data (optional - only insert if you want sample data)
-- Note: This assumes you have existing projects and users
INSERT INTO `works` (`project_id`, `name`, `description`, `type`, `assigned_user_id`, `status`, `created_at`, `updated_at`)
SELECT
    p.id as project_id,
    'Correzione bug nel modulo di login' as name,
    'Gli utenti non riescono ad accedere al sistema quando inseriscono credenziali valide. Il problema sembra essere legato alla validazione delle sessioni.' as description,
    'Bug' as type,
    u.id as assigned_user_id,
    'Completato' as status,
    DATE_SUB(NOW(), INTERVAL 10 DAY) as created_at,
    DATE_SUB(NOW(), INTERVAL 5 DAY) as updated_at
FROM projects p
CROSS JOIN users u
WHERE u.email = 'developer@programmarti.com'
LIMIT 1;

INSERT INTO `works` (`project_id`, `name`, `description`, `type`, `assigned_user_id`, `status`, `created_at`, `updated_at`)
SELECT
    p.id as project_id,
    'Implementazione sistema di notifiche' as name,
    'Sviluppare un sistema completo di notifiche in tempo reale per informare gli utenti di eventi importanti.' as description,
    'Miglioramenti' as type,
    u.id as assigned_user_id,
    'In Sospeso' as status,
    DATE_SUB(NOW(), INTERVAL 8 DAY) as created_at,
    DATE_SUB(NOW(), INTERVAL 8 DAY) as updated_at
FROM projects p
CROSS JOIN users u
WHERE u.email = 'developer@programmarti.com'
LIMIT 1;

INSERT INTO `works` (`project_id`, `name`, `description`, `type`, `assigned_user_id`, `status`, `created_at`, `updated_at`)
SELECT
    p.id as project_id,
    'Aggiunta sezione FAQ' as name,
    'Creare una sezione FAQ completa per aiutare gli utenti a trovare risposte alle domande più comuni.' as description,
    'Da fare' as type,
    u.id as assigned_user_id,
    'In Sospeso' as status,
    DATE_SUB(NOW(), INTERVAL 4 DAY) as created_at,
    DATE_SUB(NOW(), INTERVAL 4 DAY) as updated_at
FROM projects p
CROSS JOIN users u
WHERE u.email = 'designer@programmarti.com'
LIMIT 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Run these queries to verify the installation

-- Check if works table was created
SELECT 'Works table created' as status, COUNT(*) as table_exists 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'works';

-- Check if permission was added
SELECT 'Manage works permission added' as status, COUNT(*) as permission_exists 
FROM permissions 
WHERE name = 'manage works';

-- Check role permissions
SELECT r.name as role_name, p.name as permission_name
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name = 'manage works';

-- Check sample works data
SELECT 'Sample works data' as status, COUNT(*) as works_count 
FROM works;

-- =====================================================
-- ROLLBACK COMMANDS (if needed)
-- =====================================================
-- Uncomment and run these if you need to rollback the changes

-- DELETE FROM role_has_permissions WHERE permission_id = (SELECT id FROM permissions WHERE name = 'manage works');
-- DELETE FROM permissions WHERE name = 'manage works';
-- DROP TABLE IF EXISTS works;

-- =====================================================
-- NOTES
-- =====================================================
-- 1. Make sure to backup your database before running this script
-- 2. The sample data insertion will only work if you have existing projects and users
-- 3. The script uses ON DUPLICATE KEY UPDATE to prevent errors if run multiple times
-- 4. All foreign key constraints are properly set up for data integrity
-- 5. The works table includes proper indexes for performance
