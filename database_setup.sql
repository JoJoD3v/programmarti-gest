-- =====================================================
-- ProgrammArti Gestionale - Database Setup MySQL
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS `programmarti_gestionale` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `programmarti_gestionale`;

-- =====================================================
-- TABLES CREATION
-- =====================================================

-- Users table
CREATE TABLE `users` (
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

-- Password reset tokens table
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE `sessions` (
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

-- Cache table
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache locks table
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs table
CREATE TABLE `jobs` (
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

-- Job batches table
CREATE TABLE `job_batches` (
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

-- Failed jobs table
CREATE TABLE `failed_jobs` (
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

-- Permissions table (Spatie)
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table (Spatie)
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model has permissions table (Spatie)
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model has roles table (Spatie)
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role has permissions table (Spatie)
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clients table
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` enum('individual','business') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `vat_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects table
CREATE TABLE `projects` (
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
  CONSTRAINT `projects_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments table
CREATE TABLE `payments` (
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
  CONSTRAINT `payments_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expenses table
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('office_supplies','travel','software','hardware','marketing','training','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrations table
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert permissions
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'manage users', 'web', NOW(), NOW()),
(2, 'manage clients', 'web', NOW(), NOW()),
(3, 'manage projects', 'web', NOW(), NOW()),
(4, 'manage payments', 'web', NOW(), NOW()),
(5, 'manage expenses', 'web', NOW(), NOW()),
(6, 'generate invoices', 'web', NOW(), NOW()),
(7, 'send emails', 'web', NOW(), NOW()),
(8, 'view dashboard', 'web', NOW(), NOW());

-- Insert roles
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', NOW(), NOW()),
(2, 'manager', 'web', NOW(), NOW()),
(3, 'employee', 'web', NOW(), NOW());

-- Assign permissions to roles
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
-- Admin has all permissions
(1, 1), (2, 1), (3, 1), (4, 1), (5, 1), (6, 1), (7, 1), (8, 1),
-- Manager has most permissions except user management
(2, 2), (3, 2), (4, 2), (5, 2), (6, 2), (7, 2), (8, 2),
-- Employee has limited permissions
(3, 3), (4, 3), (5, 3), (8, 3);

-- Insert sample users
INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `email_verified_at`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Mario', 'Rossi', 'admin', 'admin@programmarti.com', NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(2, 'Giulia', 'Bianchi', 'manager', 'manager@programmarti.com', NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(3, 'Luca', 'Verdi', 'developer', 'developer@programmarti.com', NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(4, 'Sara', 'Neri', 'designer', 'designer@programmarti.com', NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Assign roles to users
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1), -- Admin
(2, 'App\\Models\\User', 2), -- Manager
(3, 'App\\Models\\User', 3), -- Employee
(3, 'App\\Models\\User', 4); -- Employee

-- Insert sample clients
INSERT INTO `clients` (`id`, `entity_type`, `first_name`, `last_name`, `email`, `phone`, `address`, `vat_number`, `tax_code`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'business', 'TechSolutions', 'S.r.l.', 'info@techsolutions.it', '+39 06 12345678', 'Via Roma 123, 00100 Roma (RM)', '12345678901', 'TCHS12345678901', 'Cliente enterprise per sviluppo software', NOW(), NOW()),
(2, 'individual', 'Marco', 'Ferrari', 'marco.ferrari@email.com', '+39 335 1234567', 'Via Milano 45, 20100 Milano (MI)', NULL, 'FRRMRC85M15F205Z', 'Libero professionista', NOW(), NOW()),
(3, 'business', 'Fashion', 'Store', 'contact@fashionstore.it', '+39 02 87654321', 'Corso Buenos Aires 78, 20124 Milano (MI)', '98765432109', 'FSHS98765432109', 'Negozio di abbigliamento online', NOW(), NOW()),
(4, 'individual', 'Anna', 'Colombo', 'anna.colombo@gmail.com', '+39 347 9876543', 'Via Napoli 67, 80100 Napoli (NA)', NULL, 'CLMNNA90A41F839X', 'Consulente marketing', NOW(), NOW());

-- Insert sample projects
INSERT INTO `projects` (`id`, `name`, `description`, `project_type`, `client_id`, `assigned_user_id`, `start_date`, `end_date`, `status`, `payment_type`, `total_cost`, `has_down_payment`, `down_payment_amount`, `payment_frequency`, `installment_amount`, `installment_count`, `created_at`, `updated_at`) VALUES
(1, 'Sito Web Aziendale TechSolutions', 'Sviluppo sito web responsive con CMS per TechSolutions', 'website', 1, 3, '2024-01-15', '2024-03-15', 'completed', 'installments', 5000.00, 1, 1500.00, 'monthly', 1750.00, 2, NOW(), NOW()),
(2, 'E-commerce Fashion Store', 'Piattaforma e-commerce completa con gestione inventario', 'ecommerce', 3, 3, '2024-02-01', '2024-05-01', 'in_progress', 'installments', 12000.00, 1, 3000.00, 'monthly', 3000.00, 3, NOW(), NOW()),
(3, 'Consulenza Marketing Anna Colombo', 'Strategia marketing digitale e gestione social media', 'marketing_campaign', 4, 4, '2024-03-01', '2024-06-01', 'in_progress', 'one_time', 2500.00, 0, NULL, NULL, NULL, NULL, NOW(), NOW()),
(4, 'Sistema Gestionale Marco Ferrari', 'Sviluppo sistema gestionale personalizzato', 'management_system', 2, 3, '2024-04-01', '2024-07-01', 'planning', 'installments', 8000.00, 1, 2000.00, 'monthly', 2000.00, 3, NOW(), NOW());

-- Insert sample payments
INSERT INTO `payments` (`id`, `invoice_number`, `project_id`, `client_id`, `assigned_user_id`, `amount`, `due_date`, `paid_date`, `status`, `payment_type`, `installment_number`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'INV-2024-0001', 1, 1, 3, 1500.00, '2024-01-15', '2024-01-15', 'completed', 'down_payment', NULL, 'Acconto progetto sito web', NOW(), NOW()),
(2, 'INV-2024-0002', 1, 1, 3, 1750.00, '2024-02-15', '2024-02-15', 'completed', 'installment', 1, 'Prima rata', NOW(), NOW()),
(3, 'INV-2024-0003', 1, 1, 3, 1750.00, '2024-03-15', '2024-03-15', 'completed', 'installment', 2, 'Seconda rata finale', NOW(), NOW()),
(4, 'INV-2024-0004', 2, 3, 3, 3000.00, '2024-02-01', '2024-02-01', 'completed', 'down_payment', NULL, 'Acconto e-commerce', NOW(), NOW()),
(5, NULL, 2, 3, 3, 3000.00, '2024-03-01', NULL, 'pending', 'installment', 1, 'Prima rata e-commerce', NOW(), NOW()),
(6, NULL, 2, 3, 3, 3000.00, '2024-04-01', NULL, 'pending', 'installment', 2, 'Seconda rata e-commerce', NOW(), NOW()),
(7, NULL, 2, 3, 3, 3000.00, '2024-05-01', NULL, 'pending', 'installment', 3, 'Rata finale e-commerce', NOW(), NOW()),
(8, NULL, 3, 4, 4, 2500.00, '2024-06-01', NULL, 'pending', 'one_time', NULL, 'Pagamento unico consulenza marketing', NOW(), NOW()),
(9, NULL, 4, 2, 3, 2000.00, '2024-04-01', NULL, 'pending', 'down_payment', NULL, 'Acconto sistema gestionale', NOW(), NOW());

-- Insert sample expenses
INSERT INTO `expenses` (`id`, `user_id`, `amount`, `description`, `category`, `expense_date`, `created_at`, `updated_at`) VALUES
(1, 3, 150.00, 'Licenza software sviluppo annuale', 'software', '2024-01-10', NOW(), NOW()),
(2, 4, 85.50, 'Abbonamento Adobe Creative Suite', 'software', '2024-01-15', NOW(), NOW()),
(3, 2, 45.00, 'Materiale ufficio (carta, penne, etc.)', 'office_supplies', '2024-01-20', NOW(), NOW()),
(4, 3, 200.00, 'Corso online Laravel avanzato', 'training', '2024-02-01', NOW(), NOW()),
(5, 1, 120.00, 'Spese viaggio cliente Milano', 'travel', '2024-02-15', NOW(), NOW()),
(6, 4, 75.00, 'Stock photos per progetti clienti', 'marketing', '2024-02-20', NOW(), NOW()),
(7, 3, 300.00, 'Nuovo monitor per sviluppo', 'hardware', '2024-03-01', NOW(), NOW()),
(8, 2, 60.00, 'Pranzi di lavoro con clienti', 'other', '2024-03-10', NOW(), NOW());

-- Insert sample notifications
INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'payment_created', 'Nuovo Pagamento Creato', 'È stato creato un nuovo pagamento di €3000.00 per il progetto "E-commerce Fashion Store"', '{"payment_id": 5, "project_id": 2, "client_id": 3, "amount": 3000}', NULL, NOW(), NOW()),
(2, 3, 'project_assigned', 'Progetto Assegnato', 'Ti è stato assegnato il progetto "Sistema Gestionale Marco Ferrari" per il cliente Marco Ferrari', '{"project_id": 4, "client_id": 2, "project_name": "Sistema Gestionale Marco Ferrari", "client_name": "Marco Ferrari"}', NULL, NOW(), NOW()),
(3, 1, 'payment_due', 'Pagamento in Scadenza', 'Il pagamento di €3000.00 per il progetto "E-commerce Fashion Store" scade tra 7 giorni', '{"payment_id": 5, "project_id": 2, "client_id": 3, "amount": 3000, "due_date": "2024-03-01", "days_until_due": 7}', '2024-02-22 10:30:00', NOW(), NOW()),
(4, 3, 'payment_due', 'Pagamento in Scadenza', 'Il pagamento di €3000.00 per il progetto "E-commerce Fashion Store" scade tra 7 giorni', '{"payment_id": 5, "project_id": 2, "client_id": 3, "amount": 3000, "due_date": "2024-03-01", "days_until_due": 7}', NULL, NOW(), NOW());

-- Insert migration records
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_06_09_111644_create_permission_tables', 1),
(5, '2025_06_09_111711_create_clients_table', 1),
(6, '2025_06_09_111717_create_projects_table', 1),
(7, '2025_06_09_111722_create_payments_table', 1),
(8, '2025_06_09_111727_create_expenses_table', 1),
(9, '2025_06_09_123242_create_notifications_table', 1);

-- =====================================================
-- NOTES
-- =====================================================
-- Default password for all users: 'password'
-- Admin user: admin@programmarti.com / password
-- Manager user: manager@programmarti.com / password
-- Employee users: developer@programmarti.com / password, designer@programmarti.com / password
--
-- The database includes:
-- - 4 sample users with different roles
-- - 4 sample clients (2 business, 2 individual)
-- - 4 sample projects in different states
-- - 9 sample payments (some completed, some pending)
-- - 8 sample expenses across different categories
-- - 4 sample notifications
--
-- All foreign key relationships are properly set up
-- All permissions and roles are configured
-- =====================================================
