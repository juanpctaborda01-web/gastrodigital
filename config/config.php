<?php
// ── Configuración general ─────────────────────────────────────────────────────
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Cargar conexión a BD
require_once __DIR__ . '/conexion.php';

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
define('UPLOAD_PATH',       ROOT_PATH . '/uploads/');
define('UPLOAD_URL',        BASE_URL  . '/uploads/');
define('UPLOAD_PASOS_PATH', ROOT_PATH . '/uploads/pasos/');
define('UPLOAD_PASOS_URL',  BASE_URL  . '/uploads/pasos/');

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

function urlAvatarPerfil(?string $avatar): ?string {
    $avatar = trim((string)$avatar);
    if ($avatar === '') {
        return null;
    }

    if (filter_var($avatar, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)parse_url($avatar, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $avatar : null;
    }

    $relativePath = ltrim(str_replace('\\', '/', $avatar), '/');
    if (str_starts_with($relativePath, 'uploads/')) {
        $relativePath = substr($relativePath, strlen('uploads/'));
    }
    $filename = basename($relativePath);
    if ($filename === '' || !is_file(UPLOAD_PATH . $filename)) {
        return null;
    }

    return UPLOAD_URL . rawurlencode($filename);
}

function requireLogin(): void {
    if (!isLoggedIn()) redirect('login');
}

function requireRole(string $rol): void {
    requireLogin();
    if (!hasRole($rol)) redirect('dashboard');
}
