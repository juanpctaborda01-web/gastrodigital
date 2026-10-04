-- ============================================================
--  GastroDigital — Base de datos completa
--  Motor: MySQL 5.7+ / MariaDB 10.3+
--  Importar desde phpMyAdmin o con:
--    mysql -u root -p < gastrodigital.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS recetas_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE recetas_db;

-- ============================================================
--  TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100)    NOT NULL,
    email        VARCHAR(150)    NOT NULL UNIQUE,
    password     VARCHAR(255)    NOT NULL,
    rol          ENUM('admin','chef','usuario') NOT NULL DEFAULT 'usuario',
    avatar       VARCHAR(255)    DEFAULT NULL,         -- ruta de imagen de perfil
    bio          TEXT            DEFAULT NULL,         -- descripción del chef
    activo       TINYINT(1)      NOT NULL DEFAULT 1,   -- 0 = cuenta suspendida
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: categorias
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias (
    id       INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(80)   NOT NULL UNIQUE,
    icono    VARCHAR(60)   DEFAULT 'bi-egg-fried',   -- clase Bootstrap Icon
    color    VARCHAR(7)    DEFAULT '#c0392b',         -- color hex para la UI
    bg       VARCHAR(7)    DEFAULT '#f7ede0'          -- color de fondo del chip
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: recetas
-- ============================================================
CREATE TABLE IF NOT EXISTS recetas (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    titulo              VARCHAR(150)    NOT NULL,
    descripcion         TEXT            DEFAULT NULL,
    tiempo_preparacion  SMALLINT UNSIGNED NOT NULL DEFAULT 30,  -- minutos
    porciones           TINYINT UNSIGNED  NOT NULL DEFAULT 4,
    dificultad          ENUM('Fácil','Media','Difícil') NOT NULL DEFAULT 'Media',
    categoria_id        INT UNSIGNED    DEFAULT NULL,
    categoria           VARCHAR(80)     DEFAULT NULL,            -- desnormalizado para búsquedas rápidas
    imagen              VARCHAR(255)    DEFAULT NULL,
    video_url           VARCHAR(255)    DEFAULT NULL,
    calorias            SMALLINT UNSIGNED DEFAULT NULL,          -- kcal por porción
    chef_id             INT UNSIGNED    NOT NULL,
    publicada           TINYINT(1)      NOT NULL DEFAULT 1,
    destacada           TINYINT(1)      NOT NULL DEFAULT 0,
    visitas             INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (chef_id)       REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (categoria_id)  REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_categoria  (categoria),
    INDEX idx_dificultad (dificultad),
    INDEX idx_chef       (chef_id),
    FULLTEXT idx_busqueda (titulo, descripcion)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: ingredientes
-- ============================================================
CREATE TABLE IF NOT EXISTS ingredientes (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    receta_id   INT UNSIGNED    NOT NULL,
    nombre      VARCHAR(100)    NOT NULL,
    cantidad    VARCHAR(30)     DEFAULT NULL,   -- ej: "200", "1/2", "al gusto"
    unidad      VARCHAR(30)     DEFAULT NULL,   -- ej: "g", "ml", "taza", "cucharada"
    opcional    TINYINT(1)      NOT NULL DEFAULT 0,
    orden       TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE,
    INDEX idx_receta    (receta_id),
    INDEX idx_nombre    (nombre)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: pasos
-- ============================================================
CREATE TABLE IF NOT EXISTS pasos (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    receta_id   INT UNSIGNED    NOT NULL,
    numero      TINYINT UNSIGNED NOT NULL,
    descripcion TEXT            NOT NULL,
    imagen      VARCHAR(255)    DEFAULT NULL,   -- foto opcional del paso
    duracion    SMALLINT UNSIGNED DEFAULT NULL, -- minutos del paso

    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE,
    UNIQUE KEY uq_paso (receta_id, numero)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: favoritos
-- ============================================================
CREATE TABLE IF NOT EXISTS favoritos (
    usuario_id  INT UNSIGNED NOT NULL,
    receta_id   INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (usuario_id, receta_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id)  REFERENCES recetas(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: valoraciones
-- ============================================================
CREATE TABLE IF NOT EXISTS valoraciones (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    receta_id   INT UNSIGNED    NOT NULL,
    usuario_id  INT UNSIGNED    NOT NULL,
    puntuacion  TINYINT UNSIGNED NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario  TEXT            DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_valoracion (receta_id, usuario_id),
    FOREIGN KEY (receta_id)  REFERENCES recetas(id)  ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: comentarios
-- ============================================================
CREATE TABLE IF NOT EXISTS comentarios (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    receta_id   INT UNSIGNED    NOT NULL,
    usuario_id  INT UNSIGNED    NOT NULL,
    contenido   TEXT            NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (receta_id)  REFERENCES recetas(id)  ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_receta (receta_id)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: etiquetas  (tags: vegano, sin gluten, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS etiquetas (
    id     INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50)   NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receta_etiqueta (
    receta_id   INT UNSIGNED NOT NULL,
    etiqueta_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (receta_id, etiqueta_id),
    FOREIGN KEY (receta_id)   REFERENCES recetas(id)   ON DELETE CASCADE,
    FOREIGN KEY (etiqueta_id) REFERENCES etiquetas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: notificaciones
-- ============================================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED    NOT NULL,
    tipo        VARCHAR(50)     NOT NULL,   -- 'comentario', 'favorito', 'valoracion'
    mensaje     VARCHAR(255)    NOT NULL,
    leida       TINYINT(1)      NOT NULL DEFAULT 0,
    url         VARCHAR(255)    DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id, leida)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: sesiones_chatbot  (historial de conversaciones)
-- ============================================================
CREATE TABLE IF NOT EXISTS sesiones_chatbot (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED    DEFAULT NULL,   -- NULL si es visitante
    session_key VARCHAR(64)     NOT NULL,        -- identificador de sesión anónima
    mensaje     TEXT            NOT NULL,
    respuesta   TEXT            NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_session (session_key)
) ENGINE=InnoDB;


-- ============================================================
--  DATOS DE PRUEBA
-- ============================================================

-- Usuarios
-- Contraseñas: Admin123!, Chef123!, User123!  (bcrypt)
INSERT INTO usuarios (nombre, email, password, rol, bio) VALUES
('Administrador',  'admin@gastrodigital.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   'Cuenta de administración del sistema.'),
('Carlos Ramírez', 'carlos@gastrodigital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chef',    'Chef mexicano con 10 años de experiencia en cocina tradicional.'),
('Sofía Torres',   'sofia@gastrodigital.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chef',    'Especialista en repostería y cocina italiana.'),
('Luis Gómez',     'luis@gastrodigital.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', NULL),
('Ana Martínez',   'ana@gastrodigital.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', NULL);

-- Nota: todas las contraseñas de prueba son "password" (hash de Laravel/PHP vacío compatible)
-- Para producción genera hashes reales con password_hash()

-- Categorías
INSERT INTO categorias (nombre, icono, color, bg) VALUES
('Italiana',   'bi-flag',            '#c0392b', '#fdecea'),
('Mexicana',   'bi-fire',            '#d35400', '#fdf0e8'),
('Japonesa',   'bi-flower1',         '#2980b9', '#e8f4fd'),
('Postres',    'bi-cake2',           '#8e44ad', '#f3e8ff'),
('Ensaladas',  'bi-leaf',            '#27ae60', '#edf7e6'),
('Sopas',      'bi-cup-hot-fill',    '#c0392b', '#fdecea'),
('Desayunos',  'bi-brightness-high', '#e8a838', '#fdf5e0'),
('Carnes',     'bi-egg-fried',       '#6d3b1f', '#f5ede6'),
('Mariscos',   'bi-water',           '#1a6e8a', '#e8f6fa'),
('Vegetariana','bi-heart',           '#27ae60', '#edf7e6');

-- Etiquetas
INSERT INTO etiquetas (nombre) VALUES
('Vegano'), ('Vegetariano'), ('Sin gluten'), ('Sin lactosa'),
('Alto en proteína'), ('Bajo en calorías'), ('Rápido'), ('Para niños');

-- ── Recetas ──────────────────────────────────────────────────

-- 1. Pasta Carbonara
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id, destacada) VALUES
('Pasta Carbonara', 'La auténtica carbonara romana: sin nata, solo huevo, queso pecorino y guanciale. Cremosa y llena de sabor.', 30, 2, 'Media', 1, 'Italiana', 520, 3, 1);

SET @r1 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r1, 'Espagueti',        '200', 'g',          1),
(@r1, 'Guanciale',        '150', 'g',          2),
(@r1, 'Yemas de huevo',   '4',   NULL,         3),
(@r1, 'Pecorino romano',  '80',  'g rallado',  4),
(@r1, 'Pimienta negra',   'al gusto', NULL,    5),
(@r1, 'Sal',              'al gusto', NULL,    6);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r1, 1, 'Cocina el espagueti en agua con abundante sal hasta que esté al dente. Reserva una taza del agua de cocción.', 10),
(@r1, 2, 'Corta el guanciale en tiras y dóralo a fuego medio-bajo en una sartén sin aceite hasta que quede crujiente. Retira del fuego.', 8),
(@r1, 3, 'En un bol bate las yemas con el pecorino rallado y pimienta negra generosa hasta obtener una crema espesa.', 3),
(@r1, 4, 'Agrega el espagueti escurrido a la sartén con el guanciale (fuera del fuego). Vierte la mezcla de yemas y mezcla rápido agregando agua de cocción poco a poco hasta lograr una salsa cremosa.', 5),
(@r1, 5, 'Sirve inmediatamente con más pecorino y pimienta.', 2);

-- 2. Tacos al Pastor
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id, destacada) VALUES
('Tacos al Pastor', 'Clásico mexicano con carne de cerdo marinada en achiote y especias, cocinada en trompo o sartén.', 45, 4, 'Media', 2, 'Mexicana', 380, 2, 1);

SET @r2 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r2, 'Lomo de cerdo',       '500', 'g',          1),
(@r2, 'Pasta de achiote',    '3',   'cucharadas', 2),
(@r2, 'Jugo de naranja',     '1/2', 'taza',       3),
(@r2, 'Jugo de piña',        '1/4', 'taza',       4),
(@r2, 'Vinagre blanco',      '2',   'cucharadas', 5),
(@r2, 'Chile guajillo',      '2',   'piezas',     6),
(@r2, 'Ajo',                 '3',   'dientes',    7),
(@r2, 'Comino',              '1',   'cucharadita',8),
(@r2, 'Tortillas de maíz',   '12',  'piezas',     9),
(@r2, 'Piña',                '4',   'rebanadas',  10),
(@r2, 'Cebolla blanca',      '1',   'pieza',      11),
(@r2, 'Cilantro fresco',     'al gusto', NULL,    12),
(@r2, 'Sal y pimienta',      'al gusto', NULL,    13);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r2, 1, 'Licúa el achiote, jugos de naranja y piña, vinagre, ajo, chile guajillo hidratado y comino hasta obtener una marinada suave.', 5),
(@r2, 2, 'Corta el cerdo en filetes delgados y marina en la mezcla al menos 2 horas (idealmente toda la noche).', 10),
(@r2, 3, 'Cocina la carne en sartén caliente o comal hasta dorar bien por ambos lados. Corta en trozos pequeños.', 15),
(@r2, 4, 'Asa las rebanadas de piña hasta que caramelicen y córtalas en cubitos.', 5),
(@r2, 5, 'Calienta las tortillas y arma los tacos con carne, piña, cebolla y cilantro picados. Sirve con salsa verde.', 5);

-- 3. Sushi Casero (California Roll)
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id) VALUES
('California Roll', 'El clásico roll japonés con cangrejo, aguacate y pepino. Perfecto para iniciarse en el sushi casero.', 60, 2, 'Difícil', 3, 'Japonesa', 290, 3);

SET @r3 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r3, 'Arroz para sushi',    '200', 'g',          1),
(@r3, 'Vinagre de arroz',    '3',   'cucharadas', 2),
(@r3, 'Azúcar',              '1',   'cucharada',  3),
(@r3, 'Sal',                 '1',   'cucharadita',4),
(@r3, 'Alga nori',           '2',   'hojas',      5),
(@r3, 'Surimi (cangrejo)',   '100', 'g',          6),
(@r3, 'Aguacate',            '1',   'pieza',      7),
(@r3, 'Pepino',              '1/2', 'pieza',      8),
(@r3, 'Queso crema',         '50',  'g',          9),
(@r3, 'Semillas de sésamo',  'al gusto', NULL,   10),
(@r3, 'Salsa de soya',       'para servir', NULL,11),
(@r3, 'Wasabi',              'al gusto', NULL,   12);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r3, 1, 'Lava el arroz hasta que el agua salga clara. Cocínalo con la misma cantidad de agua (1:1). Mezcla el vinagre, azúcar y sal; incorpóralo al arroz caliente con movimientos suaves. Deja enfriar.', 25),
(@r3, 2, 'Corta el aguacate, pepino y surimi en tiras delgadas.', 10),
(@r3, 3, 'Coloca el alga nori sobre la esterilla de bambú (envuelta en plástico). Extiende el arroz dejando 2 cm libres en el extremo superior.', 5),
(@r3, 4, 'Acomoda el relleno en línea sobre el arroz. Enrolla con firmeza usando la esterilla. Presiona y da forma cilíndrica.', 10),
(@r3, 5, 'Humedece el cuchillo y corta el roll en 8 piezas iguales. Sirve con soya y wasabi.', 5);

-- 4. Tiramisú Clásico
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id, destacada) VALUES
('Tiramisú Clásico', 'El postre italiano por excelencia. Capas de bizcochos empapados en café y crema de mascarpone.', 40, 6, 'Media', 4, 'Postres', 450, 3, 1);

