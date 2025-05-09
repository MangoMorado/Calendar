CREATE TABLE mundi_usuarios (
    telefono VARCHAR(255) PRIMARY KEY,
    nombre VARCHAR(255),
    documento VARCHAR(255),
    direccion TEXT,
    email VARCHAR(255),
    fecha_registro TIMESTAMP,
    ultima_actividad TIMESTAMP,
    mascotas JSONB, -- Usando JSONB para PostgreSQL
    notas TEXT,
    estado VARCHAR(50)
);