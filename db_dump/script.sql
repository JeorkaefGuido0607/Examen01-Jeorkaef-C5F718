-- CREATE DATABASE IF NOT EXISTS db_salon;

USE db_salon;

CREATE TABLE citas_salon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente VARCHAR(100) NOT NULL,
    telefono_cliente VARCHAR(20) NOT NULL,
    servicio_solicitado VARCHAR(80) NOT NULL,
    estilista_asignado VARCHAR(100) NOT NULL,
    fecha_hora_cita DATETIME NOT NULL,
    precio_servicio DECIMAL(10,2) NOT NULL,
    estado ENUM('Pendiente', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
