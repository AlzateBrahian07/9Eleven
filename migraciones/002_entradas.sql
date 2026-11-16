-- Issue #7 (RF-12): registro de entradas de mercancía.
-- Ejecutar una vez en db_9eleven si ya estaba importada.

CREATE TABLE IF NOT EXISTS `entradas` (
  `ID_Entrada` int(11) NOT NULL AUTO_INCREMENT,
  `fechaEntrada` date NOT NULL,
  `totalEntrada` double NOT NULL DEFAULT 0,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `ID_Administrador` int(11) NOT NULL,
  PRIMARY KEY (`ID_Entrada`),
  KEY `fk_entrada_prov` (`ID_Proveedor`),
  KEY `fk_entrada_admin` (`ID_Administrador`),
  CONSTRAINT `fk_entrada_prov` FOREIGN KEY (`ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_entrada_admin` FOREIGN KEY (`ID_Administrador`) REFERENCES `administrador` (`ID_Administrador`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detalle_entradas` (
  `ID_Detalle` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Entrada` int(11) NOT NULL,
  `ID_Producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precioUnitario` double NOT NULL DEFAULT 0,
  `subtotal` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID_Detalle`),
  KEY `fk_dentrada_entrada` (`ID_Entrada`),
  KEY `fk_dentrada_prod` (`ID_Producto`),
  CONSTRAINT `fk_dentrada_entrada` FOREIGN KEY (`ID_Entrada`) REFERENCES `entradas` (`ID_Entrada`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dentrada_prod` FOREIGN KEY (`ID_Producto`) REFERENCES `productos` (`ID_Producto`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;