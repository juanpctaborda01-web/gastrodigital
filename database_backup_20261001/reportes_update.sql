-- ============================================================
--  GastroDigital — Agregar tabla de reportes/denuncias
--  Ejecutar en phpMyAdmin sobre la BD recetas_db
-- ============================================================

USE recetas_db;

CREATE TABLE IF NOT EXISTS reportes (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    tipo            ENUM('receta','comentario','perfil','valoracion')
                                    NOT NULL,
    referencia_id   INT UNSIGNED    NOT NULL,   -- ID del elemento reportado
    reportado_por   INT UNSIGNED    NOT NULL,   -- usuario que reporta
    motivo          VARCHAR(120)    NOT NULL,
    descripcion     TEXT            DEFAULT NULL,
    estado          ENUM('pendiente','revisado','eliminado','desestimado')
                                    NOT NULL DEFAULT 'pendiente',
    revisado_por    INT UNSIGNED    DEFAULT NULL,  -- admin que lo revisó
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (reportado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (revisado_por)  REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tipo_estado (tipo, estado),
    INDEX idx_referencia  (tipo, referencia_id)
) ENGINE=InnoDB;
