-- =============================================================
--  GastroDigital — Migración: pais, genero, pais recetas
--  Ejecutar desde phpMyAdmin o con:
--    mysql -u root -p recetas_db < migracion_usuarios_pais_genero.sql
-- =============================================================

USE recetas_db;

-- ── 1. Agregar columnas a usuarios ──────────────────────────
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS genero      ENUM('masculino','femenino','otro') DEFAULT NULL   AFTER bio,
    ADD COLUMN IF NOT EXISTS pais_origen VARCHAR(100) DEFAULT NULL                           AFTER genero;

-- ── 2. Tabla de países de referencia ────────────────────────
CREATE TABLE IF NOT EXISTS paises (
    id_pais  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(100)    NOT NULL UNIQUE,
    codigo   CHAR(2)         NOT NULL UNIQUE    -- ISO 3166-1 alpha-2
) ENGINE=InnoDB;

-- Insertar países (selección amplia)
INSERT IGNORE INTO paises (nombre, codigo) VALUES
('Afghanistan','AF'),('Albania','AL'),('Algeria','DZ'),('Argentina','AR'),
('Australia','AU'),('Austria','AT'),('Bangladesh','BD'),('Belgium','BE'),
('Bolivia','BO'),('Brazil','BR'),('Bulgaria','BG'),('Canada','CA'),
('Chile','CL'),('China','CN'),('Colombia','CO'),('Costa Rica','CR'),
('Croatia','HR'),('Cuba','CU'),('Czech Republic','CZ'),('Denmark','DK'),
('Dominican Republic','DO'),('Ecuador','EC'),('Egypt','EG'),('El Salvador','SV'),
('Ethiopia','ET'),('Finland','FI'),('France','FR'),('Germany','DE'),
('Ghana','GH'),('Greece','GR'),('Guatemala','GT'),('Haiti','HT'),
('Honduras','HN'),('Hungary','HU'),('India','IN'),('Indonesia','ID'),
('Iran','IR'),('Iraq','IQ'),('Ireland','IE'),('Israel','IL'),
('Italy','IT'),('Jamaica','JM'),('Japan','JP'),('Jordan','JO'),
('Kazakhstan','KZ'),('Kenya','KE'),('South Korea','KR'),('Kuwait','KW'),
('Lebanon','LB'),('Libya','LY'),('Malaysia','MY'),('Mexico','MX'),
('Morocco','MA'),('Netherlands','NL'),('New Zealand','NZ'),('Nicaragua','NI'),
('Nigeria','NG'),('Norway','NO'),('Pakistan','PK'),('Panama','PA'),
('Paraguay','PY'),('Peru','PE'),('Philippines','PH'),('Poland','PL'),
('Portugal','PT'),('Puerto Rico','PR'),('Qatar','QA'),('Romania','RO'),
('Russia','RU'),('Saudi Arabia','SA'),('Senegal','SN'),('Serbia','RS'),
('Singapore','SG'),('South Africa','ZA'),('Spain','ES'),('Sweden','SE'),
('Switzerland','CH'),('Syria','SY'),('Taiwan','TW'),('Thailand','TH'),
('Tunisia','TN'),('Turkey','TR'),('Ukraine','UA'),('United Arab Emirates','AE'),
('United Kingdom','GB'),('United States','US'),('Uruguay','UY'),
('Venezuela','VE'),('Vietnam','VN'),('Yemen','YE'),('Zimbabwe','ZW');

-- ── 3. Agregar columna pais a recetas (si no existe) ─────────
ALTER TABLE recetas
    ADD COLUMN IF NOT EXISTS pais VARCHAR(100) DEFAULT NULL AFTER categoria_id;

-- Índice para filtrar recetas por país
ALTER TABLE recetas
    ADD INDEX IF NOT EXISTS idx_pais (pais);

-- ── Fin de migración ────────────────────────────────────────
