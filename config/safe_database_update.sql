-- Script SQL SEGURO para actualizar la base de datos Calendar con soporte para n8n
-- Este script NO elimina información existente, solo agrega nuevas funcionalidades

-- ============================================================================
-- 1. ACTUALIZAR TABLA SETTINGS (si existe)
-- ============================================================================

-- Verificar si la tabla settings existe
SET @settings_exists = (SELECT COUNT(*) FROM information_schema.tables 
                       WHERE table_schema = DATABASE() AND table_name = 'settings');

-- Si la tabla settings existe, agregar columnas faltantes
SET @sql = IF(@settings_exists > 0, 
    'ALTER TABLE settings 
     ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER setting_value,
     ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Tabla settings no existe, se creará más adelante" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear tabla settings si no existe
CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting_key (setting_key),
    INDEX idx_setting_key (setting_key)
);

-- Insertar configuración de n8n (solo si no existe)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('n8n_url', 'https://n8n.mangomorado.com'),
('n8n_api_key', ''),
('n8n_broadcast_webhook_url', 'https://n8n.mangomorado.com/webhook/broadcast');

-- ============================================================================
-- 2. ACTUALIZAR TABLA BROADCAST_HISTORY
-- ============================================================================

-- Agregar columnas de n8n a broadcast_history
ALTER TABLE broadcast_history
ADD COLUMN IF NOT EXISTS n8n_workflow_id VARCHAR(255) NULL AFTER status,
ADD COLUMN IF NOT EXISTS n8n_execution_id VARCHAR(255) NULL AFTER n8n_workflow_id,
ADD COLUMN IF NOT EXISTS n8n_metadata JSON NULL AFTER n8n_execution_id;

-- Actualizar ENUM de status para incluir 'queued' (solo si no existe)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'broadcast_history' 
     AND COLUMN_NAME = 'status'
     AND COLUMN_TYPE LIKE '%queued%') = 0,
    'ALTER TABLE broadcast_history MODIFY COLUMN status ENUM("queued", "in_progress", "completed", "failed", "paused") DEFAULT "queued"',
    'SELECT "Estado queued ya existe en broadcast_history" as status'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 3. ACTUALIZAR TABLA BROADCAST_DETAILS
-- ============================================================================

-- Actualizar ENUM de status para incluir 'queued' (solo si no existe)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'broadcast_details' 
     AND COLUMN_NAME = 'status'
     AND COLUMN_TYPE LIKE '%queued%') = 0,
    'ALTER TABLE broadcast_details MODIFY COLUMN status ENUM("queued", "sent", "failed", "pending") DEFAULT "queued"',
    'SELECT "Estado queued ya existe en broadcast_details" as status'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 4. CREAR NUEVAS TABLAS PARA N8N
-- ============================================================================

-- Crear tabla n8n_broadcast_logs
CREATE TABLE IF NOT EXISTS n8n_broadcast_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT(11) NOT NULL,
    n8n_execution_id VARCHAR(255) NULL,
    log_level ENUM('info', 'warning', 'error', 'debug') DEFAULT 'info',
    message TEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_broadcast_id (broadcast_id),
    INDEX idx_execution_id (n8n_execution_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (broadcast_id) REFERENCES broadcast_history(id) ON DELETE CASCADE
);

-- Crear tabla broadcast_rate_limits
CREATE TABLE IF NOT EXISTS broadcast_rate_limits (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value INT(11) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting_key (setting_key)
);

-- Crear tabla broadcast_rate_tracking
CREATE TABLE IF NOT EXISTS broadcast_rate_tracking (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT(11) NOT NULL,
    batch_number INT(11) NOT NULL,
    contacts_count INT(11) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    INDEX idx_broadcast_id (broadcast_id),
    INDEX idx_batch_number (batch_number),
    INDEX idx_status (status),
    FOREIGN KEY (broadcast_id) REFERENCES broadcast_history(id) ON DELETE CASCADE
);

-- ============================================================================
-- 5. INSERTAR CONFIGURACIONES POR DEFECTO
-- ============================================================================

-- Insertar configuraciones de rate limiting por defecto
INSERT IGNORE INTO broadcast_rate_limits (setting_key, setting_value, description) VALUES
('batch_size', 10, 'Número de contactos por lote'),
('delay_between_batches', 2000, 'Delay entre lotes en milisegundos'),
('max_retries', 3, 'Número máximo de reintentos por mensaje');

-- ============================================================================
-- 6. VERIFICACIÓN FINAL
-- ============================================================================

-- Verificar tablas actualizadas
SELECT '=== VERIFICACIÓN DE ACTUALIZACIÓN ===' as status;

SELECT 'Tablas principales:' as info;
SHOW TABLES LIKE '%broadcast%';
SHOW TABLES LIKE '%n8n%';

SELECT 'Estructura de broadcast_history:' as info;
DESCRIBE broadcast_history;

SELECT 'Estructura de broadcast_details:' as info;
DESCRIBE broadcast_details;

SELECT 'Configuración de n8n:' as info;
SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'n8n%';

SELECT 'Configuración de rate limiting:' as info;
SELECT setting_key, setting_value, description FROM broadcast_rate_limits;

SELECT '=== ACTUALIZACIÓN COMPLETADA EXITOSAMENTE ===' as status; 