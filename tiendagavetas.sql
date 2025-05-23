-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 20, 2025 at 11:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tienda_virtual`
--

-- --------------------------------------------------------

--
-- Table structure for table `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Gaveta Pequeña'),
(2, 'Gaveta Mediana'),
(3, 'Gaveta Grande'),
(4, 'Estanterías Metálicas');

-- --------------------------------------------------------

--
-- Table structure for table `cesta`
--

CREATE TABLE `cesta` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `referencia_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` enum('M','F','Otro') NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `direccion_envio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `apellidos`, `correo`, `fecha_nacimiento`, `genero`, `usuario`, `direccion_envio`) VALUES
(1, 'Admin', 'Administrador', 'admin@example.com', '1990-01-01', 'M', 'admin', 'Calle Principal 123, Ciudad, País'),
(2, 'Guille', 'EG', 'mail@mail.com', '1987-01-07', 'M', 'will', 'C\\ Leñeros 18, 3'),
(3, 'pepe', 'pito', 'maililto@mail.com', '1256-01-01', 'M', 'pepe', 'Ferrocarril 35, 28045');

-- --------------------------------------------------------

--
-- Table structure for table `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `fecha_compra` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalles_compra`
--

CREATE TABLE `detalles_compra` (
  `id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `referencia_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `referencia` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`referencia`, `nombre`, `precio`, `imagen`, `descripcion`, `categoria_id`) VALUES
('G001', 'Gaveta Azul Pequeña', 11.15, 'images/gavetapequeñaazul.jpg', 'Gavetas apilables de uso industrial para el almacenamiento y la ordenación en los espacios más exigentes. Su fabricación en polipropileno las hace resistentes a ácidos, grasas y diferentes compuestos químicos, y a temperaturas entre -10ºC y 80ºC.  ', 1),
('G002', 'Gaveta Gris Pequeña', 11.15, 'images/gavetapequeñagris.jpg', 'Gavetas apilables de uso industrial para el almacenamiento y la ordenación en los espacios más exigentes. Su fabricación en polipropileno las hace resistentes a ácidos, grasas y diferentes compuestos químicos, y a temperaturas entre -10ºC y 80ºC.  ', 1),
('G003', 'Gaveta Verde Pequeña', 11.15, 'images/gavetapequeñaverde.jpg', 'Gavetas apilables de uso industrial para el almacenamiento y la ordenación en los espacios más exigentes. Su fabricación en polipropileno las hace resistentes a ácidos, grasas y diferentes compuestos químicos, y a temperaturas entre -10ºC y 80ºC.  ', 1);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`usuario`, `contrasena`, `rol`) VALUES
('admin', '$2y$10$PA8LAtfaJJiCW5fib6OnjeXnoe6RdFz/69GzM.5ol4o4c9xGGhjRK', 'admin'),
('pepe', '$2y$10$/F2ZdthNgoS0dy1Kn22e4.Nxsm.RD3Gp1B17Vk6APg3ICi9vZAN/a', 'user'),
('will', '$2y$10$CC5jpD6546SHVglV92Yv..4bcvK1e8ut2IB24P9n0syBGm.4eXbh6', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cesta`
--
ALTER TABLE `cesta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `referencia_producto` (`referencia_producto`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `usuario` (`usuario`);

--
-- Indexes for table `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario` (`usuario`);

--
-- Indexes for table `detalles_compra`
--
ALTER TABLE `detalles_compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compra_id` (`compra_id`),
  ADD KEY `referencia_producto` (`referencia_producto`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`referencia`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuario`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cesta`
--
ALTER TABLE `cesta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `detalles_compra`
--
ALTER TABLE `detalles_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cesta`
--
ALTER TABLE `cesta`
  ADD CONSTRAINT `cesta_ibfk_1` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`usuario`),
  ADD CONSTRAINT `cesta_ibfk_2` FOREIGN KEY (`referencia_producto`) REFERENCES `productos` (`referencia`);

--
-- Constraints for table `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`usuario`);

--
-- Constraints for table `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`usuario`);

--
-- Constraints for table `detalles_compra`
--
ALTER TABLE `detalles_compra`
  ADD CONSTRAINT `detalles_compra_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `detalles_compra_ibfk_2` FOREIGN KEY (`referencia_producto`) REFERENCES `productos` (`referencia`);

--
-- Constraints for table `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
