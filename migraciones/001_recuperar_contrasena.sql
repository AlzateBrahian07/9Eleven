-- Issue #13: recuperación de contraseña por correo.
-- Ejecutar UNA vez en la base db_9eleven si ya la tenías importada
-- (phpMyAdmin -> db_9eleven -> pestaña SQL -> pegar y ejecutar).
-- Si importas db_9eleven.sql desde cero, esta tabla ya viene incluida.

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
