CREATE DATABASE IF NOT EXISTS shalom_dl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shalom_dl;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    cargo VARCHAR(80) DEFAULT 'Operador',
    rol ENUM('admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registros_cargueros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    turno ENUM('manana', 'tarde') NOT NULL,
    destino VARCHAR(150) NOT NULL,
    flota VARCHAR(50) NOT NULL,
    hora_entrada TIME DEFAULT NULL,
    hora_salida TIME DEFAULT NULL,
    observaciones VARCHAR(255) DEFAULT NULL,
    estado ENUM('pendiente', 'instalacion', 'finalizado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_fecha_turno (fecha, turno)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mantenimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    flota VARCHAR(50) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    descripcion TEXT,
    costo DECIMAL(10,2) DEFAULT 0.00,
    estado ENUM('programado', 'en_proceso', 'completado', 'cancelado') NOT NULL DEFAULT 'programado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS personal (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    rol ENUM('estiba', 'chofer', 'coordinador') NOT NULL DEFAULT 'estiba',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asistencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    personal_id INT UNSIGNED DEFAULT NULL,
    empleado VARCHAR(120) NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME DEFAULT NULL,
    hora_salida TIME DEFAULT NULL,
    observaciones VARCHAR(255) DEFAULT NULL,
    estado ENUM('presente', 'tardanza', 'ausente', 'no_regreso_almuerzo', 'justificado') NOT NULL DEFAULT 'presente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (personal_id) REFERENCES personal(id) ON DELETE SET NULL,
    INDEX idx_personal_fecha (personal_id, fecha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS programacion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    personal_id INT UNSIGNED NOT NULL,
    tipo ENUM('trabajo', 'descanso', 'domingo', 'especial') NOT NULL DEFAULT 'trabajo',
    observaciones VARCHAR(255) DEFAULT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_fecha_personal (fecha, personal_id),
    FOREIGN KEY (personal_id) REFERENCES personal(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
