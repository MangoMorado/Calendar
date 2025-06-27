-- Tabla para listas de difusión
CREATE TABLE IF NOT EXISTS broadcast_lists (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT(11) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
);

-- Tabla para contactos en listas de difusión
CREATE TABLE IF NOT EXISTS broadcast_list_contacts (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    list_id INT(11) NOT NULL,
    contact_id INT(11) NOT NULL,
    added_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES broadcast_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_list_contact (list_id, contact_id),
    INDEX idx_list_id (list_id),
    INDEX idx_contact_id (contact_id)
);

-- Tabla para historial de difusiones
CREATE TABLE IF NOT EXISTS broadcast_history (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    list_id INT(11) NOT NULL,
    message TEXT,
    image_path VARCHAR(500),
    total_contacts INT(11) NOT NULL DEFAULT 0,
    sent_successfully INT(11) NOT NULL DEFAULT 0,
    sent_failed INT(11) NOT NULL DEFAULT 0,
    user_id INT(11) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    started_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES broadcast_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_list_id (list_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
);

-- Tabla para detalles de envío de difusiones
CREATE TABLE IF NOT EXISTS broadcast_details (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT(11) NOT NULL,
    contact_id INT(11) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (broadcast_id) REFERENCES broadcast_history(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_broadcast_id (broadcast_id),
    INDEX idx_contact_id (contact_id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- Insertar datos de ejemplo para testing (opcional)
-- INSERT INTO broadcast_lists (name, description, user_id) VALUES 
-- ('Clientes VIP', 'Lista de clientes con mayor valor', 1),
-- ('Promociones', 'Lista para envío de ofertas especiales', 1),
-- ('Recordatorios', 'Lista para recordatorios de citas', 1); 