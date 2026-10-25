USE recetas_db;
SET NAMES utf8mb4;

-- Agregar pais solo si la columna todavía no existe.
SET @columna_pais = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recetas' AND COLUMN_NAME = 'pais'
);
SET @sql = IF(@columna_pais = 0,
    'ALTER TABLE recetas ADD COLUMN pais VARCHAR(80) DEFAULT NULL AFTER categoria',
    'SELECT 1');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

-- País de las recetas que ya existían.
UPDATE recetas SET pais = CASE titulo
    WHEN 'Pasta Carbonara' THEN 'Italia'
    WHEN 'Tacos al Pastor' THEN 'México'
    WHEN 'California Roll' THEN 'Japón'
    WHEN 'Tiramisú Clásico' THEN 'Italia'
    WHEN 'Ensalada César' THEN 'Estados Unidos'
    WHEN 'Caldo Tlalpeño' THEN 'México'
    WHEN 'Huevos Benedictinos' THEN 'Estados Unidos'
    WHEN 'Rib Eye a la Parrilla' THEN 'Estados Unidos'
    WHEN 'Paella Valenciana (España)' THEN 'España'
    WHEN 'Pollo Tikka Masala (India)' THEN 'India'
    WHEN 'Pad Thai (Tailandia)' THEN 'Tailandia'
    WHEN 'Moussaka (Grecia)' THEN 'Grecia'
    WHEN 'Bibimbap (Corea del Sur)' THEN 'Corea del Sur'
    WHEN 'Cuscús de verduras (Marruecos)' THEN 'Marruecos'
    WHEN 'Fish and Chips (Reino Unido)' THEN 'Reino Unido'
    WHEN 'Feijoada (Brasil)' THEN 'Brasil'
    ELSE pais
END
WHERE pais IS NULL OR pais = '';

-- Diez recetas nuevas de distintos países.
INSERT INTO recetas
    (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, pais, imagen, calorias, chef_id, destacada)
SELECT datos.titulo, datos.descripcion, datos.tiempo, datos.porciones, datos.dificultad,
       c.id, datos.categoria, datos.pais, datos.imagen, datos.calorias, 2, 0
FROM (
    SELECT 'Empanadas criollas (Argentina)' AS titulo, 'Empanadas horneadas rellenas de carne, cebolla, huevo y especias, un clásico argentino.' AS descripcion, 55 AS tiempo, 8 AS porciones, 'Media' AS dificultad, 'Carnes' AS categoria, 'Argentina' AS pais, 'empanadas.jpg' AS imagen, 310 AS calorias
    UNION ALL SELECT 'Ceviche peruano (Perú)', 'Pescado fresco marinado en limón con cebolla morada, cilantro y ají.', 30, 4, 'Media', 'Mariscos', 'Perú', 'ceviche.jpg', 260
    UNION ALL SELECT 'Ratatouille (Francia)', 'Guiso provenzal de berenjena, calabacín, tomate, pimiento y hierbas.', 60, 4, 'Fácil', 'Vegetariana', 'Francia', 'ratatouille.jpg', 220
    UNION ALL SELECT 'Kebab de Adana (Turquía)', 'Brochetas de carne especiada a la parrilla, servidas con pan y vegetales.', 45, 4, 'Media', 'Carnes', 'Turquía', 'kebab.jpg', 480
    UNION ALL SELECT 'Pho bo (Vietnam)', 'Sopa vietnamita de fideos de arroz, caldo aromático, res y hierbas frescas.', 90, 4, 'Difícil', 'Sopas', 'Vietnam', 'pho.jpg', 390
    UNION ALL SELECT 'Tabulé libanés (Líbano)', 'Ensalada fresca de perejil, trigo bulgur, tomate, hierbabuena y limón.', 25, 4, 'Fácil', 'Ensaladas', 'Líbano', 'tabule.jpg', 190
    UNION ALL SELECT 'Doro wat (Etiopía)', 'Guiso etíope de pollo, cebolla y especias berbere, acompañado de injera o arroz.', 80, 4, 'Difícil', 'Pollo', 'Etiopía', 'doro-wat.jpg', 520
    UNION ALL SELECT 'Bacalao a la portuguesa (Portugal)', 'Bacalao al horno con papa, cebolla, aceitunas y aceite de oliva.', 55, 4, 'Media', 'Mariscos', 'Portugal', 'bacalao.jpg', 440
    UNION ALL SELECT 'Nasi goreng (Indonesia)', 'Arroz frito indonesio con vegetales, huevo, pollo y salsa kecap manis.', 35, 2, 'Media', 'Arroces', 'Indonesia', 'nasi-goreng.jpg', 470
    UNION ALL SELECT 'Hamburguesa clásica (Estados Unidos)', 'Hamburguesa casera de res con queso, lechuga, tomate y pan tostado.', 30, 2, 'Fácil', 'Comida Rápida', 'Estados Unidos', 'hamburguesa.jpg', 650
) AS datos
JOIN categorias c ON CONVERT(c.nombre USING utf8mb4) = CONVERT(datos.categoria USING utf8mb4)
WHERE NOT EXISTS (
    SELECT 1 FROM recetas existente
    WHERE CONVERT(existente.titulo USING utf8mb4) = CONVERT(datos.titulo USING utf8mb4)
);

