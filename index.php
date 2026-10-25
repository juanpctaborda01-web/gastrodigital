<?php
/**
 * index.php — Punto de entrada único de GastroDigital.
 * En entornos locales sin Apache completo, sirve también como fallback para assets y rutas simples.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/RouterController.php';

$route = $_GET['route'] ?? null;
if ($route !== null && !is_string($route)) {
    http_response_code(400);
    exit('Ruta no válida.');
}

$uri = $route !== null
    ? '/' . ltrim($route, '/')
    : parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$path = trim($uri, '/');

if ($path === '' || $path === 'index.php') {
    require_once __DIR__ . '/index_home.php';
    exit;
}

$mimes = [
    'css' => 'text/css; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'ttf' => 'font/ttf',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
];
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if (isset($mimes[$extension])) {
    $root = realpath(__DIR__);
    if ($root === false) {
        http_response_code(500);
        exit('No se pudo resolver el directorio de la aplicación.');
    }

    $file = realpath(__DIR__ . DIRECTORY_SEPARATOR . $path);
    $rootPrefix = $root . DIRECTORY_SEPARATOR;

    if ($file === false || !is_file($file) || !str_starts_with($file, $rootPrefix)) {
        http_response_code(404);
        exit('Recurso no encontrado.');
    }

    header('Content-Type: ' . $mimes[$extension]);
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

$router = new RouterController();
$router->dispatch();
