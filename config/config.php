<?php
// ── Configuración general ─────────────────────────────────────────────────────
define('ROOT_PATH', realpath(__DIR__ . '/..'));

function obtenerBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    if ($docRoot !== '') {
        $rootPath = str_replace('\\', '/', ROOT_PATH);
        $docRoot  = str_replace('\\', '/', $docRoot);

        if (str_starts_with($rootPath, $docRoot)) {
            $subdir = substr($rootPath, strlen($docRoot));
            $subdir = str_replace('//', '/', $subdir);
            if ($subdir !== '') {
                return $scheme . '://' . $host . '/' . ltrim($subdir, '/');
            }
        }
    }

    return $scheme . '://' . $host;
}

define('APP_NAME', 'GastroDigital');
define('BASE_URL', obtenerBaseUrl());
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('UPLOAD_URL',  BASE_URL  . '/uploads/');

// ── Roles ─────────────────────────────────────────────────────────────────────
define('ROL_ADMIN',   'admin');
define('ROL_CHEF',    'chef');
define('ROL_USUARIO', 'usuario');

// ── Seguridad (sesión segura + CSRF + rate limiting + headers) ────────────────
require_once __DIR__ . '/seguridad.php';

// ── Helpers de autenticación ──────────────────────────────────────────────────

function redirect(string $url): void {
    $path = ltrim($url, '/');
    $target = BASE_URL . '/' . $path;

    // Permite navegar sin mod_rewrite (por ejemplo, en instalaciones Apache sin AllowOverride).
    if (in_array($path, ['login', 'register'], true)) {
        $target = BASE_URL . '/index.php?route=' . rawurlencode($path);
    }

    if (str_contains($url, 'http://') || str_contains($url, 'https://')) {
        $target = $url;
    }
    header('Location: ' . $target);
    exit();
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function hasRole(string $rol): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol;
}

function requireLogin(): void {
    if (!isLoggedIn()) redirect('login');
}

function requireRole(string $rol): void {
    requireLogin();
    if (!hasRole($rol)) redirect('dashboard');
}
