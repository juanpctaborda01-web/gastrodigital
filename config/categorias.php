<?php
/**
 * Catálogo central de categorías — GastroDigital
 * Los nombres deben coincidir EXACTAMENTE con los de la tabla `categorias` en la BD.
 * El campo 'img' apunta a /img/Icon/<archivo>.
 */

define('CATEGORIAS', [
    ['nombre' => 'Almuerzos',               'img' => 'armuerzo.png',        'icono' => 'bi-sun',             'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Arroces',                 'img' => 'arroz.png',           'icono' => 'bi-grid-3x3',        'color' => '#b7950b', 'bg' => '#fef5d4'],
    ['nombre' => 'Bebidas',                 'img' => 'bebidas.png',         'icono' => 'bi-cup-straw',       'color' => '#2980b9', 'bg' => '#d6eaf8'],
    ['nombre' => 'Carnes',                  'img' => 'carne.png',           'icono' => 'bi-fire',            'color' => '#6d3b1f', 'bg' => '#f5ede6'],
    ['nombre' => 'Cenas',                   'img' => 'cena.png',            'icono' => 'bi-moon-stars',      'color' => '#6c3483', 'bg' => '#f5eef8'],
    ['nombre' => 'Cócteles',               'img' => 'cocteles.png',        'icono' => 'bi-cup-straw',       'color' => '#2980b9', 'bg' => '#d6eaf8'],
    ['nombre' => 'Comida callejera',        'img' => 'comida-callejera.png','icono' => 'bi-lightning-fill',  'color' => '#e67e22', 'bg' => '#fdf3e7'],
    ['nombre' => 'Comida china',            'img' => 'comida-china.png',    'icono' => 'bi-globe',           'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Comida rápida',          'img' => 'comida-rapida.png',   'icono' => 'bi-lightning-fill',  'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Desayunos',               'img' => 'desayuno.png',        'icono' => 'bi-brightness-high', 'color' => '#e67e22', 'bg' => '#fdf3e7'],
    ['nombre' => 'Ensaladas',               'img' => 'ensalada.png',        'icono' => 'bi-leaf',            'color' => '#27ae60', 'bg' => '#eafaf1'],
    ['nombre' => 'Entradas',                'img' => 'aperitivo.png',       'icono' => 'bi-stars',           'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Gourmet',                 'img' => 'plato-principal.png', 'icono' => 'bi-award',           'color' => '#7d6608', 'bg' => '#fef9e7'],
    ['nombre' => 'Huevos',                  'img' => 'huevos.png',          'icono' => 'bi-egg-fried',       'color' => '#d4ac0d', 'bg' => '#fef9e7'],
    ['nombre' => 'Legumbres',               'img' => 'legumbres.png',       'icono' => 'bi-tree',            'color' => '#1e8449', 'bg' => '#d5f5e3'],
    ['nombre' => 'Navidad',                 'img' => 'navidena.png',        'icono' => 'bi-snow',            'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Panadería',              'img' => 'panaderia.png',       'icono' => 'bi-shop',            'color' => '#a04000', 'bg' => '#fae5d3'],
    ['nombre' => 'Papas',                   'img' => 'papas.png',           'icono' => 'bi-grid-fill',       'color' => '#d4ac0d', 'bg' => '#fef9e7'],
    ['nombre' => 'Parrilladas',             'img' => 'parrilla.png',        'icono' => 'bi-fire',            'color' => '#6d3b1f', 'bg' => '#f5ede6'],
    ['nombre' => 'Pastas',                  'img' => 'pasta.png',           'icono' => 'bi-circle-square',   'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Pescados y mariscos',     'img' => 'pescado.png',         'icono' => 'bi-water',           'color' => '#1a6e8a', 'bg' => '#e8f6fa'],
    ['nombre' => 'Platos principales',      'img' => 'plato-principal.png', 'icono' => 'bi-award',           'color' => '#7d6608', 'bg' => '#fef9e7'],
    ['nombre' => 'Pollo',                   'img' => 'pollo-asado.png',     'icono' => 'bi-egg-fried',       'color' => '#d4ac0d', 'bg' => '#fef9e7'],
    ['nombre' => 'Postres',                 'img' => 'pastel.png',          'icono' => 'bi-cake2',           'color' => '#8e44ad', 'bg' => '#f4ecf7'],
    ['nombre' => 'Repostería',             'img' => 'reposteria.png',      'icono' => 'bi-gift',            'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Salsas',                  'img' => 'salsas.png',          'icono' => 'bi-droplet-fill',    'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Sándwiches',             'img' => 'sandwich.png',        'icono' => 'bi-bag',             'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Snacks',                  'img' => 'snacks.png',          'icono' => 'bi-bag',             'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Sopas y cremas',          'img' => 'sopa.png',            'icono' => 'bi-cup-hot-fill',    'color' => '#a93226', 'bg' => '#fce4e2'],
]);

// Mapa nombre → color (para tarjetas de receta)
$CATEGORIAS_COLOR = [];
foreach (CATEGORIAS as $c) {
    $CATEGORIAS_COLOR[strtolower($c['nombre'])] = $c['color'];
}
