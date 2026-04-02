-- ReservaLaberinto
-- Script de actualizacion de base real
-- Fecha: 30/03/2026
-- Motor objetivo: MySQL 5.7+
--
-- Uso:
-- 1. Seleccionar previamente la base correcta.
-- 2. Ejecutar este archivo completo.
-- 3. Si la base ya tiene alguno de estos cambios, el script no deberia romper.

SET @current_db = DATABASE();

-- -----------------------------------------------------
-- 1. Renombrar values -> service_values si corresponde
-- -----------------------------------------------------
SET @values_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = @current_db
      AND table_name = 'values'
);

SET @service_values_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = @current_db
      AND table_name = 'service_values'
);

SET @sql = IF(
    @values_exists = 1 AND @service_values_exists = 0,
    'RENAME TABLE `values` TO `service_values`',
    'SELECT ''skip rename values -> service_values'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 2. Crear service_values si no existe
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_values` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `amount` FLOAT NOT NULL,
    `discount_percentage` DOUBLE NULL DEFAULT 0,
    `extra_amount` FLOAT NULL,
    `disabled` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- discount_percentage
SET @field_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'service_values'
      AND column_name = 'discount_percentage'
);

SET @sql = IF(
    @field_exists = 0,
    'ALTER TABLE `service_values` ADD COLUMN `discount_percentage` DOUBLE NULL DEFAULT 0 AFTER `amount`',
    'SELECT ''skip service_values.discount_percentage'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 3. Crear mercado_pago_keys si no existe
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mercado_pago_keys` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_key` VARCHAR(255) NULL,
    `access_token` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. uploads.notification_email
-- -----------------------------------------------------
SET @field_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'uploads'
      AND column_name = 'notification_email'
);

SET @sql = IF(
    @field_exists = 0,
    'ALTER TABLE `uploads` ADD COLUMN `notification_email` VARCHAR(150) NULL AFTER `secondary_color`',
    'SELECT ''skip uploads.notification_email'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 5. time: dias cerrados
-- -----------------------------------------------------
SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_monday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_monday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_monday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_tuesday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_tuesday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_tuesday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_wednesday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_wednesday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_wednesday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_thursday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_thursday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_thursday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_friday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_friday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_friday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_saturday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_saturday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_saturday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'time' AND column_name = 'is_sunday'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `time` ADD COLUMN `is_sunday` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip time.is_sunday'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 6. customers: alinear esquema
-- -----------------------------------------------------
SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'email'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `email` VARCHAR(255) NULL',
    'SELECT ''skip customers.email'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'type_institution'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `type_institution` VARCHAR(255) NULL',
    'SELECT ''skip customers.type_institution'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'user_name'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `user_name` VARCHAR(255) NULL',
    'SELECT ''skip customers.user_name'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'pass'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `pass` VARCHAR(255) NULL',
    'SELECT ''skip customers.pass'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'deleted'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''skip customers.deleted'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'area_code'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `area_code` VARCHAR(20) NULL',
    'SELECT ''skip customers.area_code'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db AND table_name = 'customers' AND column_name = 'complete_phone'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `customers` ADD COLUMN `complete_phone` VARCHAR(50) NULL',
    'SELECT ''skip customers.complete_phone'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `customers`
    MODIFY COLUMN `last_name` VARCHAR(50) NULL,
    MODIFY COLUMN `dni` VARCHAR(20) NULL,
    MODIFY COLUMN `city` VARCHAR(150) NULL;

-- -----------------------------------------------------
-- 7. booking_slots
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_slots` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date` DATE NOT NULL,
    `id_field` INT(10) UNSIGNED NOT NULL,
    `time_from` VARCHAR(20) NOT NULL,
    `time_until` VARCHAR(20) NOT NULL,
    `booking_id` INT(10) UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_booking_slots_booking_id` (`booking_id`),
    KEY `idx_booking_slots_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @current_db
      AND table_name = 'booking_slots'
      AND index_name = 'uniq_booking_slots_active'
);

SET @sql = IF(
    @index_exists = 0,
    'ALTER TABLE `booking_slots` ADD UNIQUE KEY `uniq_booking_slots_active` (`date`, `id_field`, `time_from`, `time_until`, `active`)',
    'SELECT ''skip booking_slots.uniq_booking_slots_active'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 8. Fin
-- -----------------------------------------------------
SELECT 'Actualizacion de esquema finalizada' AS resultado, @current_db AS base_actual;

