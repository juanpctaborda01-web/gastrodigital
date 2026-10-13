-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-10-2026 a las 21:49:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `recetas_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caracteristicas`
--

CREATE TABLE `caracteristicas` (
  `id_caracteristica` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `icono` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `icono` varchar(60) NOT NULL DEFAULT 'bi-egg-fried',
  `color` varchar(7) NOT NULL DEFAULT '#c0392b',
  `bg` varchar(7) NOT NULL DEFAULT '#f7ede0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `icono`, `color`, `bg`) VALUES
(1, 'Entradas', 'aperitivo.png', '#E67E22', '#f7ede0'),
(2, 'Almuerzos', 'almuerzo.png', '#D35400', '#f7ede0'),
(3, 'Arroces', 'arroz.png', '#F1C40F', '#f7ede0'),
(4, 'Bebidas', 'bebidas.png', '#3498DB', '#f7ede0'),
(5, 'Carnes', 'carne.png', '#C0392B', '#f7ede0'),
(6, 'Cenas', 'cena.png', '#8E44AD', '#f7ede0'),
(7, 'Cócteles', 'cocteles.png', '#16A085', '#f7ede0'),
(8, 'Otros', 'otros.png', '#E67E22', '#f7ede0'),
(9, 'Cremas y Púres', 'cremasypures.png', '#E74C3C', '#f7ede0'),
(10, 'Desayunos', 'desayuno.png', '#F39C12', '#f7ede0'),
(11, 'Ensaladas', 'ensalada.png', '#27AE60', '#f7ede0'),
(12, 'Legumbres', 'legumbres.png', '#795548', '#f7ede0'),
(13, 'Pescados y mariscos', 'pescado.png', '#2980B9', '#f7ede0'),
(14, 'Navidad', 'navidad.png', '#C0392B', '#f7ede0'),
(15, 'Panadería', 'panaderia.png', '#D68910', '#f7ede0'),
(16, 'Postres', 'postres.png', '#F5B7B1', '#f7ede0'),
(17, 'Parrilladas', 'parrilla.png', '#7B241C', '#f7ede0'),
(18, 'Pastas', 'pasta.png', '#F5B041', '#f7ede0'),
(19, 'Platos principales', 'plato-principal.png', '#5D6D7E', '#f7ede0'),
(20, 'Pollo', 'pollo-asado.png', '#D35400', '#f7ede0'),
(21, 'Repostería', 'reposteria.png', '#AF7AC5', '#f7ede0'),
(22, 'Sándwiches', 'sandwich.png', '#F5CBA7', '#f7ede0'),
(23, 'Snacks', 'snacks.png', '#F4D03F', '#f7ede0'),
(24, 'Sopas ', 'sopa.png', '#F5B041', '#f7ede0'),
(25, 'Comida Callejera', 'comida-rapida.png', '#E74C3C', '#f7ede0'),
(26, 'Salsas', 'salsas.png', '#C0392B', '#f7ede0'),
(27, 'Huevos', 'huevos.png', '#F7DC6F', '#f7ede0'),
(28, 'Papas', 'papas.png', '#F4D03F', '#f7ede0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `contenido` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas`
--

CREATE TABLE `etiquetas` (
  `id_etiqueta` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id_favorito` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos`
--

CREATE TABLE `generos` (
  `id_genero` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `icono` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `generos`
--

INSERT INTO `generos` (`id_genero`, `nombre`, `icono`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Masculino', 'genero-masculino.svg', 1, '2026-10-03 20:19:28', '2026-10-03 20:26:02'),
(2, 'Femenino', 'femenino.svg', 1, '2026-10-03 20:19:28', '2026-10-03 20:26:02'),
(3, 'Otro', 'tercer-genero.svg', 1, '2026-10-03 20:19:28', '2026-10-03 20:26:02'),
(4, 'Prefiero no decirlo', 'no.svg', 1, '2026-10-03 20:19:28', '2026-10-03 20:30:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingredientes`
--

CREATE TABLE `ingredientes` (
  `id_ingrediente` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cantidad` varchar(30) DEFAULT NULL,
  `unidad` varchar(30) DEFAULT NULL,
  `opcional` tinyint(1) NOT NULL DEFAULT 0,
  `orden` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ingredientes`
--

INSERT INTO `ingredientes` (`id_ingrediente`, `id_receta`, `nombre`, `cantidad`, `unidad`, `opcional`, `orden`) VALUES
(194, 12, 'Arroz bomba', '400', 'g', 0, 1),
(195, 12, 'Pollo troceado', '500', 'g', 0, 2),
(196, 12, 'Conejo troceado', '400', 'g', 0, 3),
(197, 12, 'Judía verde (bajoqueta)', '200', 'g', 0, 4),
(198, 12, 'Garrofón (alubia blanca grande)', '100', 'g', 0, 5),
(199, 12, 'Tomate maduro triturado', '2', 'piezas', 0, 6),
(200, 12, 'Azafrán en hebra', '1', 'pizca', 0, 7),
(201, 12, 'Aceite de oliva virgen extra', '100', 'ml', 0, 8),
(202, 12, 'Agua o caldo', '1.2', 'litros', 0, 9),
(203, 12, 'Sal y romero fresco', 'al gusto', NULL, 0, 10),
(204, 13, 'Fideos para ramen', '200', 'g', 0, 1),
(205, 13, 'Huesos de cerdo', '1', 'kg', 0, 2),
(206, 13, 'Panceta de cerdo (Pork belly)', '300', 'g', 0, 3),
(207, 13, 'Huevos marinados (Ajitsuke Tamago)', '2', 'piezas', 0, 4),
(208, 13, 'Salsa de soya', '4', 'cucharadas', 0, 5),
(209, 13, 'Ajo y jengibre', '2', 'dientes', 0, 6),
(210, 13, 'Cebollín picado', 'al gusto', NULL, 0, 7),
(211, 13, 'Alga nori', '2', 'láminas', 0, 8),
(212, 14, 'Pescado blanco fresco (corvina o lenguado)', '500', 'g', 0, 1),
(213, 14, 'Limones frescos', '10', 'piezas', 0, 2),
(214, 14, 'Cebolla morada en pluma', '1', 'pieza', 0, 3),
(215, 14, 'Ají limo picado', '1', 'pieza', 0, 4),
(216, 14, 'Cilantro picado', '2', 'cucharadas', 0, 5),
(217, 14, 'Camote sancochado', '1', 'pieza', 0, 6),
(218, 14, 'Choclo desgranado cocido', '1', 'taza', 0, 7),
(219, 14, 'Sal fina y pimienta', 'al gusto', NULL, 0, 8),
(220, 15, 'Berenjenas grandes', '3', 'piezas', 0, 1),
(221, 15, 'Carne picada de cordero o res', '500', 'g', 0, 2),
(222, 15, 'Tomate triturado', '400', 'g', 0, 3),
(223, 15, 'Cebolla picada', '1', 'pieza', 0, 4),
(224, 15, 'Ajo picado', '2', 'dientes', 0, 5),
(225, 15, 'Mantequilla', '50', 'g', 0, 6),
(226, 15, 'Harina de trigo', '50', 'g', 0, 7),
(227, 15, 'Leche entera', '500', 'ml', 0, 8),
(228, 15, 'Queso parmesano o kefalotyri', '100', 'g', 0, 9),
(229, 15, 'Canela y nuez moscada', '1', 'pizca', 0, 10),
(230, 16, 'Pechuga o muslo de pollo troceado', '600', 'g', 0, 1),
(231, 16, 'Yogur natural', '1/2', 'taza', 0, 2),
(232, 16, 'Garam masala', '1', 'cucharada', 0, 3),
(233, 16, 'Puré de tomate', '1', 'taza', 0, 4),
(234, 16, 'Mantequilla', '50', 'g', 0, 5),
(235, 16, 'Crema de leche (nata)', '1/2', 'taza', 0, 6),
(236, 16, 'Ajo y jengibre rallados', '1', 'cucharada', 0, 7),
(237, 16, 'Cashews (marañones) molidos', '50', 'g', 0, 8),
(238, 17, 'Frijoles cargamanto cocidos', '2', 'tazas', 0, 1),
(239, 17, 'Arroz blanco cocido', '2', 'tazas', 0, 2),
(240, 17, 'Carne molida sofrita', '250', 'g', 0, 3),
(241, 17, 'Chicharrón de cerdo', '2', 'tiras', 0, 4),
(242, 17, 'Chorizo antioqueño', '2', 'piezas', 0, 5),
(243, 17, 'Huevos fritos', '2', 'piezas', 0, 6),
(244, 17, 'Plátano maduro frito', '1', 'pieza', 0, 7),
(245, 17, 'Arepas blancas pequeñas', '2', 'piezas', 0, 8),
(246, 17, 'Aguacate', '1/2', 'pieza', 0, 9),
(247, 18, 'Frijoles negros', '500', 'g', 0, 1),
(248, 18, 'Costilla de cerdo ahumada', '300', 'g', 0, 2),
(249, 18, 'Carne seca (charqui)', '200', 'g', 0, 3),
(250, 18, 'Chorizo o salchicha calabresa', '200', 'g', 0, 4),
(251, 18, 'Cebolla y ajo picados', '1', 'taza', 0, 5),
(252, 18, 'Hojas de laurel', '3', 'piezas', 0, 6),
(253, 18, 'Naranjas en gajos', '2', 'piezas', 0, 7),
(254, 19, 'Fideos de arroz planos', '200', 'g', 0, 1),
(255, 19, 'Camarones limpios', '150', 'g', 0, 2),
(256, 19, 'Tofu firme en cubos', '100', 'g', 0, 3),
(257, 19, 'Huevos', '2', 'piezas', 0, 4),
(258, 19, 'Brotes de soja (dientes de dragón)', '1', 'taza', 0, 5),
(259, 19, 'Pasta de tamarindo', '2', 'cucharadas', 0, 6),
(260, 19, 'Salsa de pescado', '2', 'cucharadas', 0, 7),
(261, 19, 'Azúcar de palma o morena', '1.5', 'cucharadas', 0, 8),
(262, 19, 'Maní picado y limón', 'al gusto', NULL, 0, 9),
(263, 20, 'Carne de res (morcillo o paleta) en cubos', '600', 'g', 0, 1),
(264, 20, 'Cebolla picada', '2', 'piezas', 0, 2),
(265, 20, 'Pimentón dulce húngaro (Paprika)', '2', 'cucharadas', 0, 3),
(266, 20, 'Pimiento rojo en cubos', '1', 'pieza', 0, 4),
(267, 20, 'Papas en cubos', '2', 'piezas', 0, 5),
(268, 20, 'Caldo de res', '1', 'litro', 0, 6),
(269, 20, 'Semillas de alcaravea (comino húngaro)', '1/2', 'cucharadita', 0, 7),
(270, 21, 'Tapas/Discos para empanadas', '12', 'piezas', 0, 1),
(271, 21, 'Carne de res picada a cuchillo', '500', 'g', 0, 2),
(272, 21, 'Cebolla picada fino', '500', 'g', 0, 3),
(273, 21, 'Huevo duro picado', '2', 'piezas', 0, 4),
(274, 21, 'Aceitunas verdes picadas', '50', 'g', 0, 5),
(275, 21, 'Pimentón dulce y comino', '1', 'cucharada', 0, 6),
(276, 21, 'Grasa vacuna o mantequilla', '50', 'g', 0, 7),
(277, 22, 'Camarones medianos', '300', 'g', 0, 1),
(278, 22, 'Caldo de pollo o camarón', '1', 'litro', 0, 2),
(279, 22, 'Tallos de hierba de limón (Lemongrass)', '2', 'piezas', 0, 3),
(280, 22, 'Galanga o jengibre en rodajas', '4', 'rodajas', 0, 4),
(281, 22, 'Hojas de lima kaffir', '4', 'piezas', 0, 5),
(282, 22, 'Hongos paja o champiñones', '100', 'g', 0, 6),
(283, 22, 'Salsa de pescado y jugo de limón', '3', 'cucharadas', 0, 7),
(284, 22, 'Chile tailandés', '2', 'piezas', 0, 8),
(285, 23, 'Harina de fuerza', '500', 'g', 0, 1),
(286, 23, 'Mantequilla para hojaldrar (fría)', '250', 'g', 0, 2),
(287, 23, 'Leche tibia', '150', 'ml', 0, 3),
(288, 23, 'Agua', '100', 'ml', 0, 4),
(289, 23, 'Azúcar', '60', 'g', 0, 5),
(290, 23, 'Levadura fresca', '20', 'g', 0, 6),
(291, 23, 'Sal', '10', 'g', 0, 7),
(292, 24, 'Harina de maíz precocida blanca', '2', 'tazas', 0, 1),
(293, 24, 'Agua tibia', '2.5', 'tazas', 0, 2),
(294, 24, 'Pechuga de pollo cocida y desmechada', '300', 'g', 0, 3),
(295, 24, 'Aguacate maduro', '1', 'pieza', 0, 4),
(296, 24, 'Mayonesa', '3', 'cucharadas', 0, 5),
(297, 24, 'Cebolla rallada fino', '2', 'cucharadas', 0, 6),
(298, 24, 'Sal y pimienta', 'al gusto', NULL, 0, 7),
(299, 25, 'Harina de trigo', '125', 'g', 0, 1),
(300, 25, 'Leche entera', '250', 'ml', 0, 2),
(301, 25, 'Huevos', '2', 'piezas', 0, 3),
(302, 25, 'Mantequilla derretida', '25', 'g', 0, 4),
(303, 25, 'Crema de avellanas con chocolate (Nutella)', '150', 'g', 0, 5),
(304, 25, 'Fresas/Frutillas frescas picadas', '150', 'g', 0, 6),
(305, 26, 'Lomo de cerdo en filetes finos', '4', 'piezas', 0, 1),
(306, 26, 'Harina de trigo', '1/2', 'taza', 0, 2),
(307, 26, 'Huevos batidos', '2', 'piezas', 0, 3),
(308, 26, 'Pan rallado fino', '1', 'taza', 0, 4),
(309, 26, 'Mantequilla clarificada o aceite para freír', '150', 'ml', 0, 5),
(310, 26, 'Limón en cuartos', '1', 'pieza', 0, 6),
(311, 26, 'Sal y pimienta', 'al gusto', NULL, 0, 7),
(312, 41, 'Muslos de pollo deshuesados', '600', 'g', 0, 1),
(313, 41, 'Salsa de soja', '80', 'ml', 0, 2),
(314, 41, 'Mirin', '60', 'ml', 0, 3),
(315, 41, 'Azúcar', '2', 'cucharadas', 0, 4),
(316, 41, 'Cebolla larga', '3', 'tallos', 0, 5),
(317, 41, 'Aceite', '1', 'cucharada', 0, 6),
(318, 41, 'Semillas de sésamo', '1', 'cucharada', 0, 7),
(319, 40, 'Arroz para sushi', '300', 'g', 0, 1),
(320, 40, 'Agua', '360', 'ml', 0, 2),
(321, 40, 'Vinagre de arroz', '4', 'cucharadas', 0, 3),
(322, 40, 'Salmón fresco', '250', 'g', 0, 4),
(323, 40, 'Pepino', '1', 'unidad', 0, 5),
(324, 40, 'Aguacate', '1', 'unidad', 0, 6),
(325, 40, 'Alga nori', '5', 'hojas', 0, 7),
(326, 40, 'Salsa de soja', '100', 'ml', 0, 8),
(327, 29, 'Arroz blanco cocido', '3', 'tazas', 0, 1),
(328, 29, 'Carne de res', '250', 'g', 0, 2),
(329, 29, 'Zanahoria', '1', 'unidad', 0, 3),
(330, 29, 'Espinaca', '150', 'g', 0, 4),
(331, 29, 'Hongos', '100', 'g', 0, 5),
(332, 29, 'Huevos', '2', 'unidades', 0, 6),
(333, 29, 'Gochujang', '3', 'cucharadas', 0, 7),
(334, 29, 'Aceite de sésamo', '1', 'cucharada', 0, 8),
(335, 31, 'Carne de cerdo', '600', 'g', 0, 1),
(336, 31, 'Pimentón rojo', '1', 'unidad', 0, 2),
(337, 31, 'Pimentón verde', '1', 'unidad', 0, 3),
(338, 31, 'Piña', '200', 'g', 0, 4),
(339, 31, 'Maicena', '80', 'g', 0, 5),
(340, 31, 'Salsa de soja', '3', 'cucharadas', 0, 6),
(341, 31, 'Vinagre de arroz', '3', 'cucharadas', 0, 7),
(342, 31, 'Azúcar', '3', 'cucharadas', 0, 8),
(343, 31, 'Salsa de tomate', '4', 'cucharadas', 0, 9),
(344, 31, 'Aceite', '3', 'cucharadas', 0, 10),
(345, 37, 'Carne de cordero', '600', 'g', 0, 1),
(346, 37, 'Yogur natural', '150', 'g', 0, 2),
(347, 37, 'Cebolla', '1', 'unidad', 0, 3),
(348, 37, 'Ajo', '3', 'dientes', 0, 4),
(349, 37, 'Pimentón', '1', 'cucharadita', 0, 5),
(350, 37, 'Comino', '1', 'cucharadita', 0, 6),
(351, 37, 'Pimienta negra', '1', 'cucharadita', 0, 7),
(352, 37, 'Pan pita', '4', 'unidades', 0, 8),
(353, 37, 'Tomate', '2', 'unidades', 0, 9),
(354, 37, 'Lechuga', '100', 'g', 0, 10),
(355, 35, 'Garbanzos secos', '300', 'g', 0, 1),
(356, 35, 'Cebolla', '1', 'unidad', 0, 2),
(357, 35, 'Ajo', '3', 'dientes', 0, 3),
(358, 35, 'Perejil', '40', 'g', 0, 4),
(359, 35, 'Cilantro', '30', 'g', 0, 5),
(360, 35, 'Comino', '1', 'cucharadita', 0, 6),
(361, 35, 'Harina', '2', 'cucharadas', 0, 7),
(362, 35, 'Sal', '1', 'cucharadita', 0, 8),
(363, 35, 'Aceite', '500', 'ml', 0, 9),
(364, 35, 'Pan pita', '4', 'unidades', 0, 10),
(365, 33, 'Cuscús', '300', 'g', 0, 1),
(366, 33, 'Garbanzos cocidos', '250', 'g', 0, 2),
(367, 33, 'Zanahoria', '2', 'unidades', 0, 3),
(368, 33, 'Calabacín', '1', 'unidad', 0, 4),
(369, 33, 'Cebolla', '1', 'unidad', 0, 5),
(370, 33, 'Caldo vegetal', '400', 'ml', 0, 6),
(371, 33, 'Comino', '1', 'cucharadita', 0, 7),
(372, 33, 'Canela', '1', 'cucharadita', 0, 8),
(373, 33, 'Aceite de oliva', '2', 'cucharadas', 0, 9),
(374, 32, 'Salchichas bratwurst', '4', 'unidades', 0, 1),
(375, 32, 'Salsa de tomate', '250', 'g', 0, 2),
(376, 32, 'Kétchup', '3', 'cucharadas', 0, 3),
(377, 32, 'Curry en polvo', '2', 'cucharaditas', 0, 4),
(378, 32, 'Pimentón dulce', '1', 'cucharadita', 0, 5),
(379, 32, 'Cebolla', '1', 'unidad', 0, 6),
(380, 32, 'Aceite', '1', 'cucharada', 0, 7),
(381, 32, 'Agua', '100', 'ml', 0, 8),
(382, 32, 'Papas fritas', '400', 'g', 0, 9),
(383, 36, 'Carne de cerdo', '600', 'g', 0, 1),
(384, 36, 'Pan pita', '4', 'unidades', 0, 2),
(385, 36, 'Yogur griego', '200', 'g', 0, 3),
(386, 36, 'Pepino', '1', 'unidad', 0, 4),
(387, 36, 'Ajo', '2', 'dientes', 0, 5),
(388, 36, 'Tomate', '2', 'unidades', 0, 6),
(389, 36, 'Cebolla', '1', 'unidad', 0, 7),
(390, 36, 'Orégano', '1', 'cucharadita', 0, 8),
(391, 36, 'Limón', '1', 'unidad', 0, 9),
(392, 36, 'Aceite de oliva', '2', 'cucharadas', 0, 10),
(393, 28, 'Bacalao desalado', '600', 'g', 0, 1),
(394, 28, 'Papas', '600', 'g', 0, 2),
(395, 28, 'Cebolla', '2', 'unidades', 0, 3),
(396, 28, 'Aceitunas negras', '100', 'g', 0, 4),
(397, 28, 'Ajo', '2', 'dientes', 0, 5),
(398, 28, 'Aceite de oliva', '5', 'cucharadas', 0, 6),
(399, 28, 'Perejil', '20', 'g', 0, 7),
(400, 28, 'Huevos', '2', 'unidades', 0, 8),
(401, 39, 'Harina', '400', 'g', 0, 1),
(402, 39, 'Agua', '180', 'ml', 0, 2),
(403, 39, 'Huevo', '1', 'unidad', 0, 3),
(404, 39, 'Carne molida de res', '300', 'g', 0, 4),
(405, 39, 'Carne molida de cerdo', '200', 'g', 0, 5),
(406, 39, 'Cebolla', '1', 'unidad', 0, 6),
(407, 39, 'Sal', '1', 'cucharadita', 0, 7),
(408, 39, 'Pimienta', '1', 'cucharadita', 0, 8),
(409, 39, 'Crema agria', '150', 'g', 0, 9),
(410, 38, 'Arroz cocido', '500', 'g', 0, 1),
(411, 38, 'Pechuga de pollo', '300', 'g', 0, 2),
(412, 38, 'Huevos', '2', 'unidades', 0, 3),
(413, 38, 'Cebolla', '1', 'unidad', 0, 4),
(414, 38, 'Ajo', '3', 'dientes', 0, 5),
(415, 38, 'Salsa de soja dulce', '3', 'cucharadas', 0, 6),
(416, 38, 'Chile', '1', 'unidad', 0, 7),
(417, 38, 'Zanahoria', '1', 'unidad', 0, 8),
(418, 38, 'Aceite', '3', 'cucharadas', 0, 9),
(419, 27, 'Muslos de pollo', '1', 'kg', 0, 1),
(420, 27, 'Salsa de soja', '100', 'ml', 0, 2),
(421, 27, 'Vinagre', '80', 'ml', 0, 3),
(422, 27, 'Ajo', '6', 'dientes', 0, 4),
(423, 27, 'Hojas de laurel', '3', 'unidades', 0, 5),
(424, 27, 'Pimienta negra', '1', 'cucharadita', 0, 6),
(425, 27, 'Agua', '250', 'ml', 0, 7),
(426, 27, 'Aceite', '2', 'cucharadas', 0, 8),
(427, 27, 'Arroz blanco', '400', 'g', 0, 9),
(428, 34, 'Pollo', '1', 'kg', 0, 1),
(429, 34, 'Cebolla', '3', 'unidades', 0, 2),
(430, 34, 'Ajo', '5', 'dientes', 0, 3),
(431, 34, 'Jengibre', '30', 'g', 0, 4),
(432, 34, 'Berbere', '2', 'cucharadas', 0, 5),
(433, 34, 'Mantequilla', '60', 'g', 0, 6),
(434, 34, 'Tomate triturado', '300', 'g', 0, 7),
(435, 34, 'Huevos', '4', 'unidades', 0, 8),
(436, 34, 'Agua', '500', 'ml', 0, 9),
(437, 30, 'Carne molida de res', '700', 'g', 0, 1),
(438, 30, 'Cebolla', '2', 'unidades', 0, 2),
(439, 30, 'Pan', '2', 'rebanadas', 0, 3),
(440, 30, 'Leche', '400', 'ml', 0, 4),
(441, 30, 'Huevos', '3', 'unidades', 0, 5),
(442, 30, 'Curry en polvo', '2', 'cucharaditas', 0, 6),
(443, 30, 'Cúrcuma', '1', 'cucharadita', 0, 7),
(444, 30, 'Pasas', '80', 'g', 0, 8),
(445, 30, 'Chutney de mango', '3', 'cucharadas', 0, 9),
(446, 30, 'Aceite', '2', 'cucharadas', 0, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_chatbot`
--

CREATE TABLE `mensajes_chatbot` (
  `id_menss_chatbot` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `mensaje` text NOT NULL,
  `respuesta` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensajes_chatbot`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `mensaje` varchar(255) NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `id_pais` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo_iso` char(2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id_pais`, `nombre`, `codigo_iso`, `imagen`, `created_at`, `updated_at`) VALUES
(1, 'Afganistán', 'AF', 'af.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(2, 'Albania', 'AL', 'al.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(3, 'Alemania', 'DE', 'de.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(4, 'Andorra', 'AD', 'ad.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(5, 'Angola', 'AO', 'ao.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(6, 'Antigua y Barbuda', 'AG', 'ag.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(7, 'Arabia Saudita', 'SA', 'sa.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(8, 'Argelia', 'DZ', 'dz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(9, 'Argentina', 'AR', 'ar.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(10, 'Armenia', 'AM', 'am.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(11, 'Australia', 'AU', 'au.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(12, 'Austria', 'AT', 'at.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(13, 'Azerbaiyán', 'AZ', 'az.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(14, 'Bahamas', 'BS', 'bs.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(15, 'Bangladés', 'BD', 'bd.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(16, 'Barbados', 'BB', 'bb.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(17, 'Baréin', 'BH', 'bh.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(18, 'Bélgica', 'BE', 'be.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(19, 'Belice', 'BZ', 'bz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(20, 'Benín', 'BJ', 'bj.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(21, 'Bielorrusia', 'BY', 'by.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(22, 'Birmania (Myanmar)', 'MM', 'mm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(23, 'Bolivia', 'BO', 'bo.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(24, 'Bosnia y Herzegovina', 'BA', 'ba.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(25, 'Botsuana', 'BW', 'bw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(26, 'Brasil', 'BR', 'br.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(27, 'Brunéi', 'BN', 'bn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(28, 'Bulgaria', 'BG', 'bg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(29, 'Burkina Faso', 'BF', 'bf.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(30, 'Burundi', 'BI', 'bi.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(31, 'Bután', 'BT', 'bt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(32, 'Cabo Verde', 'CV', 'cv.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(33, 'Camboya', 'KH', 'kh.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(34, 'Camerún', 'CM', 'cm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(35, 'Canadá', 'CA', 'ca.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(36, 'Catar', 'QA', 'qa.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(37, 'Chad', 'TD', 'td.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(38, 'Chile', 'CL', 'cl.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(39, 'China', 'CN', 'cn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(40, 'Chipre', 'CY', 'cy.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(41, 'Colombia', 'CO', 'co.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(42, 'Comoras', 'KM', 'km.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(43, 'Corea del Norte', 'KP', 'kp.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(44, 'Corea del Sur', 'KR', 'kr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(45, 'Costa de Marfil', 'CI', 'ci.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(46, 'Costa Rica', 'CR', 'cr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(47, 'Croacia', 'HR', 'hr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(48, 'Cuba', 'CU', 'cu.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(49, 'Dinamarca', 'DK', 'dk.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(50, 'Dominica', 'DM', 'dm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(51, 'Ecuador', 'EC', 'ec.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(52, 'Egipto', 'EG', 'eg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(53, 'El Salvador', 'SV', 'sv.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(54, 'Emiratos Árabes Unidos', 'AE', 'ae.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(55, 'Eritrea', 'ER', 'er.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(56, 'Eslovaquia', 'SK', 'sk.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(57, 'Eslovenia', 'SI', 'si.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(58, 'España', 'ES', 'es.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(59, 'Estados Unidos', 'US', 'us.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(60, 'Estonia', 'EE', 'ee.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(61, 'Esuatini', 'SZ', 'sz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(62, 'Etiopía', 'ET', 'et.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(63, 'Fiji', 'FJ', 'fj.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(64, 'Filipinas', 'PH', 'ph.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(65, 'Finlandia', 'FI', 'fi.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(66, 'Francia', 'FR', 'fr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(67, 'Gabón', 'GA', 'ga.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(68, 'Gambia', 'GM', 'gm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(69, 'Georgia', 'GE', 'ge.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(70, 'Ghana', 'GH', 'gh.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(71, 'Granada', 'GD', 'gd.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(72, 'Grecia', 'GR', 'gr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(73, 'Guatemala', 'GT', 'gt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(74, 'Guinea', 'GN', 'gn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(75, 'Guinea-Bisáu', 'GW', 'gw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(76, 'Guinea Ecuatorial', 'GQ', 'gq.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(77, 'Guyana', 'GY', 'gy.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(78, 'Haití', 'HT', 'ht.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(79, 'Honduras', 'HN', 'hn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(80, 'Hungría', 'HU', 'hu.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(81, 'India', 'IN', 'in.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(82, 'Indonesia', 'ID', 'id.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(83, 'Irak', 'IQ', 'iq.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(84, 'Irán', 'IR', 'ir.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(85, 'Irlanda', 'IE', 'ie.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(86, 'Islandia', 'IS', 'is.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(87, 'Islas Marshall', 'MH', 'mh.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(88, 'Islas Salomón', 'SB', 'sb.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(89, 'Israel', 'IL', 'il.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(90, 'Italia', 'IT', 'it.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(91, 'Jamaica', 'JM', 'jm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(92, 'Japón', 'JP', 'jp.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(93, 'Jordania', 'JO', 'jo.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(94, 'Kazajistán', 'KZ', 'kz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(95, 'Kenia', 'KE', 'ke.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(96, 'Kirguistán', 'KG', 'kg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(97, 'Kiribati', 'KI', 'ki.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(98, 'Kuwait', 'KW', 'kw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(99, 'Laos', 'LA', 'la.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(100, 'Lesoto', 'LS', 'ls.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(101, 'Letonia', 'LV', 'lv.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(102, 'Líbano', 'LB', 'lb.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(103, 'Liberia', 'LR', 'lr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(104, 'Libia', 'LY', 'ly.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(105, 'Liechtenstein', 'LI', 'li.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(106, 'Lituania', 'LT', 'lt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(107, 'Luxemburgo', 'LU', 'lu.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(108, 'Madagascar', 'MG', 'mg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(109, 'Malasia', 'MY', 'my.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(110, 'Malaui', 'MW', 'mw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(111, 'Maldivas', 'MV', 'mv.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(112, 'Malí', 'ML', 'ml.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(113, 'Malta', 'MT', 'mt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(114, 'Marruecos', 'MA', 'ma.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(115, 'Mauricio', 'MU', 'mu.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(116, 'Mauritania', 'MR', 'mr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(117, 'México', 'MX', 'mx.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(118, 'Micronesia', 'FM', 'fm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(119, 'Moldavia', 'MD', 'md.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(120, 'Mónaco', 'MC', 'mc.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(121, 'Mongolia', 'MN', 'mn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(122, 'Montenegro', 'ME', 'me.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(123, 'Mozambique', 'MZ', 'mz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(124, 'Namibia', 'NA', 'na.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(125, 'Nauru', 'NR', 'nr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(126, 'Nepal', 'NP', 'np.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(127, 'Nicaragua', 'NI', 'ni.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(128, 'Níger', 'NE', 'ne.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(129, 'Nigeria', 'NG', 'ng.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(130, 'Noruega', 'NO', 'no.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(131, 'Nueva Zelanda', 'NZ', 'nz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(132, 'Omán', 'OM', 'om.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(133, 'Países Bajos', 'NL', 'nl.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(134, 'Pakistán', 'PK', 'pk.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(135, 'Palaos', 'PW', 'pw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(136, 'Palestina', 'PS', 'ps.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(137, 'Panamá', 'PA', 'pa.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(138, 'Papúa Nueva Guinea', 'PG', 'pg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(139, 'Paraguay', 'PY', 'py.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(140, 'Perú', 'PE', 'pe.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(141, 'Polonia', 'PL', 'pl.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(142, 'Portugal', 'PT', 'pt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(143, 'Reino Unido', 'GB', 'gb.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(144, 'República Centroafricana', 'CF', 'cf.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(145, 'República Checa', 'CZ', 'cz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(146, 'República del Congo', 'CG', 'cg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(147, 'República Democrática del Congo', 'CD', 'cd.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(148, 'República Dominicana', 'DO', 'do.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(149, 'Ruanda', 'RW', 'rw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(150, 'Rumania', 'RO', 'ro.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(151, 'Rusia', 'RU', 'ru.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(152, 'Samoa', 'WS', 'ws.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(153, 'San Cristóbal y Nieves', 'KN', 'kn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(154, 'San Marino', 'SM', 'sm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(155, 'San Vicente y las Granadinas', 'VC', 'vc.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(156, 'Santa Lucía', 'LC', 'lc.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(157, 'Santo Tomé y Príncipe', 'ST', 'st.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(158, 'Senegal', 'SN', 'sn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(159, 'Serbia', 'RS', 'rs.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(160, 'Seychelles', 'SC', 'sc.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(161, 'Sierra Leona', 'SL', 'sl.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(162, 'Singapur', 'SG', 'sg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(163, 'Siria', 'SY', 'sy.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(164, 'Somalia', 'SO', 'so.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(165, 'Sri Lanka', 'LK', 'lk.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(166, 'Sudáfrica', 'ZA', 'za.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(167, 'Sudán', 'SD', 'sd.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(168, 'Sudán del Sur', 'SS', 'ss.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(169, 'Suecia', 'SE', 'se.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(170, 'Suiza', 'CH', 'ch.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(171, 'Surinam', 'SR', 'sr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(172, 'Tailandia', 'TH', 'th.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(173, 'Tanzania', 'TZ', 'tz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(174, 'Tayikistán', 'TJ', 'tj.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(175, 'Timor Oriental', 'TL', 'tl.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(176, 'Togo', 'TG', 'tg.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(177, 'Tonga', 'TO', 'to.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(178, 'Trinidad y Tobago', 'TT', 'tt.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(179, 'Túnez', 'TN', 'tn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(180, 'Turkmenistán', 'TM', 'tm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(181, 'Turquía', 'TR', 'tr.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(182, 'Tuvalu', 'TV', 'tv.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(183, 'Ucrania', 'UA', 'ua.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(184, 'Uganda', 'UG', 'ug.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(185, 'Uruguay', 'UY', 'uy.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(186, 'Uzbekistán', 'UZ', 'uz.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(187, 'Vanuatu', 'VU', 'vu.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(188, 'Vaticano', 'VA', 'va.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(189, 'Venezuela', 'VE', 've.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(190, 'Vietnam', 'VN', 'vn.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(191, 'Yemen', 'YE', 'ye.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(192, 'Yibuti', 'DJ', 'dj.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(193, 'Zambia', 'ZM', 'zm.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(194, 'Zimbabue', 'ZW', 'zw.svg', '2026-10-01 23:52:43', '2026-10-03 18:12:13'),
(195, 'Macedonia del Norte', 'MK', 'mk.svg', '2026-10-01 23:56:13', '2026-10-03 18:12:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasos`
--

CREATE TABLE `pasos` (
  `id_pasos` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `duracion` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pasos`
--

INSERT INTO `pasos` (`id_pasos`, `id_receta`, `numero`, `descripcion`, `imagen`, `duracion`) VALUES
(103, 12, 1, 'Calienta el aceite en la paella y dora el pollo y el conejo sazonados hasta que estén bien dorados.', NULL, 15),
(104, 12, 2, 'Agrega las judías verdes y el garrofón. Sofríe unos minutos junto a la carne.', NULL, 8),
(105, 12, 3, 'Haz un hueco en el centro y añade el tomate triturado; sofríe bien hasta que evapore el agua.', NULL, 5),
(106, 12, 4, 'Vierte el agua o caldo hasta los remaches de las asas y añade el azafrán. Deja hervir a fuego medio-alto por 15 minutos.', NULL, 15),
(107, 12, 5, 'Añade el arroz distribuyéndolo uniformemente. Cocina 8 minutos a fuego fuerte y luego 10 minutos a fuego bajo. Deja reposar con romero antes de servir.', NULL, 18),
(108, 13, 1, 'Lava minuciosamente los huesos de cerdo. Hirvélos en abundante agua limpia por 6 a 8 horas removiendo la espuma constante para lograr un caldo blanco y cremoso.', NULL, 360),
(109, 13, 2, 'En paralelo, cocina la panceta de cerdo enrollada en salsa de soya, mirin, ajo y jengibre hasta que esté suave. Corta en rodajas (chashu).', NULL, 60),
(110, 13, 3, 'Cocina los fideos para ramen en agua hirviendo según el empaque.', NULL, 3),
(111, 13, 4, 'Sirve en un tazón hondo el fondo de salsa (tare), añade el caldo de cerdo hirviendo, incorpora los fideos y decora con panceta, huevo marinado, cebollín y alga nori.', NULL, 5),
(112, 14, 1, 'Corta el pescado blanco en cubos de 2 cm. Colócalo en un bol de vidrio previamente enfriado.', NULL, 5),
(113, 14, 2, 'Añade sal fina y sazona mezclando suavemente. Incorpora el ají limo y el cilantro picados.', NULL, 3),
(114, 14, 3, 'Exprime los limones suavemente directamente sobre el pescado sin apretar demasiado la cáscara para no amargar. Mezcla bien por 1 o 2 minutos.', NULL, 3),
(115, 14, 4, 'Agrega la cebolla morada lavada en agua helada. Sirve de inmediato acompañado de rodajas de camote cocido y choclo.', NULL, 4),
(116, 15, 1, 'Corta las berenjenas en rodajas, espolvorea sal para quitar amargor y dora en sartén o al horno con aceite de oliva.', NULL, 20),
(117, 15, 2, 'Prepara el relleno: sofríe cebolla y ajo, añade la carne de cordero o res hasta dorar, luego añade el tomate, canela, sal y pimienta. Reduce a fuego lento.', NULL, 25),
(118, 15, 3, 'Prepara una salsa bechamel espesa derritiendo mantequilla, añadiendo harina y leche sin dejar de batir. Finaliza con queso y nuez moscada.', NULL, 15),
(119, 15, 4, 'En un molde refractario coloca capas: berenjena, salsa de carne, berenjena y cubre con la bechamel. Hornea a 180°C por 35-40 minutos hasta dorar.', NULL, 40),
(120, 16, 1, 'Marina el pollo troceado con yogur, ajo, jengibre y garam masala por mínimo 30 minutos.', NULL, 30),
(121, 16, 2, 'Dora los trozos de pollo en una sartén con un poco de mantequilla y reserva.', NULL, 10),
(122, 16, 3, 'En la misma sartén, prepara la salsa sofríendo tomates, especias y marañones molidos. Licúa la mezcla para lograr una textura aterciopelada.', NULL, 15),
(123, 16, 4, 'Regresa la salsa a la sartén, añade abundante mantequilla, la crema de leche y el pollo. Cocina a fuego lento durante 10 minutos.', NULL, 10),
(124, 17, 1, 'Cocina los frijoles en olla a presión hasta que queden muy blandos y el caldo espese.', NULL, 45),
(125, 17, 2, 'Fríe los chicharrones hasta que estén bien tostados y crujientes.', NULL, 15),
(126, 17, 3, 'Sazona la carne molida y sofríela. Fríe el chorizo, el plátano maduro en tajadas y los huevos fritos.', NULL, 15),
(127, 17, 4, 'Sirve en una bandeja grande disponiendo el arroz, frijoles, carne, chicharrón, chorizo, tajada, huevo frito, arepa y aguacate.', NULL, 5),
(128, 18, 1, 'Remoja los frijoles negros y las carnes saladas desde el día anterior.', NULL, 720),
(129, 18, 2, 'Cocina las carnes y las costillas en agua hirviendo hasta que estén tiernas. Reserva.', NULL, 60),
(130, 18, 3, 'Cocina los frijoles negros. Cuando estén suaves, agrega las carnes precocidas y la calabresa.', NULL, 45),
(131, 18, 4, 'Haz un refrito con abundante ajo y cebolla picada y viértelo en la olla para espesar el caldo. Sirve acompañado de arroz y gajos de naranja.', NULL, 15),
(132, 19, 1, 'Remoja los fideos de arroz en agua tibia hasta que estén flexibles pero firmes.', NULL, 15),
(133, 19, 2, 'Mezcla en un tazón la pasta de tamarindo, la salsa de pescado y el azúcar de palma para hacer la salsa.', NULL, 5),
(134, 19, 3, 'En un wok muy caliente sofríe el tofu y los camarones. Haz espacio y cuaja los huevos revolviendo rápidamente.', NULL, 5),
(135, 19, 4, 'Añade los fideos y la salsa. Saltea vigorosamente hasta que absorban el líquido. Termina con brotes de soja, maní triturado y limón.', NULL, 5),
(136, 20, 1, 'Dora las cebollas en grasa o aceite a fuego medio hasta que estén transparentes.', NULL, 10),
(137, 20, 2, 'Retira del fuego momentáneamente e incorpora el pimentón dulce (paprika) para evitar que se queme.', NULL, 2),
(138, 20, 3, 'Agrega la carne de res en cubos, pimiento rojo, semillas de alcaravea y vierte el caldo. Tapa y cocina a fuego bajo por 1 hora.', NULL, 60),
(139, 20, 4, 'Añade las papas en cubos y cocina 20 minutos más hasta que la carne esté extremadamente tierno y la salsa espese.', NULL, 20),
(140, 21, 1, 'Sofríe la cebolla en grasa vacuna a fuego bajo hasta que esté muy transparente y dulce.', NULL, 15),
(141, 21, 2, 'Agrega la carne picada, pimentón y comino. Cocina brevemente sin resecar la carne. Deja enfriar completamente el refrito.', NULL, 10),
(142, 21, 3, 'Incorpora el huevo duro y las aceitunas picadas al refrito frío.', NULL, 5),
(143, 21, 4, 'Rellena los discos de empanada, cierra con el repulgue tradicional y hornea a 220°C por 12-15 minutos hasta dorar.', NULL, 15),
(144, 22, 1, 'Calienta el caldo e incorpora los tallos de hierba de limón golpeados, la galanga y las hojas de lima kaffir.', NULL, 10),
(145, 22, 2, 'Añade los hongos paja y los chiles picados; cocina a fuego lento por 5 minutos.', NULL, 5),
(146, 22, 3, 'Agrega los camarones y cocina solo 3 minutos hasta que cambien de color a rosado.', NULL, 3),
(147, 22, 4, 'Retira del fuego y sazona con salsa de pescado y abundante jugo de limón fresco.', NULL, 2),
(148, 23, 1, 'Mezcla harina, leche, agua, levadura, azúcar y sal. Amasa hasta lograr una masa lisa y deja reposar en refrigeración.', NULL, 60),
(149, 23, 2, 'Aplana el bloque de mantequilla fría. Estira la masa y envuelve la mantequilla adentro.', NULL, 20),
(150, 23, 3, 'Realiza 3 vueltas simples dobles refrigerando la masa 30 minutos entre cada vuelta para laminar.', NULL, 90),
(151, 23, 4, 'Estira la masa a 4mm, corta triángulos, enrolla desde la base y fermenta hasta que dupliquen su tamaño. Pincela con huevo y hornea a 190°C por 15 min.', NULL, 30),
(152, 24, 1, 'Mezcla la harina de maíz con agua tibia y sal. Amasa bien y deja reposar 5 minutos. Forma bolas y aplana en forma de arepas.', NULL, 10),
(153, 24, 2, 'Cocina las arepas en un budare o sartén por ambos lados hasta que se forme una costra. Termina de dorar al horno si deseas.', NULL, 15),
(154, 24, 3, 'En un bol tritura el aguacate, mezcla con la mayonesa, pollo desmechado, cebolla, sal y pimienta hasta formar una pasta cremosa.', NULL, 10),
(155, 24, 4, 'Abre las arepas calientes por la mitad y rellena generosamente con la mezcla.', NULL, 3),
(156, 25, 1, 'Licúa la harina, leche, huevos y mantequilla derretida hasta obtener una mezcla líquida sin grumos. Deja reposar 15 minutos.', NULL, 15),
(157, 25, 2, 'Engrasa una sartén antiadherente, vierte un cucharón fino de mezcla y distribuye velozmente. Cocina 1 minuto por lado.', NULL, 10),
(158, 25, 3, 'Unta cada crepe caliente con crema de Nutella, coloca fresas picadas y dobla en triángulos.', NULL, 5),
(159, 26, 1, 'Espalóma o golpee suavemente los filetes de cerdo entre plástico hasta dejarlos de 5mm de grosor.', NULL, 10),
(160, 26, 2, 'Sazona con sal y pimienta. Pasa por harina, luego por huevo batido y finalmente por pan rallado sin presionar demasiado.', NULL, 8),
(161, 26, 3, 'Fríe en abundante mantequilla clarificada o aceite muy caliente en constante movimiento hasta dorar parejo (2 min por lado).', NULL, 5),
(162, 26, 4, 'Escurre en papel absorbente y sirve caliente acompañado de gajos de limón fresco.', NULL, 2),
(163, 41, 1, 'Cortar el pollo en trozos pequeños y la cebolla larga en segmentos.', NULL, 5),
(164, 41, 2, 'Mezclar la salsa de soja, el mirin y el azúcar para preparar la salsa.', NULL, 5),
(165, 41, 3, 'Ensartar el pollo en brochetas alternándolo con la cebolla larga.', NULL, 5),
(166, 41, 4, 'Calentar una parrilla o sartén ligeramente engrasada.', NULL, 3),
(167, 41, 5, 'Asar las brochetas girándolas para que se cocinen uniformemente.', NULL, 12),
(168, 41, 6, 'Pincelar con la salsa y cocinar hasta que el pollo esté completamente cocido.', NULL, 5),
(169, 41, 7, 'Espolvorear las semillas de sésamo y servir caliente.', NULL, 2),
(170, 40, 1, 'Lavar el arroz varias veces hasta que el agua salga casi transparente.', NULL, 10),
(171, 40, 2, 'Cocinar el arroz con el agua y dejarlo reposar.', NULL, 20),
(172, 40, 3, 'Mezclar el arroz caliente con el vinagre de arroz.', NULL, 5),
(173, 40, 4, 'Cortar el salmón, el pepino y el aguacate en tiras.', NULL, 10),
(174, 40, 5, 'Colocar una hoja de nori sobre una esterilla y cubrir con arroz.', NULL, 5),
(175, 40, 6, 'Agregar el relleno y enrollar firmemente.', NULL, 5),
(176, 40, 7, 'Cortar el rollo en porciones y servir con salsa de soja.', NULL, 5),
(177, 29, 1, 'Preparar y mantener caliente el arroz blanco.', NULL, 15),
(178, 29, 2, 'Cortar la carne en tiras delgadas.', NULL, 5),
(179, 29, 3, 'Saltear la carne hasta que esté bien cocida.', NULL, 8),
(180, 29, 4, 'Cocinar por separado la zanahoria, la espinaca y los hongos.', NULL, 10),
(181, 29, 5, 'Freír los huevos dejando la yema ligeramente líquida.', NULL, 5),
(182, 29, 6, 'Servir el arroz en un tazón y distribuir encima las verduras y la carne.', NULL, 3),
(183, 29, 7, 'Agregar el huevo, el gochujang y el aceite de sésamo antes de mezclar.', NULL, 2),
(184, 31, 1, 'Cortar el cerdo en trozos pequeños y uniformes.', NULL, 5),
(185, 31, 2, 'Cubrir los trozos de cerdo con maicena.', NULL, 5),
(186, 31, 3, 'Freír el cerdo hasta que esté dorado.', NULL, 10),
(187, 31, 4, 'Cortar los pimentones y la piña.', NULL, 5),
(188, 31, 5, 'Mezclar salsa de tomate, soja, vinagre y azúcar.', NULL, 5),
(189, 31, 6, 'Saltear los vegetales y agregar la salsa agridulce.', NULL, 8),
(190, 31, 7, 'Incorporar el cerdo y la piña y cocinar hasta integrar.', NULL, 7),
(191, 37, 1, 'Cortar la carne en trozos y colocarla en un recipiente.', NULL, 5),
(192, 37, 2, 'Mezclar yogur, ajo, cebolla y especias.', NULL, 5),
(193, 37, 3, 'Marinar la carne durante al menos 30 minutos.', NULL, 30),
(194, 37, 4, 'Ensartar la carne en brochetas.', NULL, 5),
(195, 37, 5, 'Asar las brochetas hasta que la carne esté bien cocida.', NULL, 12),
(196, 37, 6, 'Calentar ligeramente el pan pita.', NULL, 3),
(197, 37, 7, 'Servir la carne con tomate y lechuga dentro del pan.', NULL, 5),
(198, 35, 1, 'Remojar los garbanzos en agua durante varias horas.', NULL, 480),
(199, 35, 2, 'Escurrir los garbanzos y triturarlos con cebolla y ajo.', NULL, 5),
(200, 35, 3, 'Agregar perejil, cilantro, comino, harina y sal.', NULL, 5),
(201, 35, 4, 'Formar pequeñas bolas con la mezcla.', NULL, 10),
(202, 35, 5, 'Calentar el aceite y freír los falafel.', NULL, 10),
(203, 35, 6, 'Escurrir sobre papel absorbente.', NULL, 3),
(204, 35, 7, 'Servir con pan pita y vegetales.', NULL, 5),
(205, 33, 1, 'Cortar la cebolla, la zanahoria y el calabacín.', NULL, 5),
(206, 33, 2, 'Sofreír la cebolla en aceite de oliva.', NULL, 5),
(207, 33, 3, 'Agregar zanahoria y calabacín y cocinar.', NULL, 8),
(208, 33, 4, 'Añadir garbanzos, comino y canela.', NULL, 5),
(209, 33, 5, 'Calentar el caldo vegetal.', NULL, 5),
(210, 33, 6, 'Agregar el cuscús al caldo y dejar hidratar.', NULL, 5),
(211, 33, 7, 'Mezclar el cuscús con las verduras y servir.', NULL, 5),
(212, 32, 1, 'Picar finamente la cebolla.', NULL, 3),
(213, 32, 2, 'Sofreír la cebolla con aceite hasta que esté transparente.', NULL, 5),
(214, 32, 3, 'Agregar salsa de tomate, kétchup, curry, pimentón y agua.', NULL, 3),
(215, 32, 4, 'Cocinar la salsa a fuego bajo hasta que espese ligeramente.', NULL, 8),
(216, 32, 5, 'Cocinar las salchichas hasta que estén doradas y bien cocidas.', NULL, 12),
(217, 32, 6, 'Cortar las salchichas en rodajas.', NULL, 2),
(218, 32, 7, 'Cubrir las salchichas con la salsa y espolvorear curry.', NULL, 2),
(219, 32, 8, 'Servir calientes con papas fritas si se desea.', NULL, 2),
(220, 36, 1, 'Cortar la carne en tiras finas.', NULL, 5),
(221, 36, 2, 'Marinar la carne con limón, aceite y orégano.', NULL, 10),
(222, 36, 3, 'Cocinar la carne hasta que esté dorada y bien cocida.', NULL, 12),
(223, 36, 4, 'Rallar el pepino y mezclarlo con yogur y ajo.', NULL, 5),
(224, 36, 5, 'Cortar el tomate y la cebolla.', NULL, 5),
(225, 36, 6, 'Calentar los panes pita.', NULL, 3),
(226, 36, 7, 'Rellenar los panes con carne, vegetales y tzatziki.', NULL, 5),
(227, 28, 1, 'Cortar las papas en rodajas y cocinarlas parcialmente.', NULL, 10),
(228, 28, 2, 'Cortar la cebolla en tiras finas.', NULL, 5),
(229, 28, 3, 'Sofreír la cebolla con ajo y aceite de oliva.', NULL, 8),
(230, 28, 4, 'Agregar el bacalao y cocinar ligeramente.', NULL, 8),
(231, 28, 5, 'Incorporar las papas y mezclar cuidadosamente.', NULL, 5),
(232, 28, 6, 'Colocar la preparación en una fuente para horno.', NULL, 3),
(233, 28, 7, 'Agregar los huevos cocidos y las aceitunas.', NULL, 5),
(234, 28, 8, 'Hornear hasta que esté caliente y ligeramente dorado.', NULL, 15),
(235, 39, 1, 'Mezclar harina, agua y huevo hasta formar una masa.', NULL, 10),
(236, 39, 2, 'Amasar hasta obtener una masa lisa y dejar reposar.', NULL, 20),
(237, 39, 3, 'Mezclar las carnes con cebolla, sal y pimienta.', NULL, 5),
(238, 39, 4, 'Estirar la masa y cortar pequeños círculos.', NULL, 10),
(239, 39, 5, 'Colocar una pequeña cantidad de relleno en cada círculo.', NULL, 10),
(240, 39, 6, 'Doblar y cerrar los pelmeni.', NULL, 10),
(241, 39, 7, 'Cocer en agua hirviendo hasta que floten y estén cocidos.', NULL, 5),
(242, 39, 8, 'Servir con crema agria.', NULL, 3),
(243, 38, 1, 'Cortar el pollo y los vegetales en trozos pequeños.', NULL, 5),
(244, 38, 2, 'Calentar aceite en una sartén grande.', NULL, 2),
(245, 38, 3, 'Sofreír ajo, cebolla y chile.', NULL, 5),
(246, 38, 4, 'Agregar el pollo y cocinarlo completamente.', NULL, 8),
(247, 38, 5, 'Incorporar la zanahoria y cocinar unos minutos.', NULL, 5),
(248, 38, 6, 'Agregar el arroz y mezclar.', NULL, 5),
(249, 38, 7, 'Añadir la salsa de soja dulce.', NULL, 3),
(250, 38, 8, 'Agregar los huevos y cocinar hasta integrar.', NULL, 5),
(251, 27, 1, 'Marinar el pollo con salsa de soja y ajo.', NULL, 20),
(252, 27, 2, 'Calentar aceite y dorar el pollo.', NULL, 10),
(253, 27, 3, 'Agregar la marinada, el vinagre y el agua.', NULL, 5),
(254, 27, 4, 'Incorporar las hojas de laurel y la pimienta.', NULL, 3),
(255, 27, 5, 'Cocinar hasta que el pollo esté tierno y bien cocido.', NULL, 25),
(256, 27, 6, 'Reducir la salsa hasta que espese ligeramente.', NULL, 5),
(257, 27, 7, 'Servir caliente acompañado de arroz blanco.', NULL, 5),
(258, 34, 1, 'Picar finamente la cebolla y cocinarla a fuego bajo.', NULL, 20),
(259, 34, 2, 'Agregar el ajo y el jengibre triturados.', NULL, 5),
(260, 34, 3, 'Incorporar la mantequilla y las especias berbere.', NULL, 5),
(261, 34, 4, 'Agregar el tomate y cocinar la salsa.', NULL, 15),
(262, 34, 5, 'Incorporar el pollo y mezclar bien.', NULL, 10),
(263, 34, 6, 'Agregar agua y cocinar hasta que el pollo esté tierno.', NULL, 30),
(264, 34, 7, 'Cocer los huevos por separado y pelarlos.', NULL, 10),
(265, 34, 8, 'Agregar los huevos al estofado antes de servir.', NULL, 5),
(266, 30, 1, 'Remojar el pan en parte de la leche.', NULL, 5),
(267, 30, 2, 'Sofreír la cebolla hasta que esté transparente.', NULL, 7),
(268, 30, 3, 'Agregar la carne molida y cocinarla.', NULL, 10),
(269, 30, 4, 'Incorporar curry, cúrcuma, pasas y chutney.', NULL, 8),
(270, 30, 5, 'Agregar el pan remojado y mezclar.', NULL, 5),
(271, 30, 6, 'Colocar la mezcla en una fuente para horno.', NULL, 5),
(272, 30, 7, 'Batir los huevos con la leche restante.', NULL, 5),
(273, 30, 8, 'Verter la mezcla de huevo sobre la carne.', NULL, 3),
(274, 30, 9, 'Hornear hasta que la superficie esté firme y dorada.', NULL, 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id_receta` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tiempo_preparacion` smallint(5) UNSIGNED DEFAULT NULL,
  `porciones` tinyint(3) UNSIGNED DEFAULT NULL,
  `dificultad` enum('Fácil','Media','Difícil') NOT NULL DEFAULT 'Media',
  `id_categoria` int(10) UNSIGNED DEFAULT NULL,
  `id_pais` int(10) UNSIGNED DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `calorias` smallint(5) UNSIGNED DEFAULT NULL,
  `id_chef` int(10) UNSIGNED DEFAULT NULL,
  `publicada` tinyint(1) NOT NULL DEFAULT 0,
  `destacada` tinyint(1) NOT NULL DEFAULT 0,
  `visitas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`id_receta`, `titulo`, `descripcion`, `tiempo_preparacion`, `porciones`, `dificultad`, `id_categoria`, `id_pais`, `imagen`, `video_url`, `calorias`, `id_chef`, `publicada`, `destacada`, `visitas`, `created_at`, `updated_at`) VALUES
(12, 'Paella Valenciana', 'Tradicional paella española elaborada con arroz, pollo, conejo, judías verdes y azafrán.', 60, 4, 'Media', 3, 58, 'paella-valenciana-spanish.jpg', NULL, 580, NULL, 1, 1, 120, '2026-10-09 14:50:45', '2026-10-09 16:10:50'),
(13, 'Ramen Tonkotsu', 'Sopa japonesa con fideos en un rico y cremoso caldo a base de hueso de cerdo, acompañado de chashu y huevo.', 120, 2, 'Difícil', 24, 92, 'ramen-tonkotsu-japanese.jpg', NULL, 650, NULL, 1, 1, 210, '2026-10-09 14:50:45', '2026-10-09 15:23:14'),
(14, 'Ceviche Peruano', 'Pescado blanco fresco marinando en jugo de limón, acompañado de cebolla morada, ají, camote y choclo.', 20, 3, 'Fácil', 13, 140, 'ceviche-peruano-peruvian.jpg', NULL, 220, NULL, 1, 1, 340, '2026-10-09 14:50:45', '2026-10-09 16:10:54'),
(15, 'Moussaka Griega', 'Pastel tradicional griego horneado a base de capas de berenjena, carne picada de cordero y salsa bechamel.', 75, 6, 'Media', 19, 72, 'moussaka-griega.jpg', NULL, 510, NULL, 1, 0, 85, '2026-10-09 14:50:45', '2026-10-09 16:13:52'),
(16, 'Butter Chicken (Murgh Makhani)', 'Pollo indio tierno en una suave, cremosa y especiada salsa de tomate y mantequilla.', 45, 4, 'Media', 20, 81, 'butter-chicken-murgh-makhani-indian.jpg', NULL, 490, NULL, 1, 1, 175, '2026-10-09 14:50:45', '2026-10-09 15:23:18'),
(17, 'Bandeja Paisa', 'Plato representativo colombiano con frijoles, arroz, carne molida, chicharrón, huevo frito, tajada de plátano y aguacate.', 90, 2, 'Media', 19, 41, 'bandeja-paisa-colombian.png', NULL, 1050, NULL, 1, 1, 410, '2026-10-09 14:50:45', '2026-10-09 16:11:06'),
(18, 'Feijoada Brasileña', 'Estofado de frijoles negros cocinado lentamente con una variedad de carnes de cerdo y res.', 150, 8, 'Media', 12, 26, 'feijoada-brasileña-brazilian.jpg', NULL, 720, NULL, 1, 0, 95, '2026-10-09 14:50:45', '2026-10-09 16:11:09'),
(19, 'Pad Thai', 'Salteado tailandés de fideos de arroz con camarones, tofu, huevo, brotes de soja y maní triturado.', 30, 2, 'Fácil', 18, 172, 'pad-thai-thai.jpg', NULL, 430, NULL, 1, 1, 290, '2026-10-09 14:50:45', '2026-10-09 16:11:10'),
(20, 'Goulash Húngaro', 'Estofado de carne de res tierno sazonado con abundante pimentón húngaro y verduras.', 90, 4, 'Media', 5, 80, 'goulash-húngaro-hungría.jpg', NULL, 480, NULL, 1, 0, 60, '2026-10-09 14:50:45', '2026-10-09 15:23:26'),
(21, 'Empanadas Argentinas de Carne', 'Jugosas empanadas horneadas rellenas de carne cortada a cuchillo, cebolla, huevo duro y aceitunas.', 50, 6, 'Media', 1, 9, 'empanadas-argentinas-de-carne-argentine.jpg', NULL, 310, NULL, 1, 1, 230, '2026-10-09 14:50:45', '2026-10-09 15:23:28'),
(22, 'Sopa Tom Yum', 'Sopa picante y ácida tradicional de Tailandia con camarones, hierba de limón, galanga y hojas de lima kaffir.', 25, 3, 'Fácil', 24, 172, 'sopa-tom-yum-thai.jpg', NULL, 180, NULL, 1, 0, 115, '2026-10-09 14:50:45', '2026-10-09 15:23:31'),
(23, 'Croissant Francés', 'Clásico pan hojaldrado francés elaborado con abundantes capas de mantequilla.', 180, 8, 'Difícil', 15, 66, 'croissant-francés-french.jpg', NULL, 340, NULL, 1, 1, 180, '2026-10-09 14:50:45', '2026-10-09 15:23:35'),
(24, 'Arepas Rellenas ', 'Arepa de maíz asada rellena de ensalada de pollo desmenuzado con mayonesa y aguacate.', 30, 3, 'Fácil', 22, 189, 'arepas-rellenas-venezuelan.jpg', NULL, 420, NULL, 1, 1, 310, '2026-10-09 14:50:45', '2026-10-09 15:55:49'),
(25, 'Crepes de Nutella y Fresas', 'Delgadas crepas francesas rellenas de crema de avellanas con chocolate y fresas frescas.', 20, 4, 'Fácil', 16, 66, 'crepes-de-nutella.jpg', NULL, 390, NULL, 1, 0, 140, '2026-10-09 14:50:45', '2026-10-09 15:32:18'),
(26, 'Schnitzel de Cerdo', 'Milanesa estilo austríaco empanada y frita hasta quedar crujiente y dorada, servida con limón.', 25, 2, 'Fácil', 5, 12, 'schnitzel-de-cerdo-austrian.jpg', NULL, 530, NULL, 1, 0, 90, '2026-10-09 14:50:45', '2026-10-09 15:23:59'),
(27, 'Adobo filipino', 'Pollo cocinado con salsa de soja, vinagre, ajo y hojas de laurel.', 70, 4, 'Media', 5, 64, 'adobo-filipino-filipino.jpg', NULL, 570, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:01'),
(28, 'Bacalao a la portuguesa', 'Bacalao con papas, cebolla, aceitunas y aceite de oliva.', 60, 4, 'Media', 13, 142, 'bacalao-a-la-portuguesa.jpg', NULL, 560, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 16:16:29'),
(29, 'Bibimbap', 'Plato coreano de arroz con verduras, carne, huevo y salsa gochujang.', 45, 4, 'Media', 3, 44, 'bibimbap-korean.jpg', NULL, 610, NULL, 1, 1, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:14'),
(30, 'Bobotie', 'Preparación sudafricana de carne especiada horneada con huevo y leche.', 80, 6, 'Media', 5, 166, 'bobotie-south-african.jpg', NULL, 590, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:17'),
(31, 'Cerdo agridulce', 'Trozos de cerdo dorados con salsa agridulce, piña y vegetales.', 50, 4, 'Media', 5, 39, 'cerdo-agridulce-chinese.jpg', NULL, 680, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:23'),
(32, 'Currywurst', 'Salchichas alemanas con salsa de tomate especiada con curry.', 35, 4, 'Fácil', 25, 3, 'currywurst-german.jpg', NULL, 480, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:25'),
(33, 'Cuscús de verduras', 'Cuscús con garbanzos y verduras cocinadas con especias aromáticas.', 45, 4, 'Fácil', 12, 114, 'cuscús-de-verduras-moroccan.jpg', NULL, 420, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:29'),
(34, 'Doro Wat', 'Estofado etíope de pollo con cebolla, especias y huevo cocido.', 100, 4, 'Difícil', 20, 62, 'doro-wat-ethiopian.jpg', NULL, 640, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:36'),
(35, 'Falafel', 'Croquetas crujientes de garbanzos y especias servidas con vegetales.', 45, 4, 'Media', 25, 102, 'falafel-lebanese.jpg', NULL, 390, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:38'),
(36, 'Gyros', 'Pan pita relleno de carne sazonada, vegetales y salsa tzatziki.', 50, 4, 'Media', 25, 72, 'gyros-greek.jpg', NULL, 640, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:42'),
(37, 'Kebab', 'Carne marinada y asada acompañada de vegetales y pan plano.', 60, 4, 'Media', 5, 181, 'kebab.jpg', NULL, 620, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 16:20:51'),
(38, 'Nasi Goreng', 'Arroz frito indonesio con pollo, vegetales, huevo y salsa de soja dulce.', 40, 4, 'Media', 3, 82, 'nasi-goreng-indonesian.jpg', NULL, 610, NULL, 1, 1, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:52'),
(39, 'Pelmeni', 'Dumplings de masa rellenos de carne y cocidos en agua.', 70, 4, 'Media', 18, 151, 'pelmeni-russian.jpg', NULL, 580, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:56:09'),
(40, 'Sushi', 'Arroz de sushi acompañado de pescado, vegetales y alga nori.', 60, 4, 'Media', 13, 92, 'sushi-japanese.jpg', NULL, 430, NULL, 1, 1, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:56'),
(41, 'Yakitori', 'Brochetas japonesas de pollo marinadas en salsa de soja dulce y asadas.', 35, 4, 'Fácil', 5, 92, 'yakitori-japanese.jpg', NULL, 320, NULL, 1, 0, 0, '2026-10-09 15:20:36', '2026-10-09 15:24:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_caracteristica`
--

CREATE TABLE `receta_caracteristica` (
  `id_receta_caracteristica` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `id_caracteristica` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_etiqueta`
--

CREATE TABLE `receta_etiqueta` (
  `id_receta` int(10) UNSIGNED NOT NULL,
  `id_etiqueta` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED DEFAULT NULL,
  `id_comentario` int(10) UNSIGNED DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `id_valoracion` int(10) UNSIGNED DEFAULT NULL,
  `reportado_por` int(10) UNSIGNED NOT NULL,
  `motivo` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('pendiente','revisado','eliminado','desestimado') NOT NULL DEFAULT 'pendiente',
  `revisado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_chatbot`
--

CREATE TABLE `sesiones_chatbot` (
  `id_sesion` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `session_key` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones_chatbot`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','chef','usuario') NOT NULL DEFAULT 'usuario',
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `id_genero` int(10) UNSIGNED DEFAULT NULL,
  `id_pais` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones`
--

CREATE TABLE `valoraciones` (
  `id_valoracion` int(10) UNSIGNED NOT NULL,
  `id_receta` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `caracteristicas`
--
ALTER TABLE `caracteristicas`
  ADD PRIMARY KEY (`id_caracteristica`),
  ADD UNIQUE KEY `uq_caracteristicas_nombre` (`nombre`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uq_categorias_nombre` (`nombre`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `idx_comentarios_receta` (`id_receta`),
  ADD KEY `idx_comentarios_usuario` (`id_usuario`);

--
-- Indices de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id_etiqueta`),
  ADD UNIQUE KEY `uq_etiquetas_nombre` (`nombre`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id_favorito`),
  ADD UNIQUE KEY `uq_favorito_usuario_receta` (`id_usuario`,`id_receta`),
  ADD KEY `idx_favoritos_receta` (`id_receta`);

--
-- Indices de la tabla `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id_genero`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD PRIMARY KEY (`id_ingrediente`),
  ADD KEY `idx_ingredientes_receta` (`id_receta`);

--
-- Indices de la tabla `mensajes_chatbot`
--
ALTER TABLE `mensajes_chatbot`
  ADD PRIMARY KEY (`id_menss_chatbot`),
  ADD KEY `idx_mensajes_chatbot_session` (`session_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `idx_notificaciones_usuario` (`id_usuario`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`id_pais`),
  ADD UNIQUE KEY `uq_paises_codigo_iso` (`codigo_iso`);

--
-- Indices de la tabla `pasos`
--
ALTER TABLE `pasos`
  ADD PRIMARY KEY (`id_pasos`),
  ADD KEY `idx_pasos_receta` (`id_receta`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id_receta`),
  ADD KEY `idx_recetas_categoria` (`id_categoria`),
  ADD KEY `idx_recetas_pais` (`id_pais`),
  ADD KEY `idx_recetas_chef` (`id_chef`);

--
-- Indices de la tabla `receta_caracteristica`
--
ALTER TABLE `receta_caracteristica`
  ADD PRIMARY KEY (`id_receta_caracteristica`),
  ADD UNIQUE KEY `uq_receta_caracteristica` (`id_receta`,`id_caracteristica`),
  ADD KEY `idx_rc_caracteristica` (`id_caracteristica`);

--
-- Indices de la tabla `receta_etiqueta`
--
ALTER TABLE `receta_etiqueta`
  ADD PRIMARY KEY (`id_receta`,`id_etiqueta`),
  ADD KEY `fk_receta_etiqueta_etiqueta` (`id_etiqueta`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `idx_reportes_receta` (`id_receta`),
  ADD KEY `idx_reportes_comentario` (`id_comentario`),
  ADD KEY `idx_reportes_usuario` (`id_usuario`),
  ADD KEY `idx_reportes_valoracion` (`id_valoracion`),
  ADD KEY `idx_reportes_reportado_por` (`reportado_por`),
  ADD KEY `idx_reportes_revisado_por` (`revisado_por`);

--
-- Indices de la tabla `sesiones_chatbot`
--
ALTER TABLE `sesiones_chatbot`
  ADD PRIMARY KEY (`id_sesion`),
  ADD UNIQUE KEY `uq_sesiones_chatbot_session_key` (`session_key`),
  ADD KEY `idx_sesiones_chatbot_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `fk_usuarios_genero` (`id_genero`),
  ADD KEY `fk_usuarios_pais` (`id_pais`);

--
-- Indices de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD PRIMARY KEY (`id_valoracion`),
  ADD KEY `idx_valoraciones_receta` (`id_receta`),
  ADD KEY `idx_valoraciones_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `caracteristicas`
--
ALTER TABLE `caracteristicas`
  MODIFY `id_caracteristica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  MODIFY `id_etiqueta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id_favorito` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `generos`
--
ALTER TABLE `generos`
  MODIFY `id_genero` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  MODIFY `id_ingrediente` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=447;

--
-- AUTO_INCREMENT de la tabla `mensajes_chatbot`
--
ALTER TABLE `mensajes_chatbot`
  MODIFY `id_menss_chatbot` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id_pais` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT de la tabla `pasos`
--
ALTER TABLE `pasos`
  MODIFY `id_pasos` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=275;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id_receta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `receta_caracteristica`
--
ALTER TABLE `receta_caracteristica`
  MODIFY `id_receta_caracteristica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_chatbot`
--
ALTER TABLE `sesiones_chatbot`
  MODIFY `id_sesion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  MODIFY `id_valoracion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_comentarios_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD CONSTRAINT `fk_ingredientes_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_chatbot`
--
ALTER TABLE `mensajes_chatbot`
  ADD CONSTRAINT `fk_mensajes_chatbot_session` FOREIGN KEY (`session_id`) REFERENCES `sesiones_chatbot` (`id_sesion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pasos`
--
ALTER TABLE `pasos`
  ADD CONSTRAINT `fk_pasos_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `fk_recetas_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recetas_chef` FOREIGN KEY (`id_chef`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recetas_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `receta_caracteristica`
--
ALTER TABLE `receta_caracteristica`
  ADD CONSTRAINT `fk_rc_caracteristica` FOREIGN KEY (`id_caracteristica`) REFERENCES `caracteristicas` (`id_caracteristica`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rc_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `receta_etiqueta`
--
ALTER TABLE `receta_etiqueta`
  ADD CONSTRAINT `fk_receta_etiqueta_etiqueta` FOREIGN KEY (`id_etiqueta`) REFERENCES `etiquetas` (`id_etiqueta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_receta_etiqueta_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `fk_reportes_comentario` FOREIGN KEY (`id_comentario`) REFERENCES `comentarios` (`id_comentario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_reportado_por` FOREIGN KEY (`reportado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_revisado_por` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportes_valoracion` FOREIGN KEY (`id_valoracion`) REFERENCES `valoraciones` (`id_valoracion`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones_chatbot`
--
ALTER TABLE `sesiones_chatbot`
  ADD CONSTRAINT `fk_sesiones_chatbot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD CONSTRAINT `fk_valoraciones_receta` FOREIGN KEY (`id_receta`) REFERENCES `recetas` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_valoraciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
