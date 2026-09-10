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
    $path   = ltrim($url, '/');
    $target = BASE_URL . '/' . $path;

    if (str_contains($url, 'http://') || str_contains($url, 'https://')) {
        $target = $url;
    }
    header('Location: ' . $target);
    exit();
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

/**
 * Devuelve el HTML del avatar de cualquier usuario.
 * Si tiene foto de perfil subida la muestra; si no, muestra la inicial con fondo de color.
 *
 * @param string      $nombre   Nombre del usuario (para la inicial de respaldo)
 * @param string|null $avatar   Nombre del archivo en /uploads/ (campo avatar de la BD)
 * @param int         $size     Tamaño en px (por defecto 36)
 * @param string      $extraCss Clases CSS adicionales
 */
function avatarHtml(string $nombre, ?string $avatar, int $size = 36, string $extraCss = ''): string
{
    $style = "width:{$size}px;height:{$size}px;flex-shrink:0;";
    $clase = 'rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0';

    if ($avatar && file_exists(UPLOAD_PATH . $avatar)) {
        $url = htmlspecialchars(UPLOAD_URL . $avatar, ENT_QUOTES);
        $alt = htmlspecialchars($nombre, ENT_QUOTES);
        return "<img src=\"{$url}\" alt=\"{$alt}\" class=\"{$clase} {$extraCss}\""
             . " style=\"{$style}object-fit:cover;\" loading=\"lazy\">";
    }

    // Respaldo: inicial sobre fondo degradado
    $inicial  = strtoupper(mb_substr($nombre, 0, 1));
    $fontSize = max(10, (int)($size * 0.4));
    return "<div class=\"{$clase} text-white fw-700 {$extraCss}\""
         . " style=\"{$style}background:linear-gradient(135deg,var(--secondary),var(--primary));"
         . "font-size:{$fontSize}px\">{$inicial}</div>";
}

/**
 * Genera el HTML del avatar del usuario en sesión.
 * Si tiene imagen de perfil la muestra; si no, muestra sus iniciales.
 *
 * @param int    $size  Tamaño en px (ancho y alto)
 * @param string $class Clases CSS adicionales
 */
function avatarSesion(int $size = 40, string $class = ''): string {
    $nombre = $_SESSION['usuario_nombre'] ?? 'U';
    $avatar = $_SESSION['usuario_avatar'] ?? null;
    return avatarHtml($nombre, $avatar, $size, $class);
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
