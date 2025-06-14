-- -----------------------------------------------------
-- Schema Preventivi - Modulo Gestionale
-- -----------------------------------------------------
-- Script di creazione tabelle per il modulo Preventivi
-- Compatibile con MySQL 8.x e MariaDB 10.x
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Tabella `preventivi`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `preventivi` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_number` VARCHAR(20) NOT NULL COMMENT 'Numero univoco del preventivo (formato PREV-YYYY-NNNN)',
  `client_id` BIGINT UNSIGNED NOT NULL COMMENT 'Riferimento al cliente',
  `project_id` BIGINT UNSIGNED NOT NULL COMMENT 'Riferimento al progetto',
  `description` TEXT NOT NULL COMMENT 'Descrizione generale del preventivo',
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Importo totale calcolato dalle voci',
  `status` ENUM('draft', 'sent', 'accepted', 'rejected') NOT NULL DEFAULT 'draft' COMMENT 'Stato del preventivo',
  `ai_processed` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Flag che indica se il preventivo è stato elaborato con AI',
  `pdf_path` VARCHAR(255) NULL COMMENT 'Percorso del file PDF generato',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data di creazione',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data di ultima modifica',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `preventivi_quote_number_unique` (`quote_number` ASC),
  INDEX `preventivi_client_id_foreign` (`client_id` ASC),
  INDEX `preventivi_project_id_foreign` (`project_id` ASC),
  INDEX `preventivi_status_index` (`status` ASC),
  CONSTRAINT `preventivi_client_id_foreign`
    FOREIGN KEY (`client_id`)
    REFERENCES `clients` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `preventivi_project_id_foreign`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Tabella principale dei preventivi';

-- -----------------------------------------------------
-- Tabella `preventivo_items`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `preventivo_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `preventivo_id` BIGINT UNSIGNED NOT NULL COMMENT 'Riferimento al preventivo',
  `description` VARCHAR(255) NOT NULL COMMENT 'Descrizione della voce di lavoro',
  `cost` DECIMAL(8,2) NOT NULL COMMENT 'Costo della voce',
  `ai_enhanced_description` TEXT NULL COMMENT 'Descrizione migliorata tramite AI',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data di creazione',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data di ultima modifica',
  PRIMARY KEY (`id`),
  INDEX `preventivo_items_preventivo_id_foreign` (`preventivo_id` ASC),
  CONSTRAINT `preventivo_items_preventivo_id_foreign`
    FOREIGN KEY (`preventivo_id`)
    REFERENCES `preventivi` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Voci di lavoro associate ai preventivi';

-- -----------------------------------------------------
-- Trigger per aggiornare il totale del preventivo
-- -----------------------------------------------------
DELIMITER $$

CREATE TRIGGER `preventivo_items_after_insert` AFTER INSERT ON `preventivo_items`
FOR EACH ROW
BEGIN
    UPDATE `preventivi` 
    SET `total_amount` = (
        SELECT SUM(`cost`) 
        FROM `preventivo_items` 
        WHERE `preventivo_id` = NEW.`preventivo_id`
    ),
    `updated_at` = NOW()
    WHERE `id` = NEW.`preventivo_id`;
END$$

CREATE TRIGGER `preventivo_items_after_update` AFTER UPDATE ON `preventivo_items`
FOR EACH ROW
BEGIN
    UPDATE `preventivi` 
    SET `total_amount` = (
        SELECT SUM(`cost`) 
        FROM `preventivo_items` 
        WHERE `preventivo_id` = NEW.`preventivo_id`
    ),
    `updated_at` = NOW()
    WHERE `id` = NEW.`preventivo_id`;
END$$

CREATE TRIGGER `preventivo_items_after_delete` AFTER DELETE ON `preventivo_items`
FOR EACH ROW
BEGIN
    UPDATE `preventivi` 
    SET `total_amount` = (
        SELECT COALESCE(SUM(`cost`), 0) 
        FROM `preventivo_items` 
        WHERE `preventivo_id` = OLD.`preventivo_id`
    ),
    `updated_at` = NOW()
    WHERE `id` = OLD.`preventivo_id`;
END$$

DELIMITER ;

-- -----------------------------------------------------
-- Procedura per generare un nuovo numero preventivo
-- -----------------------------------------------------
DELIMITER $$

CREATE PROCEDURE `generate_quote_number`(OUT new_quote_number VARCHAR(20))
BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE last_number INT;
    DECLARE new_number INT;
    
    -- Ottieni l'anno corrente
    SET year_part = YEAR(CURDATE());
    
    -- Trova l'ultimo numero utilizzato per l'anno corrente
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(quote_number, '-', -1) AS UNSIGNED)), 0)
    INTO last_number
    FROM `preventivi`
    WHERE quote_number LIKE CONCAT('PREV-', year_part, '-%');
    
    -- Incrementa il numero
    SET new_number = last_number + 1;
    
    -- Formatta il nuovo numero preventivo
    SET new_quote_number = CONCAT('PREV-', year_part, '-', LPAD(new_number, 4, '0'));
END$$

DELIMITER ;