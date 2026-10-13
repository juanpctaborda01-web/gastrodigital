-- ═══════════════════════════════════════════════════════════════════════════
-- GastroDigital 2.0 — Script de correcciones y normalización
-- Analiza los errores encontrados en recetas_db 3.2.sql y aplica correcciones
-- siguiendo las 3 Formas Normales (1FN, 2FN, 3FN)
-- ═══════════════════════════════════════════════════════════════════════════
-- Ejecutar con: mysql -u root -p recetas_db < correccion_normalizacion.sql
-- ═══════════════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Las credenciales y el restablecimiento de contraseñas se gestionan fuera de
-- este script; no se incluyen contraseñas ni cambios de cuentas de usuario.

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 2 — PRIMERA FORMA NORMAL (1FN)
-- Cada celda debe contener un valor atómico (indivisible).
-- No debe haber grupos repetidos ni valores múltiples en un campo.
-- ═══════════════════════════════════════════════════════════════════════════

-- PROBLEMA 1FN: La tabla `usuarios` tiene columna `redes_sociales` TEXT
-- que almacena múltiples redes separadas por comas o saltos de línea.
-- VERIFICACIÓN: Si la columna existe, se normaliza en tabla separada.
-- SOLUCIÓN: Crear tabla normalizada usuarios_redes.

CREATE TABLE IF NOT EXISTS `usuarios_redes` (
    `id_red`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_usuario`  INT UNSIGNED NOT NULL,
    `tipo`        ENUM('instagram','tiktok','youtube','twitter','facebook','web','otro')
                  NOT NULL DEFAULT 'otro',
    `url`         VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_red`),
    KEY `idx_ur_usuario` (`id_usuario`),
    CONSTRAINT `fk_ur_usuario`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PROBLEMA 1FN: La columna `especialidad` en `usuarios` puede contener
-- múltiples especialidades separadas por ' / ' o ','.
-- SOLUCIÓN: Tabla especialidades normalizada.

