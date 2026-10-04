<?php
/**
 * Catálogo central de categorías — GastroDigital
 * Iconos: /img/Icon/Categorias/*.png  (ruta real en disco)
 */

define('CATEGORIAS', [
    // nombre                           icono (en /img/Icon/Categorias/)   color       bg
    ['nombre' => 'Desayunos',               'icono' => 'desayuno.png',         'color' => '#e67e22', 'bg' => '#fdf3e7'],
    ['nombre' => 'Almuerzos',               'icono' => 'almuerzo.png',         'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Cenas',                   'icono' => 'cena.png',             'color' => '#6c3483', 'bg' => '#f5eef8'],
    ['nombre' => 'Entradas',                'icono' => 'aperitivo.png',        'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Sopas y Cremas',          'icono' => 'sopa.png',             'color' => '#a93226', 'bg' => '#fce4e2'],
    ['nombre' => 'Cremas y Purés',          'icono' => 'cremasypures.png',     'color' => '#a93226', 'bg' => '#fce4e2'],
    ['nombre' => 'Ensaladas',               'icono' => 'ensalada.png',         'color' => '#27ae60', 'bg' => '#eafaf1'],
    ['nombre' => 'Carnes',                  'icono' => 'carne.png',            'color' => '#6d3b1f', 'bg' => '#f5ede6'],
    ['nombre' => 'Pollo',                   'icono' => 'pollo-asado.png',      'color' => '#d4ac0d', 'bg' => '#fef9e7'],
    ['nombre' => 'Pescados y Mariscos',     'icono' => 'pescado.png',          'color' => '#1a6e8a', 'bg' => '#e8f6fa'],
    ['nombre' => 'Pastas',                  'icono' => 'pasta.png',            'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Arroces',                 'icono' => 'arroz.png',            'color' => '#b7950b', 'bg' => '#fef5d4'],
    ['nombre' => 'Comida Rápida',           'icono' => 'comida-rapida.png',    'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Comida Callejera',        'icono' => 'comida-callejera.png', 'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Comida Típica Colombiana','icono' => 'plato-principal.png',  'color' => '#1a5276', 'bg' => '#d6eaf8'],
    ['nombre' => 'Platos Principales',      'icono' => 'plato-principal.png',  'color' => '#1a5276', 'bg' => '#d6eaf8'],
    ['nombre' => 'Postres',                 'icono' => 'postres.png',          'color' => '#8e44ad', 'bg' => '#f4ecf7'],
    ['nombre' => 'Panadería',               'icono' => 'panaderia.png',        'color' => '#a04000', 'bg' => '#fae5d3'],
    ['nombre' => 'Repostería',              'icono' => 'reposteria.png',       'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Bebidas',                 'icono' => 'bebidas.png',          'color' => '#2980b9', 'bg' => '#d6eaf8'],
    ['nombre' => 'Cócteles',                'icono' => 'cocteles.png',         'color' => '#2980b9', 'bg' => '#d6eaf8'],
    ['nombre' => 'Vegetarianas',            'icono' => 'ensalada.png',         'color' => '#27ae60', 'bg' => '#eafaf1'],
    ['nombre' => 'Veganas',                 'icono' => 'legumbres.png',        'color' => '#1e8449', 'bg' => '#d5f5e3'],
    ['nombre' => 'Saludables',              'icono' => 'ensalada.png',         'color' => '#148f77', 'bg' => '#d1f2eb'],
    ['nombre' => 'Gourmet',                 'icono' => 'parrillada.png',       'color' => '#7d6608', 'bg' => '#fef9e7'],
    ['nombre' => 'Parrilladas',             'icono' => 'parrillada.png',       'color' => '#7d6608', 'bg' => '#fef9e7'],
    ['nombre' => 'Sándwiches',              'icono' => 'sandwich.png',         'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Snacks',                  'icono' => 'snacks.png',           'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Huevos',                  'icono' => 'huevos.png',           'color' => '#e67e22', 'bg' => '#fdf3e7'],
    ['nombre' => 'Papas',                   'icono' => 'papas.png',            'color' => '#b7950b', 'bg' => '#fef5d4'],
    ['nombre' => 'Salsas',                  'icono' => 'salsas.png',           'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Legumbres',               'icono' => 'legumbres.png',        'color' => '#1e8449', 'bg' => '#d5f5e3'],
    ['nombre' => 'Recetas Rápidas',         'icono' => 'recetas-rapidas.png',  'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Navidad',                 'icono' => 'navidad.png',          'color' => '#c0392b', 'bg' => '#fdecea'],
]);

// ── Mapas derivados (nombre → color / icono) ──────────────────────────────────
$CATEGORIAS_COLOR = [];
$CATEGORIAS_ICONO = [];
foreach (CATEGORIAS as $c) {
    $key = strtolower($c['nombre']);
    $CATEGORIAS_COLOR[$key] = $c['color'];
    $CATEGORIAS_ICONO[$key] = $c['icono'];
}

/**
 * Devuelve el nombre del archivo PNG para una categoría.
 * Ruta base: BASE_URL . '/img/Icon/Categorias/'
 */
function getCategoriaIcono(string $nombre): string {
    global $CATEGORIAS_ICONO;
    $key = strtolower(trim($nombre));
    return $CATEGORIAS_ICONO[$key] ?? 'plato-principal.png';
}

/**
 * Devuelve el color hex de una categoría.
 */
function getCategoriaColor(string $nombre): string {
    global $CATEGORIAS_COLOR;
    $key = strtolower(trim($nombre));
    return $CATEGORIAS_COLOR[$key] ?? '#e67e22';
}