SET @r4 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r4, 'Bizcochos de soletilla', '24',  'piezas',    1),
(@r4, 'Queso mascarpone',       '500', 'g',         2),
(@r4, 'Huevos',                 '4',   'piezas',    3),
(@r4, 'Azúcar',                 '100', 'g',         4),
(@r4, 'Café espresso frío',     '250', 'ml',        5),
(@r4, 'Licor de amaretto',      '2',   'cucharadas',6),
(@r4, 'Cacao en polvo',         'al gusto', NULL,   7);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r4, 1, 'Separa las claras de las yemas. Bate las yemas con el azúcar hasta blanquear. Incorpora el mascarpone y mezcla bien.', 10),
(@r4, 2, 'Monta las claras a punto de nieve firme. Incorpóralas a la mezcla de mascarpone con movimientos envolventes.', 8),
(@r4, 3, 'Mezcla el café frío con el amaretto. Sumerge rápidamente los bizcochos (no los empapes demasiado).', 3),
(@r4, 4, 'En una fuente rectangular, crea capas: bizcochos, crema de mascarpone, bizcochos, crema. Repite.', 10),
(@r4, 5, 'Refrigera mínimo 4 horas (mejor toda la noche). Antes de servir, espolvorea cacao en polvo generosamente.', 5);