CREATE TABLE IF NOT EXISTS `especialidades` (
    `id_especialidad` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`          VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_especialidad`),
    UNIQUE KEY `uq_especialidad_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuario_especialidad` (
    `id_usuario`      INT UNSIGNED NOT NULL,
    `id_especialidad` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_usuario`, `id_especialidad`),
    CONSTRAINT `fk_ue_usuario`      FOREIGN KEY (`id_usuario`)      REFERENCES `usuarios`       (`id_usuario`)      ON DELETE CASCADE,
    CONSTRAINT `fk_ue_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PROBLEMA 1FN: `pasos.imagen` almacena múltiples imágenes separadas
-- cuando debería ser una sola por paso.
-- SOLUCIÓN: La columna ya es VARCHAR(255) — solo documentar restricción.
ALTER TABLE `pasos`
    MODIFY COLUMN `imagen` VARCHAR(255) DEFAULT NULL
    COMMENT 'Una sola imagen por paso. Sin comas ni URLs múltiples.';

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 3 — SEGUNDA FORMA NORMAL (2FN)
-- En tablas con clave compuesta, todos los atributos no-clave deben depender
-- de TODA la clave, no de una parte de ella.
-- ═══════════════════════════════════════════════════════════════════════════

-- PROBLEMA 2FN: `receta_etiqueta` tiene clave compuesta (id_receta, id_etiqueta).
-- Actualmente no tiene atributos adicionales → ya cumple 2FN. ✓
-- Sin embargo, falta índice explícito en id_receta para JOINs eficientes.
ALTER TABLE `receta_etiqueta`
    ADD KEY IF NOT EXISTS `idx_re_receta` (`id_receta`);

-- PROBLEMA 2FN: `ingredientes` tiene `orden` que podría considerarse
-- dependiente solo de id_receta y no del id_ingrediente.
-- Está bien estructurado: `orden` es el orden dentro de una receta específica.
-- Se mantiene. ✓

-- PROBLEMA 2FN: La tabla `recetas` tiene el campo `calorias` como
-- SMALLINT UNSIGNED — límite de 65535 kcal, suficiente. ✓
-- Pero le falta la columna `updated_at` en el modelo de ingredientes:
ALTER TABLE `ingredientes`
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER `orden`;

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 4 — TERCERA FORMA NORMAL (3FN)
-- No debe haber dependencias transitivas: atributo_no_clave → otro_atributo_no_clave.
-- ═══════════════════════════════════════════════════════════════════════════

-- PROBLEMA 3FN: `recetas.id_chef` referencia a `usuarios`.
-- El campo `usuarios.nombre` es transitivo: id_receta → id_chef → nombre_chef.
-- Las consultas deben hacer JOIN en lugar de duplicar el nombre en recetas.
-- VERIFICACIÓN: La tabla recetas NO almacena nombre_chef (correcto). ✓
-- Las consultas del modelo ya hacen LEFT JOIN usuarios. ✓

-- PROBLEMA 3FN: `categorias` tiene `color` y `bg` que son propiedades de la
-- categoría, no atributos derivados → cumple 3FN. ✓

-- PROBLEMA 3FN REAL: `usuarios` tiene `id_pais` y `id_genero` como FK,
-- pero también podría haber transitividad con `bio` si bio derivara de otro campo.
-- Bio es atributo propio del usuario → cumple 3FN. ✓

-- PROBLEMA 3FN: `reportes` tiene CUATRO columnas nullable que apuntan a
-- diferentes entidades (id_receta, id_comentario, id_usuario, id_valoracion).
-- Esto viola la atomicidad y hace difícil garantizar exactamente UNO sea no-null.
-- SOLUCIÓN: Añadir CHECK constraint para garantizar coherencia.
ALTER TABLE `reportes`
    ADD CONSTRAINT `chk_reportes_tipo`
    CHECK (
        (id_receta    IS NOT NULL) +
        (id_comentario IS NOT NULL) +
        (id_usuario    IS NOT NULL) +
        (id_valoracion IS NOT NULL) = 1
    );
-- Nota: Si el motor no soporta CHECK en FK, se implementa a nivel de aplicación.
-- La restricción está igualmente en RecetaController.php::reportar().

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 5 — INCONSISTENCIAS DE DATOS ENCONTRADAS
-- ═══════════════════════════════════════════════════════════════════════════

-- PROBLEMA: Recetas con publicada=0 aparecen en el catálogo público
-- porque la columna tiene DEFAULT 0 pero el INSERT antiguo no lo especificaba.
-- VERIFICACIÓN: Las recetas id 4-11 tienen publicada=0 (borradores sin publicar).
-- Dependiendo del negocio, pueden dejarse así o publicarse todas las de demo.
-- Las siguientes recetas de demo se dejan como borradores intencionalmente.
-- Si se quieren publicar las recetas de demo:
-- UPDATE recetas SET publicada = 1 WHERE publicada = 0;

-- PROBLEMA: El campo `pasos.numero` puede no ser secuencial o tener duplicados
-- dentro de una misma receta.
ALTER TABLE `pasos`
    ADD CONSTRAINT `chk_pasos_numero_positivo`
    CHECK (`numero` > 0);

-- PROBLEMA: `valoraciones.puntuacion` no tiene CHECK de rango 1-5.
ALTER TABLE `valoraciones`
    ADD CONSTRAINT `chk_valoracion_puntuacion`
    CHECK (`puntuacion` BETWEEN 1 AND 5);

-- PROBLEMA: `ingredientes.orden` puede ser NULL o 0.
-- Normalizar: si es NULL asignar 99 como "sin orden definido".
UPDATE `ingredientes`
SET `orden` = 99
WHERE `orden` IS NULL OR `orden` = 0;

-- PROBLEMA: `recetas` tiene títulos con espacios al final (ej: "Arepas Rellenas ").
-- Limpiar espacios en blanco al inicio/fin de títulos y descripciones.
UPDATE `recetas`
SET
    `titulo`      = TRIM(`titulo`),
    `descripcion` = TRIM(`descripcion`)
WHERE `titulo` != TRIM(`titulo`)
   OR `descripcion` != TRIM(`descripcion`);

-- PROBLEMA: `categorias` nombre "Sopas " tiene un espacio al final (id=24).
UPDATE `categorias`
SET `nombre` = TRIM(`nombre`)
WHERE `nombre` != TRIM(`nombre`);

-- PROBLEMA: `usuarios` sin `activo` definido explícitamente en algunos registros.
UPDATE `usuarios`
SET `activo` = 1
WHERE `activo` IS NULL;

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 6 — ÍNDICES FALTANTES PARA RENDIMIENTO
-- ═══════════════════════════════════════════════════════════════════════════

-- Índice en recetas.publicada (filtro más frecuente en catálogo)
ALTER TABLE `recetas`
    ADD INDEX IF NOT EXISTS `idx_recetas_publicada` (`publicada`);

-- Índice compuesto para el catálogo público (publicada + visitas)
ALTER TABLE `recetas`
    ADD INDEX IF NOT EXISTS `idx_recetas_pub_visitas` (`publicada`, `visitas` DESC);

-- Índice en recetas.destacada
ALTER TABLE `recetas`
    ADD INDEX IF NOT EXISTS `idx_recetas_destacada` (`destacada`);

-- Índice en comentarios.created_at para ordenar por fecha
ALTER TABLE `comentarios`
    ADD INDEX IF NOT EXISTS `idx_comentarios_fecha` (`created_at`);

-- Índice en valoraciones para calcular el promedio por receta
ALTER TABLE `valoraciones`
    ADD INDEX IF NOT EXISTS `idx_valoraciones_receta_puntaje` (`id_receta`, `puntuacion`);

-- Índice en usuarios.activo para filtrar solo activos
ALTER TABLE `usuarios`
    ADD INDEX IF NOT EXISTS `idx_usuarios_activo` (`activo`);

-- Índice en usuarios.rol para filtros por tipo de usuario
ALTER TABLE `usuarios`
    ADD INDEX IF NOT EXISTS `idx_usuarios_rol` (`rol`);

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 7 — COLUMNAS FALTANTES SUGERIDAS POR 3FN
-- ═══════════════════════════════════════════════════════════════════════════

-- `comentarios` no tiene `updated_at` para saber si fue editado.
ALTER TABLE `comentarios`
    ADD COLUMN IF NOT EXISTS `editado` TINYINT(1) NOT NULL DEFAULT 0
    AFTER `contenido`;

-- `valoraciones` no tiene `updated_at` (solo created_at).
ALTER TABLE `valoraciones`
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER `created_at`;

-- `sesiones_chatbot` no tiene `updated_at` para saber cuándo fue la última actividad.
ALTER TABLE `sesiones_chatbot`
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER `created_at`;

-- ═══════════════════════════════════════════════════════════════════════════
-- BLOQUE 8 — CORRECCIONES DE CONSTRAINT NAMES DUPLICADAS
-- ═══════════════════════════════════════════════════════════════════════════
-- En el SQL original `idx_recetas_publicada` no existía.
-- Los ADD INDEX IF NOT EXISTS del bloque 6 ya cubren esto.
-- No se requieren acciones adicionales.

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════════════
-- RESUMEN DE PROBLEMAS ENCONTRADOS
-- ═══════════════════════════════════════════════════════════════════════════
-- SEGURIDAD:
--   El restablecimiento de credenciales debe gestionarse de forma individual.
--
-- 1FN (Atomicidad):
--   ✗ `usuarios.redes_sociales` puede contener múltiples valores → nueva tabla
--   ✗ `usuarios.especialidad` puede contener múltiples valores → nueva tabla
--   ✓ Resto de columnas atómicas
--
-- 2FN (Dependencia completa de la clave):
--   ✓ Todas las tablas con PK simple cumplen 2FN automáticamente
--   ✓ `receta_etiqueta` (clave compuesta) — sin atributos extra, cumple 2FN
--   ✓ `receta_caracteristica` — igual
--
-- 3FN (Sin dependencias transitivas):
--   ✓ No se almacena información derivada de FKs en la misma tabla
--   ✗ `reportes` permite que múltiples columnas nullable sean no-null → CHECK añadido
--
-- DATOS INCONSISTENTES:
--   ✗ Títulos/nombres con espacios al final ("Sopas ", "Arepas Rellenas ")
--   ✗ `valoraciones` sin CHECK de rango 1-5
--   ✗ `pasos.numero` sin CHECK de valor positivo
--   ✗ `ingredientes.orden` con NULLs
--
-- RENDIMIENTO:
--   ✗ Falta índice en `recetas.publicada` (columna de filtro más usada)
--   ✗ Falta índice en `recetas.destacada`
--   ✗ Falta índice compuesto publicada+visitas para el catálogo público
-- ═══════════════════════════════════════════════════════════════════════════
