-- =====================================================
-- NOTIFICATIONS SYSTEM CLEANUP FOR MYSQL DATABASE
-- =====================================================
-- This file contains the SQL commands to remove the notifications
-- system from the ProgrammArti Gestionale database.
-- 
-- Execute this file to clean up notification-related tables.
-- =====================================================

USE `programmarti_gestionale`;

-- Drop notifications table if it exists
DROP TABLE IF EXISTS `notifications`;

-- Remove notification-related migration records
DELETE FROM `migrations` WHERE `migration` LIKE '%notifications%';

-- Add cleanup migration record
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2025_01_27_130000_drop_notifications_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Use these queries to verify the cleanup:

-- Check if notifications table was dropped
-- SHOW TABLES LIKE 'notifications';
-- (Should return empty result)

-- Check migration records
-- SELECT * FROM migrations WHERE migration LIKE '%notifications%';
-- (Should only show the drop migration)

-- =====================================================
-- NOTES
-- =====================================================
-- This cleanup script:
-- - Drops the notifications table completely
-- - Removes old notification migration records
-- - Adds the cleanup migration record
-- 
-- The notifications system has been completely removed from:
-- - Models (Notification.php)
-- - Controllers (NotificationController.php)
-- - Services (NotificationService.php)
-- - Views (notifications/index.blade.php)
-- - Events and Listeners
-- - Routes
-- - JavaScript code
-- - UI elements (bell icon, dropdowns)
-- 
-- All other functionality remains intact, including:
-- - User management
-- - Client management
-- - Project management
-- - Payment management
-- - Expense management
-- - Work management
-- - Appointment management (with working AJAX)
-- =====================================================
