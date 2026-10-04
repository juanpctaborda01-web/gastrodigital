USE recetas_db;
SET NAMES utf8mb4;

INSERT IGNORE INTO categorias (nombre, icono, color, bg) VALUES
    ('Arroces', 'bi-grid-3x3', '#b7950b', '#fef5d4'),
    ('Pollo', 'bi-egg-fried', '#d4ac0d', '#fef9e7'),
    ('Pastas', 'bi-circle-square', '#c0392b', '#fdecea'),
    ('Comida Rápida', 'bi-lightning-fill', '#e74c3c', '#fce8e6');

-- Imágenes locales para las recetas existentes (incluye las filas repetidas del catálogo).
UPDATE recetas SET imagen = CASE titulo
    WHEN 'Pasta Carbonara' THEN 'pasta-carbonara.jpg'
    WHEN 'Tacos al Pastor' THEN 'tacos-al-pastor.jpg'
    WHEN 'California Roll' THEN 'california-roll.jpg'
    WHEN 'Tiramisú Clásico' THEN 'tiramisu.jpg'
    WHEN 'Ensalada César' THEN 'ensalada-cesar.jpg'
    WHEN 'Caldo Tlalpeño' THEN 'caldo-tlalpeno.jpg'
    WHEN 'Huevos Benedictinos' THEN 'huevos-benedictinos.jpg'
    WHEN 'Rib Eye a la Parrilla' THEN 'rib-eye.jpg'
    ELSE imagen
END
WHERE titulo IN (
    'Pasta Carbonara', 'Tacos al Pastor', 'California Roll', 'Tiramisú Clásico',
    'Ensalada César', 'Caldo Tlalpeño', 'Huevos Benedictinos', 'Rib Eye a la Parrilla'
);

-- Nuevas recetas internacionales. El título incluye el país para identificar su origen.
INSERT INTO recetas
    (titulo, descripcion, tiempo_preparacion, porciones, dificultad, categoria_id, categoria, imagen, calorias, chef_id, destacada)
SELECT datos.titulo, datos.descripcion, datos.tiempo, datos.porciones, datos.dificultad,
       c.id, datos.categoria, datos.imagen, datos.calorias, 2, 0
FROM (
    SELECT 'Paella Valenciana (España)' AS titulo, 'Arroz español con pollo, mariscos, pimientos y azafrán, inspirado en la cocina valenciana.' AS descripcion, 55 AS tiempo, 4 AS porciones, 'Difícil' AS dificultad, 'Arroces' AS categoria, 'paella.jpg' AS imagen, 540 AS calorias
    UNION ALL SELECT 'Pollo Tikka Masala (India)', 'Pollo marinado en especias y yogur, cocinado en una salsa cremosa de tomate.', 50, 4, 'Media', 'Pollo', 'curry.jpg', 460
    UNION ALL SELECT 'Pad Thai (Tailandia)', 'Fideos de arroz salteados con vegetales, cacahuate, huevo y salsa agridulce.', 30, 2, 'Media', 'Pastas', 'pad-thai.jpg', 410
    UNION ALL SELECT 'Moussaka (Grecia)', 'Capas de berenjena, carne sazonada y salsa bechamel al estilo griego.', 75, 6, 'Difícil', 'Carnes', 'moussaka.jpg', 590
    UNION ALL SELECT 'Bibimbap (Corea del Sur)', 'Arroz caliente con vegetales, huevo, carne y salsa gochujang.', 35, 2, 'Media', 'Arroces', 'bibimbap.jpg', 430
    UNION ALL SELECT 'Cuscús de verduras (Marruecos)', 'Cuscús especiado con verduras asadas, garbanzos y hierbas frescas.', 35, 4, 'Fácil', 'Vegetariana', 'couscous.jpg', 360
    UNION ALL SELECT 'Fish and Chips (Reino Unido)', 'Pescado crujiente acompañado de papas doradas, un clásico británico.', 45, 2, 'Media', 'Comida Rápida', 'fish-and-chips.jpg', 620
    UNION ALL SELECT 'Feijoada (Brasil)', 'Guiso brasileño de frijoles negros y carnes, servido con arroz y naranja.', 120, 6, 'Difícil', 'Carnes', 'feijoada.jpg', 680
) AS datos
JOIN categorias c ON CONVERT(c.nombre USING utf8mb4) = CONVERT(datos.categoria USING utf8mb4)
WHERE NOT EXISTS (
    SELECT 1 FROM recetas existente
    WHERE CONVERT(existente.titulo USING utf8mb4) = CONVERT(datos.titulo USING utf8mb4)
);

