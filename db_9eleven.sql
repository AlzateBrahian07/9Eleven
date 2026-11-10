-- phpMyAdmin SQL Dump (versión corregida)
-- Proyecto: Sistema de Gestión de Inventario - Minimarket 9Eleven
-- Cambios respecto a la versión original:
--   1. Se agregó `stockMinimo` a `productos` (necesario para las alertas de stock)
--   2. Se separó `ventas` en `ventas` (encabezado) + `detalle_ventas` (productos por venta)
--   3. Se eliminó la tabla `reportes` (ahora se genera con consultas SQL, no se almacena)
--   4. Se eliminó la tabla `alerta_stock` (ahora se genera comparando stockActual vs stockMinimo)
--
-- Servidor: 127.0.0.1
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_9eleven`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `ID_Administrador` int(11) NOT NULL,
  `email` varchar(120) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`ID_Administrador`, `email`, `contrasena`) VALUES
(1, 'admin@9eleven.com', 'neineleven9e@dmin');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `ID_Categoria` int(11) NOT NULL,
  `nombreCategoria` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`ID_Categoria`, `nombreCategoria`) VALUES
(1, 'Bebidas'),
(2, 'Snacks'),
(3, 'Lácteos'),
(4, 'Aseo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `ID_Producto` int(11) NOT NULL,
  `codigoProducto` varchar(50) NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `informacionGeneral` text DEFAULT NULL,
  `precioCompra` double NOT NULL DEFAULT 0,
  `precioVenta` double NOT NULL DEFAULT 0,
  `iva` double NOT NULL DEFAULT 0,
  `stockActual` int(11) NOT NULL DEFAULT 0,
  `stockMinimo` int(11) NOT NULL DEFAULT 0,
  `ID_Categoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`ID_Producto`, `codigoProducto`, `nombreProducto`, `informacionGeneral`, `precioCompra`, `precioVenta`, `iva`, `stockActual`, `stockMinimo`, `ID_Categoria`) VALUES
(1, 'P001', 'Agua 600ml', NULL, 800, 1200, 0, 50, 20, 1),
(2, 'P002', 'Papas fritas', NULL, 1200, 2000, 19, 30, 15, 2),
(3, 'P003', 'Leche entera', NULL, 2500, 3200, 0, 20, 30, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `ID_Proveedor` int(11) NOT NULL,
  `nombreEmpresa` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `ID_Categoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`ID_Proveedor`, `nombreEmpresa`, `telefono`, `contacto`, `ID_Categoria`) VALUES
(1, 'Distribuidora Norte', '3001234567', 'Carlos Pérez', 1),
(2, 'Snacks S.A.S', '3117654321', 'Laura Gómez', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas` (encabezado)
--

CREATE TABLE `ventas` (
  `ID_Venta` int(11) NOT NULL,
  `fechaVenta` date NOT NULL,
  `totalVenta` double NOT NULL DEFAULT 0,
  `ID_Administrador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`ID_Venta`, `fechaVenta`, `totalVenta`, `ID_Administrador`) VALUES
(1, '2026-06-16', 4400, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `ID_Detalle` int(11) NOT NULL,
  `ID_Venta` int(11) NOT NULL,
  `ID_Producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precioUnitario` double NOT NULL DEFAULT 0,
  `subtotal` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`ID_Detalle`, `ID_Venta`, `ID_Producto`, `cantidad`, `precioUnitario`, `subtotal`) VALUES
(1, 1, 1, 2, 1200, 2400),
(2, 1, 2, 1, 2000, 2000);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`ID_Administrador`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`ID_Categoria`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`ID_Producto`),
  ADD UNIQUE KEY `codigoProducto` (`codigoProducto`),
  ADD KEY `fk_prod_cat` (`ID_Categoria`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`ID_Proveedor`),
  ADD KEY `fk_prov_cat` (`ID_Categoria`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`ID_Venta`),
  ADD KEY `fk_venta_admin` (`ID_Administrador`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`ID_Detalle`),
  ADD KEY `fk_detalle_venta` (`ID_Venta`),
  ADD KEY `fk_detalle_prod` (`ID_Producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

ALTER TABLE `administrador`
  MODIFY `ID_Administrador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categorias`
  MODIFY `ID_Categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `productos`
  MODIFY `ID_Producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `proveedores`
  MODIFY `ID_Proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `ventas`
  MODIFY `ID_Venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `detalle_ventas`
  MODIFY `ID_Detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`ID_Categoria`) REFERENCES `categorias` (`ID_Categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD CONSTRAINT `fk_prov_cat` FOREIGN KEY (`ID_Categoria`) REFERENCES `categorias` (`ID_Categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_venta_admin` FOREIGN KEY (`ID_Administrador`) REFERENCES `administrador` (`ID_Administrador`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`ID_Venta`) REFERENCES `ventas` (`ID_Venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_prod` FOREIGN KEY (`ID_Producto`) REFERENCES `productos` (`ID_Producto`) ON UPDATE CASCADE;

--
-- Estructura de tabla para la tabla `recuperacion_contrasena` (issue #13)
--

CREATE TABLE IF NOT EXISTS `recuperacion_contrasena` (
  `ID_Recuperacion` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Administrador` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID_Recuperacion`),
  UNIQUE KEY `uk_recup_token` (`token_hash`),
  KEY `idx_recup_admin` (`ID_Administrador`),
  CONSTRAINT `fk_recup_admin` FOREIGN KEY (`ID_Administrador`) REFERENCES `administrador` (`ID_Administrador`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