-- 5. Ensalada César
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id) VALUES
('Ensalada César', 'La ensalada más famosa del mundo con aderezo cremoso casero, crutones y parmesano.', 15, 2, 'Fácil', 5, 'Ensaladas', 320, 2);

SET @r5 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r5, 'Lechuga romana',        '1',  'cabeza',      1),
(@r5, 'Parmesano',             '50', 'g rallado',   2),
(@r5, 'Crutones',              '1',  'taza',        3),
(@r5, 'Mayonesa',              '3',  'cucharadas',  4),
(@r5, 'Anchoas en aceite',     '2',  'filetes',     5),
(@r5, 'Ajo',                   '1',  'diente',      6),
(@r5, 'Jugo de limón',         '1',  'cucharada',   7),
(@r5, 'Mostaza Dijon',         '1',  'cucharadita', 8),
(@r5, 'Salsa inglesa',         '1',  'cucharadita', 9),
(@r5, 'Pimienta negra',        'al gusto', NULL,   10);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r5, 1, 'Prepara el aderezo: machaca el ajo y las anchoas hasta formar una pasta. Mezcla con mayonesa, mostaza, salsa inglesa, limón y pimienta.', 5),
(@r5, 2, 'Lava y seca bien la lechuga. Córtala en trozos grandes.', 3),
(@r5, 3, 'Mezcla la lechuga con el aderezo. Añade los crutones y el parmesano. Sirve inmediatamente.', 3);

