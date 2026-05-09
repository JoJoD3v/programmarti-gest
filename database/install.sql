-- =====================================================================
-- ProgrammArti Gestionale — Script di Installazione Database Completo
-- =====================================================================
-- Versione consolidata di tutti gli script SQL.
-- Eseguire con: mysql -u root -p < database/install.sql
--
-- IMPORTANTE: Fare un backup del database prima di eseguire questo
-- script su un'installazione esistente.
-- =====================================================================

-- Crea il database se non esiste
CREATE DATABASE IF NOT EXISTS `programmarti_gestionale`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `programmarti_gestionale`;

-- Disabilita controlli FK durante la creazione per evitare errori di ordine
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- TABELLE DI SISTEMA
-- =====================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PERMESSI E RUOLI (Spatie Laravel Permission)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign`
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign`
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABELLE APPLICAZIONE
-- =====================================================================

CREATE TABLE IF NOT EXISTS `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` enum('individual','business') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `vat_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `project_type` enum('website','ecommerce','management_system','marketing_campaign','social_media_management','nfc_accessories') COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `assigned_user_id` bigint unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planning',
  `payment_type` enum('one_time','installments') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `total_cost` decimal(10,2) DEFAULT NULL,
  `has_down_payment` tinyint(1) NOT NULL DEFAULT '0',
  `down_payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_frequency` enum('monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installment_amount` decimal(10,2) DEFAULT NULL,
  `installment_count` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_client_id_foreign` (`client_id`),
  KEY `projects_assigned_user_id_foreign` (`assigned_user_id`),
  CONSTRAINT `projects_assigned_user_id_foreign`
    FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_client_id_foreign`
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `assigned_user_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','overdue','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_type` enum('down_payment','installment','one_time') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `installment_number` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_invoice_number_unique` (`invoice_number`),
  KEY `payments_project_id_foreign` (`project_id`),
  KEY `payments_client_id_foreign` (`client_id`),
  KEY `payments_assigned_user_id_foreign` (`assigned_user_id`),
  CONSTRAINT `payments_assigned_user_id_foreign`
    FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_client_id_foreign`
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_project_id_foreign`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  CONSTRAINT `expenses_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `works` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Bug','Miglioramenti','Da fare') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_user_id` bigint unsigned NOT NULL,
  `status` enum('In Sospeso','Completato') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'In Sospeso',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `works_project_id_foreign` (`project_id`),
  KEY `works_assigned_user_id_foreign` (`assigned_user_id`),
  CONSTRAINT `works_project_id_foreign`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `works_assigned_user_id_foreign`
    FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `appointment_date` datetime NOT NULL,
  `appointment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','cancelled','absent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_client_id_foreign` (`client_id`),
  KEY `appointments_user_id_foreign` (`user_id`),
  KEY `appointments_appointment_date_index` (`appointment_date`),
  KEY `appointments_status_index` (`status`),
  CONSTRAINT `appointments_client_id_foreign`
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preventivi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Formato PREV-YYYY-NNNN',
  `client_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 22.00,
  `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `ai_processed` tinyint(1) NOT NULL DEFAULT 0,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preventivi_quote_number_unique` (`quote_number`),
  KEY `preventivi_client_id_foreign` (`client_id`),
  KEY `preventivi_status_index` (`status`),
  CONSTRAINT `preventivi_client_id_foreign`
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preventivo_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `preventivo_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(8,2) NOT NULL,
  `ai_enhanced_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `preventivo_items_preventivo_id_foreign` (`preventivo_id`),
  CONSTRAINT `preventivo_items_preventivo_id_foreign`
    FOREIGN KEY (`preventivo_id`) REFERENCES `preventivi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Riabilita controlli FK
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATI DI BASE — PERMESSI E RUOLI
-- =====================================================================

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('manage users',    'web', NOW(), NOW()),
('manage clients',  'web', NOW(), NOW()),
('manage projects', 'web', NOW(), NOW()),
('manage payments', 'web', NOW(), NOW()),
('manage expenses', 'web', NOW(), NOW()),
('generate invoices','web', NOW(), NOW()),
('send emails',     'web', NOW(), NOW()),
('view dashboard',  'web', NOW(), NOW()),
('manage works',    'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('admin',    'web', NOW(), NOW()),
('manager',  'web', NOW(), NOW()),
('employee', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Permessi admin (tutti)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id FROM `permissions` p CROSS JOIN `roles` r WHERE r.name = 'admin'
ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;

-- Permessi manager (tutto tranne manage users)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id FROM `permissions` p CROSS JOIN `roles` r
WHERE r.name = 'manager' AND p.name != 'manage users'
ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;

-- Permessi employee (limitati)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id FROM `permissions` p CROSS JOIN `roles` r
WHERE r.name = 'employee' AND p.name IN ('manage projects','manage payments','manage expenses','manage works','view dashboard')
ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;

-- =====================================================================
-- DATI DI ESEMPIO (opzionali — commentare se non desiderati)
-- =====================================================================

-- Utenti (password: 'password')
INSERT INTO `users` (`first_name`, `last_name`, `username`, `email`, `email_verified_at`, `password`, `created_at`, `updated_at`) VALUES
('Mario',  'Rossi',   'admin',     'admin@programmarti.com',     NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Giulia', 'Bianchi', 'manager',   'manager@programmarti.com',   NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Luca',   'Verdi',   'developer', 'developer@programmarti.com', NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('Sara',   'Neri',    'designer',  'designer@programmarti.com',  NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Assegna ruoli agli utenti
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)
SELECT r.id, 'App\\Models\\User', u.id FROM `roles` r JOIN `users` u
ON (r.name = 'admin'    AND u.username = 'admin')
OR (r.name = 'manager'  AND u.username = 'manager')
OR (r.name = 'employee' AND u.username IN ('developer','designer'))
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Clienti di esempio
INSERT INTO `clients` (`entity_type`, `first_name`, `last_name`, `email`, `phone`, `address`, `vat_number`, `tax_code`, `created_at`, `updated_at`) VALUES
('business',    'TechSolutions', 'S.r.l.',  'info@techsolutions.it',      '+39 06 12345678', 'Via Roma 123, 00100 Roma', '12345678901', 'TCHS12345678901', NOW(), NOW()),
('individual',  'Marco',         'Ferrari', 'marco.ferrari@email.com',    '+39 335 1234567', 'Via Milano 45, 20100 Milano', NULL, 'FRRMRC85M15F205Z', NOW(), NOW()),
('business',    'Fashion',       'Store',   'contact@fashionstore.it',    '+39 02 87654321', 'Corso Buenos Aires 78, 20124 Milano', '98765432109', 'FSHS98765432109', NOW(), NOW()),
('individual',  'Anna',          'Colombo', 'anna.colombo@gmail.com',     '+39 347 9876543', 'Via Napoli 67, 80100 Napoli', NULL, 'CLMNNA90A41F839X', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- =====================================================================
-- REGISTRO MIGRAZIONI LARAVEL
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2025_01_27_000000_create_works_table', 1),
('2025_01_27_120000_create_appointments_table', 1),
('2025_01_27_140000_create_preventivi_table', 1),
('2025_01_27_140001_create_preventivo_items_table', 1),
('2025_06_09_111644_create_permission_tables', 1),
('2025_06_09_111711_create_clients_table', 1),
('2025_06_09_111717_create_projects_table', 1),
('2025_06_09_111722_create_payments_table', 1),
('2025_06_09_111727_create_expenses_table', 1)
ON DUPLICATE KEY UPDATE `batch` = `batch`;

-- =====================================================================
-- FINE SCRIPT
-- =====================================================================
-- IMPORTANTE: Dopo l'installazione, cambiare tutte le password
-- degli utenti di esempio tramite l'interfaccia amministrativa.
-- Password di default: 'password'
-- =====================================================================
