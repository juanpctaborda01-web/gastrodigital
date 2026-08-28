<?php
/**
 * Catálogo central de categorías — GastroDigital
 * Importar en cualquier vista/controller con:
 *   require_once __DIR__ . '/../config/categorias.php';  (desde controllers/)
 *   require_once __DIR__ . '/../../config/categorias.php'; (desde view/)
 */

define('CATEGORIAS', [
    // nombre                        icono                       color     bg
    ['nombre' => 'Desayunos',               'icono' => 'bi-brightness-high',   'color' => '#e67e22', 'bg' => '#fdf3e7'],
    ['nombre' => 'Almuerzos',               'icono' => 'bi-sun',               'color' => '#d35400', 'bg' => '#fdebd0'],
    ['nombre' => 'Cenas',                   'icono' => 'bi-moon-stars',        'color' => '#6c3483', 'bg' => '#f5eef8'],
    ['nombre' => 'Entradas',                'icono' => 'bi-stars',             'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Sopas y Cremas',          'icono' => 'bi-cup-hot-fill',      'color' => '#a93226', 'bg' => '#fce4e2'],
    ['nombre' => 'Ensaladas',               'icono' => 'bi-leaf',              'color' => '#27ae60', 'bg' => '#eafaf1'],
    ['nombre' => 'Carnes',                  'icono' => 'bi-fire',              'color' => '#6d3b1f', 'bg' => '#f5ede6'],
    ['nombre' => 'Pollo',                   'icono' => 'bi-egg-fried',         'color' => '#d4ac0d', 'bg' => '#fef9e7'],
    ['nombre' => 'Pescados y Mariscos',     'icono' => 'bi-water',             'color' => '#1a6e8a', 'bg' => '#e8f6fa'],
    ['nombre' => 'Pastas',                  'icono' => 'bi-circle-square',     'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Arroces',                 'icono' => 'bi-grid-3x3',          'color' => '#b7950b', 'bg' => '#fef5d4'],
    ['nombre' => 'Comida Rápida',           'icono' => 'bi-lightning-fill',    'color' => '#e74c3c', 'bg' => '#fce8e6'],
    ['nombre' => 'Comida Típica Colombiana','icono' => 'bi-flag',              'color' => '#1a5276', 'bg' => '#d6eaf8'],
    ['nombre' => 'Postres',                 'icono' => 'bi-cake2',             'color' => '#8e44ad', 'bg' => '#f4ecf7'],
    ['nombre' => 'Panadería',               'icono' => 'bi-shop',              'color' => '#a04000', 'bg' => '#fae5d3'],
    ['nombre' => 'Repostería',              'icono' => 'bi-gift',              'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Bebidas',                 'icono' => 'bi-cup-straw',         'color' => '#2980b9', 'bg' => '#d6eaf8'],
    ['nombre' => 'Vegetarianas',            'icono' => 'bi-heart-fill',        'color' => '#27ae60', 'bg' => '#eafaf1'],
    ['nombre' => 'Veganas',                 'icono' => 'bi-tree',              'color' => '#1e8449', 'bg' => '#d5f5e3'],
    ['nombre' => 'Saludables',              'icono' => 'bi-activity',          'color' => '#148f77', 'bg' => '#d1f2eb'],
    ['nombre' => 'Gourmet',                 'icono' => 'bi-award',             'color' => '#7d6608', 'bg' => '#fef9e7'],
    ['nombre' => 'Navidad',                 'icono' => 'bi-snow',              'color' => '#c0392b', 'bg' => '#fdecea'],
    ['nombre' => 'Snacks',                  'icono' => 'bi-bag',               'color' => '#d35400', 'bg' => '#fdebd0'],
]);

// Mapa nombre → color (para tarjetas de receta)
$CATEGORIAS_COLOR = [];
foreach (CATEGORIAS as $c) {
    $CATEGORIAS_COLOR[strtolower($c['nombre'])] = $c['color'];
}