-- Ingredientes y preparación paso a paso para las recetas internacionales.
DELETE i FROM ingredientes i JOIN recetas r ON r.id = i.receta_id
WHERE r.titulo IN (
    'Paella Valenciana (España)', 'Pollo Tikka Masala (India)', 'Pad Thai (Tailandia)',
    'Moussaka (Grecia)', 'Bibimbap (Corea del Sur)', 'Cuscús de verduras (Marruecos)',
    'Fish and Chips (Reino Unido)', 'Feijoada (Brasil)'
);
DELETE p FROM pasos p JOIN recetas r ON r.id = p.receta_id
WHERE r.titulo IN (
    'Paella Valenciana (España)', 'Pollo Tikka Masala (India)', 'Pad Thai (Tailandia)',
    'Moussaka (Grecia)', 'Bibimbap (Corea del Sur)', 'Cuscús de verduras (Marruecos)',
    'Fish and Chips (Reino Unido)', 'Feijoada (Brasil)'
);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Paella Valenciana (España)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Arroz bomba','300','g',1),(@r,'Pollo','300','g',2),(@r,'Langostinos','200','g',3),(@r,'Pimiento rojo','1','pieza',4),(@r,'Azafrán','1','pizca',5),(@r,'Caldo de pescado','900','ml',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Sofríe el pollo en la paellera hasta dorarlo. Añade el pimiento picado y cocina unos minutos.',10),
(@r,2,'Agrega el arroz y tuéstalo con el sofrito durante un minuto.',3),
(@r,3,'Vierte el caldo caliente con el azafrán y reparte el arroz sin volver a moverlo.',5),
(@r,4,'Cocina a fuego medio durante 15 minutos. Incorpora los langostinos en los últimos 5 minutos.',15),
(@r,5,'Deja reposar la paella tapada durante 5 minutos y sirve con limón.',5);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Pollo Tikka Masala (India)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Pechuga de pollo','600','g',1),(@r,'Yogur natural','150','g',2),(@r,'Tomate triturado','400','g',3),(@r,'Leche de coco','200','ml',4),(@r,'Garam masala','2','cucharaditas',5),(@r,'Ajo y jengibre','2','cucharadas',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Corta el pollo en cubos y mézclalo con yogur, garam masala, ajo y jengibre. Marina 30 minutos.',30),
(@r,2,'Dora el pollo en una sartén caliente y retíralo cuando esté sellado.',8),
(@r,3,'Sofríe cebolla y especias, añade el tomate triturado y cocina hasta espesar.',10),
(@r,4,'Incorpora el pollo y la leche de coco. Cocina a fuego bajo hasta que esté completamente hecho.',15),
(@r,5,'Prueba de sal y sirve caliente con arroz basmati o pan naan.',3);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Pad Thai (Tailandia)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Fideos de arroz','200','g',1),(@r,'Camarones','250','g',2),(@r,'Huevo','2','piezas',3),(@r,'Brotes de soya','100','g',4),(@r,'Cacahuates','50','g',5),(@r,'Salsa de pescado y tamarindo','4','cucharadas',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Remoja los fideos en agua tibia hasta que estén flexibles y escúrrelos.',15),
(@r,2,'Mezcla la salsa de pescado, tamarindo, azúcar y un poco de agua.',3),
(@r,3,'Saltea los camarones con ajo. Muévelos a un lado y cocina los huevos batidos.',5),
(@r,4,'Añade los fideos y la salsa. Saltea hasta que absorban el sabor.',5),
(@r,5,'Incorpora los brotes, mezcla y sirve con cacahuate, limón y chile al gusto.',2);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Moussaka (Grecia)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Berenjenas','3','piezas',1),(@r,'Carne molida de res','500','g',2),(@r,'Tomate triturado','400','g',3),(@r,'Leche','500','ml',4),(@r,'Queso parmesano','80','g',5),(@r,'Canela molida','1','pizca',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Corta las berenjenas en rebanadas, sala y déjalas reposar 15 minutos. Sécalas y dóralas.',20),
(@r,2,'Sofríe la carne con cebolla, tomate, canela, sal y pimienta hasta que reduzca.',20),
(@r,3,'Prepara una bechamel calentando leche con mantequilla y harina. Añade parte del queso.',15),
(@r,4,'Monta capas de berenjena, carne y berenjena en una fuente. Cubre con bechamel.',10),
(@r,5,'Espolvorea queso y hornea a 190 grados hasta gratinar. Reposa antes de cortar.',25);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Bibimbap (Corea del Sur)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Arroz blanco cocido','3','tazas',1),(@r,'Carne de res','250','g',2),(@r,'Zanahoria','1','pieza',3),(@r,'Espinaca','150','g',4),(@r,'Huevo','2','piezas',5),(@r,'Salsa gochujang','3','cucharadas',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Cocina el arroz y mantenlo caliente.',20),
(@r,2,'Corta la carne y marínala con soya, ajo, aceite de sésamo y azúcar.',10),
(@r,3,'Saltea por separado la carne, zanahoria y espinaca para conservar sus sabores.',12),
(@r,4,'Fríe los huevos dejando la yema ligeramente líquida.',5),
(@r,5,'Sirve el arroz en un bol, acomoda los ingredientes encima y termina con gochujang.',3);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Cuscús de verduras (Marruecos)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Cuscús','300','g',1),(@r,'Garbanzos cocidos','250','g',2),(@r,'Zanahoria','2','piezas',3),(@r,'Calabacín','1','pieza',4),(@r,'Caldo vegetal','400','ml',5),(@r,'Comino y canela','1','cucharadita',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Corta las verduras en cubos y sofríelas con aceite, comino, canela, sal y pimienta.',10),
(@r,2,'Añade el caldo vegetal y cocina hasta que las verduras estén tiernas.',15),
(@r,3,'Incorpora los garbanzos y deja hervir 3 minutos.',3),
(@r,4,'Coloca el cuscús en un bol, vierte el caldo caliente y tapa durante 5 minutos.',5),
(@r,5,'Esponja el cuscús con un tenedor, mezcla con las verduras y termina con cilantro.',3);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Fish and Chips (Reino Unido)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Filetes de bacalao','2','piezas',1),(@r,'Papas','500','g',2),(@r,'Harina','150','g',3),(@r,'Cerveza fría','200','ml',4),(@r,'Huevo','1','pieza',5),(@r,'Limón','1','pieza',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Corta las papas en bastones, enjuágalas y sécalas muy bien.',10),
(@r,2,'Mezcla harina, huevo y cerveza fría hasta obtener una masa ligera.',5),
(@r,3,'Fríe las papas hasta que estén doradas y escúrrelas.',12),
(@r,4,'Seca el pescado, pásalo por la masa y fríelo hasta que quede crujiente.',8),
(@r,5,'Sirve con las papas, limón y salsa tártara.',2);

SET @r = (SELECT id FROM recetas WHERE titulo = 'Feijoada (Brasil)' LIMIT 1);
INSERT INTO ingredientes (receta_id,nombre,cantidad,unidad,orden) VALUES
(@r,'Frijoles negros','500','g',1),(@r,'Costilla de cerdo','300','g',2),(@r,'Chorizo','2','piezas',3),(@r,'Cebolla','1','pieza',4),(@r,'Ajo','3','dientes',5),(@r,'Arroz blanco','3','tazas',6);
INSERT INTO pasos (receta_id,numero,descripcion,duracion) VALUES
(@r,1,'Remoja los frijoles durante la noche y escúrrelos antes de cocinar.',480),
(@r,2,'Dora la costilla y el chorizo en una olla grande.',10),
(@r,3,'Añade cebolla, ajo, frijoles y agua. Cocina hasta que los frijoles estén tiernos.',90),
(@r,4,'Retira el exceso de grasa, ajusta de sal y deja espesar el caldo.',15),
(@r,5,'Sirve con arroz blanco, naranja y couve o verduras salteadas.',5);