-- Ingredientes y pasos para las diez recetas nuevas.
DELETE i FROM ingredientes i JOIN recetas r ON r.id = i.receta_id
WHERE r.titulo IN (
    'Empanadas criollas (Argentina)', 'Ceviche peruano (Perú)', 'Ratatouille (Francia)',
    'Kebab de Adana (Turquía)', 'Pho bo (Vietnam)', 'Tabulé libanés (Líbano)',
    'Doro wat (Etiopía)', 'Bacalao a la portuguesa (Portugal)', 'Nasi goreng (Indonesia)',
    'Hamburguesa clásica (Estados Unidos)'
);
DELETE p FROM pasos p JOIN recetas r ON r.id = p.receta_id
WHERE r.titulo IN (
    'Empanadas criollas (Argentina)', 'Ceviche peruano (Perú)', 'Ratatouille (Francia)',
    'Kebab de Adana (Turquía)', 'Pho bo (Vietnam)', 'Tabulé libanés (Líbano)',
    'Doro wat (Etiopía)', 'Bacalao a la portuguesa (Portugal)', 'Nasi goreng (Indonesia)',
    'Hamburguesa clásica (Estados Unidos)'
);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Empanadas criollas (Argentina)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Carne molida','500','g',1),(@r,'Discos de empanada','16','piezas',2),(@r,'Cebolla','2','piezas',3),(@r,'Huevo duro','2','piezas',4),(@r,'Pimentón','1','cucharadita',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Sofríe la cebolla y añade la carne, sal, pimienta, comino y pimentón.',15),(@r,2,'Deja enfriar el relleno e incorpora el huevo duro picado.',10),(@r,3,'Rellena cada disco, dobla y cierra los bordes con un repulgue.',10),(@r,4,'Coloca las empanadas en una bandeja y píntalas con huevo batido.',5),(@r,5,'Hornea a 200 grados hasta que estén doradas.',20);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Ceviche peruano (Perú)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Filete de pescado blanco','500','g',1),(@r,'Jugo de limón','1','taza',2),(@r,'Cebolla morada','1','pieza',3),(@r,'Ají y cilantro','al gusto',NULL,4),(@r,'Camote y maíz','para servir',NULL,5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Corta el pescado en cubos y mantenlo frío.',10),(@r,2,'Añade sal, ají, cilantro y jugo de limón.',5),(@r,3,'Mezcla suavemente y deja marinar unos minutos.',10),(@r,4,'Incorpora la cebolla morada en plumas.',3),(@r,5,'Sirve de inmediato con camote y maíz.',2);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Ratatouille (Francia)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Berenjena','1','pieza',1),(@r,'Calabacín','2','piezas',2),(@r,'Tomate','4','piezas',3),(@r,'Pimiento','1','pieza',4),(@r,'Hierbas provenzales','1','cucharadita',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Corta todas las verduras en rodajas o cubos similares.',10),(@r,2,'Sofríe por separado cebolla, pimiento y ajo.',10),(@r,3,'Añade tomate y hierbas, y cocina hasta formar una salsa.',15),(@r,4,'Incorpora berenjena y calabacín y mezcla con cuidado.',20),(@r,5,'Cocina a fuego bajo hasta que las verduras estén tiernas.',15);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Kebab de Adana (Turquía)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Carne molida de cordero','600','g',1),(@r,'Pimiento rojo','1','pieza',2),(@r,'Cebolla','1','pieza',3),(@r,'Comino y pimentón','2','cucharaditas',4),(@r,'Pan plano','4','piezas',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Mezcla la carne con pimiento, cebolla, especias, sal y pimienta.',10),(@r,2,'Amasa la mezcla hasta que quede compacta.',8),(@r,3,'Forma cilindros alrededor de brochetas.',10),(@r,4,'Cocina a la parrilla, girando hasta dorar todos los lados.',12),(@r,5,'Sirve con pan plano, cebolla y perejil.',5);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Pho bo (Vietnam)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Caldo de res','2','litros',1),(@r,'Fideos de arroz','300','g',2),(@r,'Carne de res en láminas','400','g',3),(@r,'Anís y canela','2','piezas',4),(@r,'Albahaca y brotes','al gusto',NULL,5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Tuesta las especias y añádelas al caldo con cebolla y jengibre.',10),(@r,2,'Cocina el caldo a fuego bajo para concentrar los aromas.',60),(@r,3,'Cuece los fideos de arroz y reparte en tazones.',8),(@r,4,'Coloca encima la carne cortada muy fina.',3),(@r,5,'Vierte el caldo hirviendo y termina con hierbas y brotes.',5);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Tabulé libanés (Líbano)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Perejil fresco','2','manojos',1),(@r,'Trigo bulgur','100','g',2),(@r,'Tomate','3','piezas',3),(@r,'Hierbabuena','1','manojo',4),(@r,'Limón y aceite de oliva','al gusto',NULL,5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Hidrata el bulgur con agua y escúrrelo bien.',15),(@r,2,'Pica finamente perejil, hierbabuena y tomate.',10),(@r,3,'Mezcla el bulgur con las verduras y hierbas.',5),(@r,4,'Aliña con limón, aceite, sal y pimienta.',3),(@r,5,'Refrigera unos minutos y sirve fresco.',5);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Doro wat (Etiopía)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Pollo','800','g',1),(@r,'Cebolla','3','piezas',2),(@r,'Pasta berbere','2','cucharadas',3),(@r,'Huevos cocidos','4','piezas',4),(@r,'Mantequilla','60','g',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Cocina la cebolla picada sin aceite hasta que se caramelice.',25),(@r,2,'Añade mantequilla, ajo y pasta berbere y sofríe.',8),(@r,3,'Incorpora el pollo y cubre con agua.',10),(@r,4,'Cocina tapado hasta que el pollo esté tierno y la salsa espesa.',30),(@r,5,'Añade los huevos cocidos y sirve con injera o arroz.',7);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Bacalao a la portuguesa (Portugal)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Bacalao desalado','4','filetes',1),(@r,'Papas','600','g',2),(@r,'Cebolla','2','piezas',3),(@r,'Aceitunas negras','80','g',4),(@r,'Aceite de oliva','100','ml',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Corta las papas y la cebolla en rodajas.',10),(@r,2,'Dora ligeramente las papas y reserva.',12),(@r,3,'Sofríe la cebolla hasta que quede transparente.',8),(@r,4,'Monta papas, cebolla y bacalao en una fuente.',5),(@r,5,'Hornea con aceite de oliva y aceitunas hasta que el pescado esté listo.',20);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Nasi goreng (Indonesia)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Arroz cocido frío','3','tazas',1),(@r,'Pollo','200','g',2),(@r,'Huevo','2','piezas',3),(@r,'Vegetales','1','taza',4),(@r,'Salsa kecap manis','3','cucharadas',5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Calienta el wok y saltea ajo, cebolla y pollo.',10),(@r,2,'Añade los vegetales y cocina hasta que estén tiernos.',6),(@r,3,'Aparta los ingredientes y cocina los huevos batidos.',4),(@r,4,'Incorpora el arroz y la salsa kecap manis.',8),(@r,5,'Saltea todo a fuego alto y sirve caliente.',4);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Hamburguesa clásica (Estados Unidos)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES (@r,'Carne molida de res','400','g',1),(@r,'Pan de hamburguesa','2','piezas',2),(@r,'Queso cheddar','2','rebanadas',3),(@r,'Lechuga y tomate','al gusto',NULL,4),(@r,'Pepinillos y salsa','al gusto',NULL,5);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES (@r,1,'Forma dos medallones de carne y sazona con sal y pimienta.',8),(@r,2,'Cocina los medallones en una plancha caliente.',10),(@r,3,'Coloca el queso encima y deja que se derrita.',2),(@r,4,'Tuesta los panes y prepara los vegetales.',5),(@r,5,'Monta las hamburguesas con vegetales, pepinillos y salsa.',5);

-- Limpiar duplicados antiguos conservando la receta con el ID menor.
INSERT IGNORE INTO favoritos (usuario_id, receta_id)
SELECT f.usuario_id, MIN(r_principal.id)
FROM favoritos f
JOIN recetas r_copia ON r_copia.id = f.receta_id
JOIN recetas r_principal ON r_principal.titulo = r_copia.titulo
GROUP BY f.usuario_id, r_copia.titulo;

DELETE FROM recetas
WHERE id NOT IN (SELECT id FROM (SELECT MIN(id) AS id FROM recetas GROUP BY titulo) AS unicas);
