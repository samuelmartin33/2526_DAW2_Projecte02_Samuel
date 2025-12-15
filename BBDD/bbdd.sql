-- ==========================================
--   BASE DE DATOS: restaurante_db (OPTIMIZADA)
--   Autor: Marc Guillem, Samuel Martínez
--   Fecha: Diciembre 2025
--   Descripción: Versión normalizada sin redundancia
-- ==========================================

DROP DATABASE IF EXISTS restaurante_db;
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE restaurante_db;

-- ==========================================
--   TABLA: users
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol INT DEFAULT 1,  -- 1=camarero, 2=admin, 3=cliente, 4=mantenimiento, 5=jefe de camareros
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_baja DATETIME NULL
) ENGINE=InnoDB;

-- ==========================================
--   TABLA: salas
-- ==========================================
CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ==========================================
--   TABLA: mesas
-- ==========================================
CREATE TABLE IF NOT EXISTS mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sala INT NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    sillas INT NOT NULL,
    estado INT DEFAULT 1,  -- 1=libre, 2=ocupada, 3=reservada
    asignado_por INT NULL,
    FOREIGN KEY (id_sala) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================
--   TABLA: reservas
-- ==========================================
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_reserva INT NOT NULL,
    id_mesa INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    num_comensales INT,
    estado INT DEFAULT 1,  -- 1=pendiente, 2=confirmada, 3=cancelada, 4=finalizada
    FOREIGN KEY (id_usuario_reserva) REFERENCES users(id),
    FOREIGN KEY (id_mesa) REFERENCES mesas(id),
    CONSTRAINT check_duracion CHECK (TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) <= 7)
) ENGINE=InnoDB;

-- ==========================================
--   TABLA: ocupaciones
-- ==========================================
CREATE TABLE IF NOT EXISTS ocupaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_camarero INT NOT NULL,
    id_mesa INT NOT NULL,
    inicio_ocupacion DATETIME NOT NULL,
    final_ocupacion DATETIME NULL,
    num_comensales INT,
    id_reserva INT NULL,
    FOREIGN KEY (id_camarero) REFERENCES users(id),
    FOREIGN KEY (id_mesa) REFERENCES mesas(id),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Añadimos columnas para la posición en porcentaje (para que sea responsive)
ALTER TABLE mesas ADD COLUMN pos_x FLOAT DEFAULT 50;
ALTER TABLE mesas ADD COLUMN pos_y FLOAT DEFAULT 50;


ALTER TABLE salas ADD COLUMN imagen VARCHAR(255) NULL;