-- 6. Caldo Tlalpeño
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id) VALUES
('Caldo Tlalpeño', 'Sopa tradicional mexicana con pollo, garbanzos y chile chipotle. Reconfortante y llena de sabor.', 50, 4, 'Media', 6, 'Sopas', 280, 2);

SET @r6 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r6, 'Pollo (muslos)',       '500', 'g',          1),
(@r6, 'Garbanzos cocidos',    '1',   'lata 400g',  2),
(@r6, 'Chile chipotle',       '2',   'piezas',     3),
(@r6, 'Jitomate',             '3',   'piezas',     4),
(@r6, 'Cebolla',              '1/2', 'pieza',      5),
(@r6, 'Ajo',                  '3',   'dientes',    6),
(@r6, 'Epazote',              '3',   'ramas',      7),
(@r6, 'Caldo de pollo',       '1.5', 'litros',     8),
(@r6, 'Aguacate',             '2',   'piezas',     9),
(@r6, 'Sal y pimienta',       'al gusto', NULL,   10);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r6, 1, 'Asa el jitomate, la cebolla y el ajo en un comal hasta que estén bien tatemados. Licúa con el chipotle.', 10),
(@r6, 2, 'Calienta el caldo en una olla y cocina el pollo a fuego medio hasta que esté tierno. Deshebralo.', 20),
(@r6, 3, 'Fríe la salsa licuada en un poco de aceite hasta que cambie de color. Incorpora al caldo.', 5),
(@r6, 4, 'Agrega los garbanzos, el pollo deshebrado y el epazote. Sazona y deja hervir 10 minutos.', 10),
(@r6, 5, 'Sirve caliente en tazones y acompaña con aguacate en cubitos y tortillas.', 3);

