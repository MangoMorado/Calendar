-- Script SQL SIMPLE para actualizar la base de datos Calendar con soporte para n8n
-- Este script es SEGURO y NO elimina información existente

-- ============================================================================
-- 1. CREAR/ACTUALIZAR TABLA SETTINGS
-- ============================================================================

-- Crear tabla settings si no existe
CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT NULL,
    setting_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting_key (setting_key),
    INDEX idx_setting_key (setting_key)
);

-- Agregar columnas faltantes si la tabla ya existe
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS setting_description TEXT NULL AFTER setting_value,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER setting_description,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Insertar configuración de n8n (solo si no existe)
INSERT IGNORE INTO settings (setting_key, setting_value, setting_description) VALUES
('n8n_url', 'https://n8n.mangomorado.com', 'URL base de n8n'),
('n8n_api_key', '', 'API Key de n8n (opcional)'),
('n8n_broadcast_webhook_url', 'https://n8n.mangomorado.com/webhook/broadcast', 'URL del webhook de n8n para difusiones');

-- ============================================================================
-- 2. ACTUALIZAR TABLAS EXISTENTES
-- ============================================================================

-- Agregar columnas de n8n a broadcast_history
ALTER TABLE broadcast_history
ADD COLUMN IF NOT EXISTS n8n_workflow_id VARCHAR(255) NULL AFTER status,
ADD COLUMN IF NOT EXISTS n8n_execution_id VARCHAR(255) NULL AFTER n8n_workflow_id,
ADD COLUMN IF NOT EXISTS n8n_metadata JSON NULL AFTER n8n_execution_id;

-- Actualizar ENUM de status en broadcast_history
ALTER TABLE broadcast_history
MODIFY COLUMN status ENUM('queued', 'in_progress', 'completed', 'failed', 'paused') DEFAULT 'queued';

-- Actualizar ENUM de status en broadcast_details
ALTER TABLE broadcast_details
MODIFY COLUMN status ENUM('queued', 'sent', 'failed', 'pending') DEFAULT 'queued';

-- ============================================================================
-- 3. CREAR NUEVAS TABLAS
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
-- 4. INSERTAR CONFIGURACIONES POR DEFECTO
-- ============================================================================

-- Insertar configuraciones de rate limiting por defecto
INSERT IGNORE INTO broadcast_rate_limits (setting_key, setting_value, description) VALUES
('batch_size', 10, 'Número de contactos por lote'),
('delay_between_batches', 2000, 'Delay entre lotes en milisegundos'),
('max_retries', 3, 'Número máximo de reintentos por mensaje');

-- ============================================================================
-- 5. VERIFICACIÓN
-- ============================================================================

SELECT '=== ACTUALIZACIÓN COMPLETADA ===' as status;
SELECT 'Configuración de n8n:' as info;
SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'n8n%'; 