-- ==========================================
--   INSERTS: USERS
-- ==========================================
INSERT INTO users (username, nombre, apellido, email, password_hash, rol)
VALUES
-- 5 Camareros (Rol 1, IDs 1-5)
('laura.gomez', 'Laura', 'Gómez', 'laura.gomez@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 1),
('carlos.perez', 'Carlos', 'Pérez', 'carlos.perez@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 1),
('sofia.martin', 'Sofía', 'Martín', 'sofia.martin@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 1),
('daniel.ruiz', 'Daniel', 'Ruiz', 'daniel.ruiz@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 1),
('maria.hernandez', 'María', 'Hernández', 'maria.hernandez@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 1),

-- 1 Administrador (Rol 2, ID 6)
('admin.root', 'Admin', 'Root', 'admin@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 2),

-- 4 Clientes (Rol 3, IDs 7-10)
('cliente.juan', 'Juan', 'Pascual', 'juan.pascual@cliente.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 3),
('cliente.marta', 'Marta', 'Casado', 'marta.casado@cliente.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 3),
('cliente.luis', 'Luis', 'Fernandez', 'luis.fernandez@cliente.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 3),
('cliente.eva', 'Eva', 'Soria', 'eva.soria@cliente.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 3),

-- 7 Mantenimiento (Rol 4, IDs 11-17)
('mantenimiento.luis', 'Luis', 'García', 'luis.garcia@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.ana', 'Ana', 'López', 'ana.lopez@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.pepe', 'Pepe', 'Ruiz', 'pepe.ruiz@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.sara', 'Sara', 'Conde', 'sara.conde@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.juan', 'Juan', 'Sanz', 'juan.sanz@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.eva', 'Eva', 'Gil', 'eva.gil@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),
('mantenimiento.tomas', 'Tomas', 'Mora', 'tomas.mora@mantenimiento.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 4),

-- 3 Jefes de Camareros (Rol 5, IDs 18-20)
('jefe.carlos', 'Carlos', 'Sánchez', 'carlos.sanchez@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 5),
('jefe.maria', 'María', 'Rodríguez', 'maria.rodriguez@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 5),
('jefe.antonio', 'Antonio', 'Diaz', 'antonio.diaz@restaurante.com', '$2y$10$VPM7.4cja7LDxoYO6YaOLuZgW.moRul/o5VnNuYUIupyjdko/sB5m', 5);


-- ==========================================
--   INSERTS: SALAS
-- ==========================================
INSERT INTO salas (nombre)
VALUES
('Terraza 1'),
('Terraza 2'),
('Terraza 3'),
('Comedor 1'),
('Comedor 2'),
('Privada 1'),
('Privada 2'),
('Privada 3'),
('Privada 4');

-- ==========================================
--   INSERTS: MESAS
-- ==========================================
INSERT INTO mesas (id_sala, nombre, sillas) VALUES
-- Terraza 1 (ID Sala 1)
(1, 'T1-1', 4), (1, 'T1-2', 4), (1, 'T1-3', 6), (1, 'T1-4', 2),
-- Terraza 2 (ID Sala 2)
(2, 'T2-1', 4), (2, 'T2-2', 6), (2, 'T2-3', 4), (2, 'T2-4', 2),
-- Terraza 3 (ID Sala 3)
(3, 'T3-1', 4), (3, 'T3-2', 4), (3, 'T3-3', 6), (3, 'T3-4', 4),
-- Comedor 1 (ID Sala 4)
(4, 'C1-1', 6), (4, 'C1-2', 6), (4, 'C1-3', 4), (4, 'C1-4', 4),
-- Comedor 2 (ID Sala 5)
(5, 'C2-1', 4), (5, 'C2-2', 4), (5, 'C2-3', 6), (5, 'C2-4', 2),
-- Privadas (Salas 6, 7, 8, 9)
(6, 'P1', 10),
(7, 'P2', 15),
(8, 'P3', 20),
(9, 'P4', 30);

-- ==========================================
--   INSERTS: RESERVAS
--   Nota: IDs de clientes actualizados a 7-10
-- ==========================================
INSERT INTO reservas (id_usuario_reserva, id_mesa, fecha_inicio, fecha_fin, num_comensales, estado)
VALUES
(7, 1, '2025-11-10 13:00:00', '2025-11-10 14:00:00', 4, 2),
(8, 5, '2025-11-10 14:30:00', '2025-11-10 16:30:00', 2, 1),
(9, 13, '2025-11-11 15:00:00', '2025-11-11 16:00:00', 3, 2),
(10, 22, '2025-11-12 21:00:00', '2025-11-12 23:00:00', 12, 1);

-- ==========================================
--   INSERTS: OCUPACIONES
--   Nota: IDs de camareros actualizados a 1-5
-- ==========================================
INSERT INTO ocupaciones (id_camarero, id_mesa, inicio_ocupacion, final_ocupacion, num_comensales, id_reserva)
VALUES
-- Año 2023
(1, 1, '2023-01-10 13:05:00', '2023-01-10 14:00:00', 4, NULL),
(2, 5, '2023-01-11 14:35:00', '2023-01-11 15:30:00', 2, NULL),
(3, 13, '2023-01-12 15:10:00', '2023-01-12 16:45:00', 3, NULL),
(4, 9, '2023-02-15 17:00:00', '2023-02-15 18:20:00', 4, NULL),
(5, 22, '2023-02-20 19:00:00', '2023-02-20 21:30:00', 10, NULL),
(1, 17, '2023-03-01 20:15:00', '2023-03-01 22:00:00', 6, NULL),
(2, 21, '2023-03-05 12:30:00', '2023-03-05 14:00:00', 8, NULL),
(3, 23, '2023-03-10 13:00:00', '2023-03-10 15:30:00', 8, NULL),
(4, 24, '2023-04-01 18:00:00', '2023-04-01 20:00:00', 12, NULL),
(5, 2, '2023-04-02 19:30:00', '2023-04-02 21:00:00', 4, NULL),
(1, 6, '2023-05-10 20:00:00', '2023-05-10 22:15:00', 6, NULL),
(2, 14, '2023-05-15 13:30:00', '2023-05-15 15:00:00', 5, NULL),
(3, 18, '2023-06-10 14:00:00', '2023-06-10 15:30:00', 3, NULL),
(4, 3, '2023-06-12 20:30:00', '2023-06-12 22:00:00', 5, NULL),
(5, 10, '2023-07-01 21:00:00', '2023-07-01 22:30:00', 2, NULL),
(1, 15, '2023-07-04 14:15:00', '2023-07-04 15:45:00', 4, NULL),
(2, 19, '2023-08-10 13:00:00', '2023-08-10 14:30:00', 6, NULL),
(3, 4, '2023-08-12 19:00:00', '2023-08-12 20:30:00', 2, NULL),
(4, 7, '2023-09-01 20:00:00', '2023-09-01 21:45:00', 4, NULL),
(5, 11, '2023-09-05 13:30:00', '2023-09-05 15:00:00', 5, NULL),
(1, 16, '2023-10-10 14:00:00', '2023-10-10 15:15:00', 4, NULL),
(2, 20, '2023-10-12 20:30:00', '2023-10-12 22:00:00', 2, NULL),
(3, 21, '2023-11-01 13:00:00', '2023-11-01 15:00:00', 9, NULL),
(4, 22, '2023-11-15 14:30:00', '2023-11-15 16:30:00', 14, NULL),
(5, 23, '2023-12-24 21:00:00', '2023-12-24 23:30:00', 18, NULL),
-- Año 2024
(1, 1, '2024-01-10 13:05:00', '2024-01-10 14:00:00', 2, NULL),
(2, 5, '2024-01-11 14:35:00', '2024-01-11 15:30:00', 4, NULL),
(3, 13, '2024-01-12 15:10:00', '2024-01-12 16:45:00', 6, NULL),
(4, 9, '2024-02-15 17:00:00', '2024-02-15 18:20:00', 3, NULL),
(5, 22, '2024-02-20 19:00:00', '2024-02-20 21:30:00', 12, NULL),
(1, 17, '2024-03-01 20:15:00', '2024-03-01 22:00:00', 5, NULL),
(2, 21, '2024-03-05 12:30:00', '2024-03-05 14:00:00', 7, NULL),
(3, 23, '2024-03-10 13:00:00', '2024-03-10 15:30:00', 20, NULL),
(4, 24, '2024-04-01 18:00:00', '2024-04-01 20:00:00', 25, NULL),
(5, 2, '2024-04-02 19:30:00', '2024-04-02 21:00:00', 4, NULL),
(1, 6, '2024-05-10 20:00:00', '2024-05-10 22:15:00', 6, NULL),
(2, 14, '2024-05-15 13:30:00', '2024-05-15 15:00:00', 5, NULL),
(3, 18, '2024-06-10 14:00:00', '2024-06-10 15:30:00', 4, NULL),
(4, 3, '2024-06-12 20:30:00', '2024-06-12 22:00:00', 6, NULL),
(5, 10, '2024-07-01 21:00:00', '2024-07-01 22:30:00', 4, NULL),
(1, 15, '2024-07-04 14:15:00', '2024-07-04 15:45:00', 4, NULL),
(2, 19, '2024-08-10 13:00:00', '2024-08-10 14:30:00', 6, NULL),
(3, 4, '2024-08-12 19:00:00', '2024-08-12 20:30:00', 2, NULL),
(4, 7, '2024-09-01 20:00:00', '2024-09-01 21:45:00', 4, NULL),
(5, 11, '2024-09-05 13:30:00', '2024-09-05 15:00:00', 5, NULL),
(1, 16, '2024-10-10 14:00:00', '2024-10-10 15:15:00', 4, NULL),
(2, 20, '2024-10-12 20:30:00', '2024-10-12 22:00:00', 2, NULL),
(3, 21, '2024-11-01 13:00:00', '2024-11-01 15:00:00', 10, NULL),
(4, 22, '2024-11-15 14:30:00', '2024-11-15 16:30:00', 14, NULL),
(5, 23, '2024-11-07 21:00:00', '2024-11-07 23:30:00', 18, NULL),
(1, 24, '2024-11-08 20:00:00', '2024-11-08 22:30:00', 30, NULL),
(2, 2, '2024-11-08 19:30:00', '2024-11-08 21:00:00', 4, NULL),
(3, 8, '2024-11-08 20:15:00', '2024-11-08 21:45:00', 2, NULL),
(4, 12, '2024-11-09 13:00:00', '2024-11-09 14:30:00', 4, NULL),
(5, 14, '2024-11-09 14:00:00', '2024-11-09 15:30:00', 6, NULL),
(1, 17, '2024-11-09 20:00:00', '2024-11-09 22:00:00', 3, NULL),
(2, 1, '2024-11-10 13:30:00', '2024-11-10 15:00:00', 4, NULL),
(3, 5, '2024-11-10 14:00:00', '2024-11-10 15:15:00', 2, NULL),
(4, 13, '2024-11-10 20:00:00', '2024-11-10 21:30:00', 5, NULL),
(5, 9, '2024-11-10 21:00:00', '2024-11-10 22:00:00', 3, NULL),
(1, 3, '2025-01-15 13:00:00', '2025-01-15 14:45:00', 5, NULL),
(2, 7, '2025-02-18 14:00:00', '2025-02-18 15:30:00', 3, NULL),
(3, 15, '2025-03-20 20:00:00', '2025-03-20 22:00:00', 4, NULL),
(4, 19, '2025-04-22 13:30:00', '2025-04-22 15:00:00', 6, NULL),
(5, 4, '2025-05-25 14:30:00', '2025-05-25 16:00:00', 2, NULL),
(1, 11, '2025-06-30 20:30:00', '2025-06-30 22:15:00', 5, NULL),
(2, 21, '2025-07-10 19:00:00', '2025-07-10 21:00:00', 8, NULL),
(3, 23, '2025-08-15 13:00:00', '2025-08-15 15:30:00', 15, NULL),
(4, 24, '2025-09-20 20:00:00', '2025-09-20 22:30:00', 20, NULL),
(5, 22, '2025-10-28 14:00:00', '2025-10-28 16:00:00', 12, NULL);