-- 7. Huevos Benedictinos
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id) VALUES
('Huevos Benedictinos', 'El desayuno elegante por excelencia: huevos pochados sobre muffin inglés con salsa holandesa casera.', 25, 2, 'Difícil', 7, 'Desayunos', 490, 3);

SET @r7 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r7, 'Huevos',             '4',   'piezas',     1),
(@r7, 'Muffins ingleses',   '2',   'piezas',     2),
(@r7, 'Jamón de pierna',    '4',   'rebanadas',  3),
(@r7, 'Mantequilla',        '100', 'g',          4),
(@r7, 'Yemas de huevo',     '3',   NULL,         5),
(@r7, 'Jugo de limón',      '1',   'cucharada',  6),
(@r7, 'Vinagre blanco',     '1',   'cucharada',  7),
(@r7, 'Sal, pimienta, cayena', 'al gusto', NULL, 8);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r7, 1, 'Salsa holandesa: derrite la mantequilla. Bate las yemas con el limón a baño maría hasta que espesen. Agrega la mantequilla en hilo sin dejar de batir. Sazona.', 10),
(@r7, 2, 'Pocha los huevos: en agua con vinagre a punto de hervor, crea un remolino y deja caer el huevo sin cáscara. Cocina 3 minutos.', 8),
(@r7, 3, 'Tuesta los muffins y calienta el jamón en la sartén.', 4),
(@r7, 4, 'Arma: muffin, jamón, huevo pochado y baña con abundante salsa holandesa. Espolvorea cayena.', 2);

-- 8. Rib Eye a la parrilla
INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, calorias, chef_id, destacada) VALUES
('Rib Eye a la Parrilla', 'El corte más apreciado de la res, cocinado a la perfección con mantequilla de hierbas.', 20, 2, 'Media', 8, 'Carnes', 680, 2, 1);

SET @r8 = LAST_INSERT_ID();

INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES
(@r8, 'Rib Eye (3 cm grosor)', '2',   'piezas 350g', 1),
(@r8, 'Mantequilla',           '50',  'g',           2),
(@r8, 'Ajo',                   '3',   'dientes',     3),
(@r8, 'Romero fresco',         '2',   'ramas',       4),
(@r8, 'Tomillo fresco',        '2',   'ramas',       5),
(@r8, 'Sal de mar gruesa',     'al gusto', NULL,     6),
(@r8, 'Pimienta negra molida', 'al gusto', NULL,     7),
(@r8, 'Aceite de oliva',       '1',   'cucharada',   8);

INSERT INTO pasos (receta_id, numero, descripcion, duracion) VALUES
(@r8, 1, 'Saca la carne del refrigerador 30 minutos antes. Seca con papel absorbente. Sazona generosamente con sal gruesa y pimienta.', 2),
(@r8, 2, 'Calienta la parrilla o sartén de hierro al máximo. Agrega aceite de oliva.', 5),
(@r8, 3, 'Sella el Rib Eye 3 minutos por lado sin moverlo para lograr una costra perfecta.', 6),
(@r8, 4, 'Reduce el fuego a medio. Agrega mantequilla, ajo aplastado, romero y tomillo. Baña continuamente la carne con la mantequilla aromatizada por 2 minutos.', 4),
(@r8, 5, 'Retira y deja reposar sobre una tabla 5 minutos antes de cortar. Sirve con las hierbas encima.', 3);

-- ── Valoraciones de prueba ──────────────────────────────────
INSERT INTO valoraciones (receta_id, usuario_id, puntuacion, comentario) VALUES
(1, 4, 5, 'Increíble, quedó exactamente como en Roma. El truco del agua de pasta es clave.'),
(1, 5, 4, 'Muy buena receta, aunque conseguir guanciale aquí es difícil.'),
(2, 4, 5, 'Los tacos quedaron espectaculares, la marinada es perfecta.'),
(3, 5, 4, 'Me costó el primer intento pero valió la pena aprender.'),
(4, 4, 5, 'El mejor tiramisú que he preparado en casa.'),
(8, 5, 5, 'Perfecto el término del corte. El bañado con mantequilla hace toda la diferencia.');

-- ── Comentarios de prueba ───────────────────────────────────
INSERT INTO comentarios (receta_id, usuario_id, contenido) VALUES
(1, 4, '¿Se puede sustituir el guanciale por panceta?'),
(1, 3, 'Claro que sí, la panceta ahumada funciona muy bien como sustituto.'),
(2, 5, 'Añadí chile de árbol a la marinada y quedó con más kick. Recomendado.'),
(4, 4, 'Lo dejé toda la noche en el refrigerador y quedó mucho mejor. Gracias por el tip.');

-- ── Favoritos de prueba ─────────────────────────────────────
INSERT INTO favoritos (usuario_id, receta_id) VALUES
(4, 1), (4, 2), (4, 4), (4, 8),
(5, 1), (5, 3), (5, 4);

-- ── Etiquetas por receta ────────────────────────────────────
INSERT INTO receta_etiqueta (receta_id, etiqueta_id) VALUES
(5, 2),  -- Ensalada César → Vegetariano
(3, 3),  -- California Roll → Sin gluten
(6, 3),  -- Tlalpeño → Sin gluten
(5, 6),  -- Ensalada César → Bajo en calorías
(8, 5);  -- Rib Eye → Alto en proteína

-- ── Notificaciones de prueba ────────────────────────────────
INSERT INTO notificaciones (usuario_id, tipo, mensaje, url) VALUES
(3, 'comentario', 'Luis Gómez comentó en tu receta "Pasta Carbonara"',  '/view/usuario/ver_recetas.php?id=1'),
(3, 'favorito',   'Ana Martínez guardó tu receta "Tiramisú Clásico"',    '/view/usuario/ver_recetas.php?id=4'),
(2, 'valoracion', 'Ana Martínez valoró tu receta "Rib Eye a la Parrilla" con 5 estrellas', '/view/usuario/ver_recetas.php?id=8');
