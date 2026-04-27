-- Actualizacion: pago parcial por entradas + historial de acciones
-- Motor objetivo: MySQL 5.7+
-- Ejecutar con la base correcta seleccionada.

SET @current_db = DATABASE();

-- -----------------------------------------------------
-- 1. BOOKINGS: campos para pago parcial por entradas
-- -----------------------------------------------------

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings'
      AND column_name = 'partial_by_entries'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings` ADD COLUMN `partial_by_entries` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mp`',
    'SELECT ''skip bookings.partial_by_entries'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings'
      AND column_name = 'paid_entries'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings` ADD COLUMN `paid_entries` INT(11) NOT NULL DEFAULT 0 AFTER `partial_by_entries`',
    'SELECT ''skip bookings.paid_entries'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings'
      AND column_name = 'IdPedido'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings` ADD COLUMN `IdPedido` VARCHAR(40) NULL AFTER `paid_entries`',
    'SELECT ''skip bookings.IdPedido'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill IdPedido para reservas existentes
UPDATE `bookings`
SET `IdPedido` = CONCAT('RES-', LPAD(`id`, 8, '0'))
WHERE (`IdPedido` IS NULL OR `IdPedido` = '');

-- Para reservas ya pagadas totalmente, marcar entradas pagadas con visitors.
-- Ojo: si alguna reserva vieja tenia pago parcial real, revisar manualmente.
UPDATE `bookings`
SET `paid_entries` = IFNULL(`visitors`, 0)
WHERE IFNULL(`total_payment`, 0) > 0
  AND IFNULL(`paid_entries`, 0) = 0;

-- Indice IdPedido en bookings
SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = @current_db
      AND table_name = 'bookings'
      AND index_name = 'idx_bookings_IdPedido'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `bookings` ADD INDEX `idx_bookings_IdPedido` (`IdPedido`)',
    'SELECT ''skip index bookings.idx_bookings_IdPedido'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 2. PAYMENTS: detalle para pagos por entradas
-- -----------------------------------------------------

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'payments'
      AND column_name = 'paid_entries'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `payments` ADD COLUMN `paid_entries` INT(11) NULL AFTER `amount`',
    'SELECT ''skip payments.paid_entries'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'payments'
      AND column_name = 'unit_price'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `payments` ADD COLUMN `unit_price` DECIMAL(14,2) NULL AFTER `paid_entries`',
    'SELECT ''skip payments.unit_price'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'payments'
      AND column_name = 'payment_type'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `payments` ADD COLUMN `payment_type` VARCHAR(30) NULL AFTER `unit_price`',
    'SELECT ''skip payments.payment_type'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'payments'
      AND column_name = 'created_by_admin'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `payments` ADD COLUMN `created_by_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_type`',
    'SELECT ''skip payments.created_by_admin'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'payments'
      AND column_name = 'admin_user_id'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `payments` ADD COLUMN `admin_user_id` INT(11) UNSIGNED NULL AFTER `created_by_admin`',
    'SELECT ''skip payments.admin_user_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 3. UPLOADS: configuracion general/admin
-- -----------------------------------------------------

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'uploads'
      AND column_name = 'enable_pay_by_entries'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `uploads` ADD COLUMN `enable_pay_by_entries` TINYINT(1) NOT NULL DEFAULT 0 AFTER `invoice_email_message`',
    'SELECT ''skip uploads.enable_pay_by_entries'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'uploads'
      AND column_name = 'pay_by_entries_min_entries'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `uploads` ADD COLUMN `pay_by_entries_min_entries` INT(11) NOT NULL DEFAULT 0 AFTER `enable_pay_by_entries`',
    'SELECT ''skip uploads.pay_by_entries_min_entries'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'uploads'
      AND column_name = 'pay_by_entries_min_days_before_booking'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `uploads` ADD COLUMN `pay_by_entries_min_days_before_booking` INT(11) NOT NULL DEFAULT 0 AFTER `pay_by_entries_min_entries`',
    'SELECT ''skip uploads.pay_by_entries_min_days_before_booking'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 4. BOOKINGS_ACCTION: historial/auditoria de acciones
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `bookings_acction` (
    `ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `IdPedido` VARCHAR(40) NOT NULL,
    `fechaHora` DATETIME NOT NULL,
    `Accion` CHAR(1) NOT NULL COMMENT 'A=Alta, M=Modificacion, B=Baja, C=Cancelacion, P=Pago, R=Reactivacion',
    `observacion` TEXT NULL,
    `usuario` VARCHAR(120) NULL,
    PRIMARY KEY (`ID`),
    KEY `idx_bookings_acction_IdPedido` (`IdPedido`),
    KEY `idx_bookings_acction_fechaHora` (`fechaHora`),
    KEY `idx_bookings_acction_Accion` (`Accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existia, asegurar columnas faltantes
SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND column_name = 'IdPedido'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings_acction` ADD COLUMN `IdPedido` VARCHAR(40) NOT NULL AFTER `ID`',
    'SELECT ''skip bookings_acction.IdPedido'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND column_name = 'fechaHora'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings_acction` ADD COLUMN `fechaHora` DATETIME NOT NULL AFTER `IdPedido`',
    'SELECT ''skip bookings_acction.fechaHora'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND column_name = 'Accion'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings_acction` ADD COLUMN `Accion` CHAR(1) NOT NULL AFTER `fechaHora`',
    'SELECT ''skip bookings_acction.Accion'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND column_name = 'observacion'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings_acction` ADD COLUMN `observacion` TEXT NULL AFTER `Accion`',
    'SELECT ''skip bookings_acction.observacion'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @field_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND column_name = 'usuario'
);
SET @sql = IF(@field_exists = 0,
    'ALTER TABLE `bookings_acction` ADD COLUMN `usuario` VARCHAR(120) NULL AFTER `observacion`',
    'SELECT ''skip bookings_acction.usuario'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indices en bookings_acction si faltan
SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND index_name = 'idx_bookings_acction_IdPedido'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `bookings_acction` ADD INDEX `idx_bookings_acction_IdPedido` (`IdPedido`)',
    'SELECT ''skip index bookings_acction.IdPedido'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND index_name = 'idx_bookings_acction_fechaHora'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `bookings_acction` ADD INDEX `idx_bookings_acction_fechaHora` (`fechaHora`)',
    'SELECT ''skip index bookings_acction.fechaHora'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = @current_db
      AND table_name = 'bookings_acction'
      AND index_name = 'idx_bookings_acction_Accion'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `bookings_acction` ADD INDEX `idx_bookings_acction_Accion` (`Accion`)',
    'SELECT ''skip index bookings_acction.Accion'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 5. Opcional: registrar alta historica para reservas existentes
-- -----------------------------------------------------

INSERT INTO `bookings_acction` (`IdPedido`, `fechaHora`, `Accion`, `observacion`, `usuario`)
SELECT b.`IdPedido`, NOW(), 'A', CONCAT('Alta historica de reserva por ', IFNULL(b.`visitors`, 0), ' entradas.'), 'sistema'
FROM `bookings` b
WHERE b.`IdPedido` IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `bookings_acction` ba
      WHERE ba.`IdPedido` = b.`IdPedido`
        AND ba.`Accion` = 'A'
  );
