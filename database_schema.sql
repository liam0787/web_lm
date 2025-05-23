-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS tienda_virtual;
USE tienda_virtual;

-- Tabla categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla usuarios
CREATE TABLE usuarios (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('user', 'admin') NOT NULL DEFAULT 'user'
);

-- Tabla clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('M', 'F', 'Otro') NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    direccion_envio TEXT,
    FOREIGN KEY (usuario) REFERENCES usuarios(usuario)
);

-- Tabla productos
CREATE TABLE productos (
    referencia VARCHAR(20) NOT NULL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255),
    descripcion TEXT,
    categoria_id INT NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla cesta
CREATE TABLE cesta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    referencia_producto VARCHAR(20) NOT NULL,
    cantidad INT NOT NULL,
    FOREIGN KEY (usuario) REFERENCES usuarios(usuario),
    FOREIGN KEY (referencia_producto) REFERENCES productos(referencia)
);

-- Inserción de categorías iniciales
INSERT INTO categorias (nombre) VALUES
('Electrónica'),
('Accesorios'),
('Fotografía'),
('Hogar'),
('Gaming');

-- Inserción de 10 productos de ejemplo con categoría_id
INSERT INTO productos (referencia, nombre, precio, imagen, descripcion, categoria_id) VALUES
('REF001', 'Smartphone X', 599.99, 'images/smartphone_x.jpg', 'Smartphone de última generación con pantalla AMOLED y 128GB de almacenamiento.', (SELECT id FROM categorias WHERE nombre = 'Electrónica')),
('REF002', 'Laptop Pro', 1299.99, 'images/laptop_pro.jpg', 'Laptop potente con procesador i7 y 16GB de RAM.', (SELECT id FROM categorias WHERE nombre = 'Electrónica')),
('REF003', 'Auriculares Bluetooth', 89.99, 'images/auriculares_bluetooth.jpg', 'Auriculares inalámbricos con cancelación de ruido.', (SELECT id FROM categorias WHERE nombre = 'Accesorios')),
('REF004', 'Tablet 10"', 249.99, 'images/tablet_10.jpg', 'Tablet ligera con pantalla de 10 pulgadas y 64GB.', (SELECT id FROM categorias WHERE nombre = 'Electrónica')),
('REF005', 'Smartwatch', 199.99, 'images/smartwatch.jpg', 'Reloj inteligente con monitoreo de salud.', (SELECT id FROM categorias WHERE nombre = 'Accesorios')),
('REF006', 'Cámara Digital', 349.99, 'images/camara_digital.jpg', 'Cámara de 24MP con lente intercambiable.', (SELECT id FROM categorias WHERE nombre = 'Fotografía')),
('REF007', 'Teclado Mecánico', 79.99, 'images/teclado_mecanico.jpg', 'Teclado con switches Cherry MX y retroiluminación RGB.', (SELECT id FROM categorias WHERE nombre = 'Accesorios')),
('REF008', 'Monitor 27"', 299.99, 'images/monitor_27.jpg', 'Monitor 4K con alta resolución y tasa de refresco de 144Hz.', (SELECT id FROM categorias WHERE nombre = 'Electrónica')),
('REF009', 'Altavoz Inteligente', 129.99, 'images/altavoz_inteligente.jpg', 'Altavoz con asistente virtual integrado.', (SELECT id FROM categorias WHERE nombre = 'Hogar')),
('REF010', 'Consola de Videojuegos', 499.99, 'images/consola_videojuegos.jpg', 'Consola de nueva generación con gráficos 4K.', (SELECT id FROM categorias WHERE nombre = 'Gaming'));

-- Inserción de usuario admin
INSERT INTO usuarios (usuario, contrasena, rol) VALUES
('admin', '$2y$10$z2X8Y8Z8Y8Z8Y8Z8Y8Z8Yu8Y8Z8Y8Z8Y8Z8Y8Z8Y8Z8Y8Z8Y8Z8', 'admin');

-- Opcional: Datos para el usuario admin en clientes
INSERT INTO clientes (nombre, apellidos, correo, fecha_nacimiento, genero, usuario, direccion_envio) VALUES
('Admin', 'Administrador', 'admin@example.com', '1990-01-01', 'M', 'admin', 'Calle Principal 123, Ciudad, País');