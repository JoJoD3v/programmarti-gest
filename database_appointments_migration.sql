-- =====================================================
-- APPOINTMENTS TABLE MIGRATION FOR MYSQL DATABASE
-- =====================================================
-- This file contains the SQL commands to create the appointments table
-- and insert sample data for the ProgrammArti Gestionale system.
-- 
-- Execute this file after ensuring the main database is set up.
-- =====================================================

USE `programmarti_gestionale`;

-- Create appointments table
CREATE TABLE `appointments` (
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
  CONSTRAINT `appointments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample appointments data
INSERT INTO `appointments` (`id`, `client_id`, `user_id`, `appointment_date`, `appointment_name`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-01-28 10:00:00', 'Consulenza Fiscale Q4 2024', 'Revisione documenti fiscali e preparazione dichiarazione', 'pending', NOW(), NOW()),
(2, 2, 2, '2025-01-28 14:30:00', 'Riunione Progetto Website', 'Discussione requisiti e timeline per nuovo sito web', 'pending', NOW(), NOW()),
(3, 3, 3, '2025-01-28 16:00:00', 'Supporto Tecnico Sistema', 'Risoluzione problemi sistema gestionale', 'pending', NOW(), NOW()),
(4, 4, 1, '2025-01-29 09:00:00', 'Pianificazione Strategica 2025', 'Definizione obiettivi e budget per il nuovo anno', 'pending', NOW(), NOW()),
(5, 1, 2, '2025-01-29 11:30:00', 'Follow-up Progetto Mobile App', 'Verifica avanzamento sviluppo applicazione mobile', 'pending', NOW(), NOW()),
(6, 2, 4, '2025-01-29 15:00:00', 'Presentazione Proposta Design', 'Presentazione mockup e concept design', 'pending', NOW(), NOW()),
(7, 3, 1, '2025-01-27 10:00:00', 'Consulenza Legale Contratti', 'Revisione contratti fornitori', 'completed', NOW(), NOW()),
(8, 4, 3, '2025-01-27 14:00:00', 'Training Sistema CRM', 'Formazione utilizzo nuovo sistema CRM', 'completed', NOW(), NOW()),
(9, 1, 2, '2025-01-26 16:30:00', 'Riunione Mensile', 'Riunione di allineamento mensile', 'absent', NOW(), NOW()),
(10, 2, 4, '2025-01-25 11:00:00', 'Presentazione Risultati Q4', 'Presentazione risultati ultimo trimestre', 'cancelled', NOW(), NOW());

-- Add migration record to migrations table
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2025_01_27_120000_create_appointments_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Use these queries to verify the installation:

-- Check if table was created successfully
-- SELECT COUNT(*) as appointment_count FROM appointments;

-- Check appointments with client and user details
-- SELECT 
--     a.id,
--     a.appointment_name,
--     a.appointment_date,
--     a.status,
--     CONCAT(c.first_name, ' ', c.last_name) as client_name,
--     CONCAT(u.first_name, ' ', u.last_name) as assigned_user
-- FROM appointments a
-- JOIN clients c ON a.client_id = c.id
-- JOIN users u ON a.user_id = u.id
-- ORDER BY a.appointment_date DESC;

-- Check today's appointments
-- SELECT 
--     a.appointment_name,
--     a.appointment_date,
--     a.status,
--     CONCAT(c.first_name, ' ', c.last_name) as client_name
-- FROM appointments a
-- JOIN clients c ON a.client_id = c.id
-- WHERE DATE(a.appointment_date) = CURDATE()
-- ORDER BY a.appointment_date ASC;

-- =====================================================
-- NOTES
-- =====================================================
-- Sample data includes:
-- - 10 sample appointments across different dates
-- - Mix of different statuses (pending, completed, cancelled, absent)
-- - Appointments assigned to different users
-- - Appointments for different clients
-- - Realistic appointment names and notes
-- 
-- The appointments table includes:
-- - Foreign key constraints to clients and users tables
-- - Proper indexes for performance
-- - ENUM status field with 4 possible values
-- - DateTime field for precise appointment scheduling
-- - Optional notes field for additional information
-- =====================================================
