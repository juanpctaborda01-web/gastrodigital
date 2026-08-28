<?php
/**
 * index.php — Punto de entrada único de GastroDigital.
 * En entornos locales sin Apache completo, sirve también como fallback para assets y rutas simples.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/RouterController.php';

$route = $_GET['route'] ?? null;
$uri = $route !== null
    ? '/' . ltrim((string) $route, '/')
    : parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uri, '/');

if ($path === '' || $path === 'index.php') {
    require_once __DIR__ . '/index_home.php';
    exit;
}

if (preg_match('#\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|ttf|woff|woff2)$#i', $path)) {
    $file = __DIR__ . '/' . $path;
    if (file_exists($file) && is_file($file)) {
        $mime = 'text/plain';
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css; charset=utf-8',
            'js'  => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'gif' => 'image/gif',
            'webp'=> 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'ttf' => 'font/ttf',
            'woff'=> 'font/woff',
            'woff2'=>'font/woff2',
        ];
        if (isset($mimes[$ext])) {
            $mime = $mimes[$ext];
        }
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}

$router = new RouterController();
$router->dispatch();
