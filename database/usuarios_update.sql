-- ============================================================
--  GastroDigital — Migración: columnas genero e id_pais en usuarios
--  Ejecutar una sola vez sobre la BD existente
-- ============================================================

USE recetas_db;

-- Agregar columna genero (si no existe)
ALTER TABLE `usuarios`
    ADD COLUMN IF NOT EXISTS `genero`   ENUM('masculino','femenino','otro','prefiero_no_decir')
                                        DEFAULT NULL
                                        AFTER `bio`;

-- Agregar FK a paises (si no existe)
ALTER TABLE `usuarios`
    ADD COLUMN IF NOT EXISTS `id_pais`  INT(10) UNSIGNED DEFAULT NULL
                                        AFTER `genero`;

-- Agregar índice para la FK
ALTER TABLE `usuarios`
    ADD INDEX IF NOT EXISTS `idx_usuarios_pais` (`id_pais`);

-- Agregar restricción de FK (ignorar si ya existe)
ALTER TABLE `usuarios`
    ADD CONSTRAINT `fk_usuarios_pais`
        FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;
