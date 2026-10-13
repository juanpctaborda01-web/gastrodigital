<?php
/**
 * GastroDigital — Capa de seguridad centralizada
 * Incluir SOLO desde config.php (ya está cargado en toda la app)
 */

// ── 1. Configuración segura de sesión ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly',  '1');   // JS no puede leer la cookie
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode',  '1');   // IDs de sesión solo los genera el servidor
    ini_set('session.gc_maxlifetime',   '3600'); // 1 hora de inactividad
    session_start();
}

// Regenerar ID de sesión en cada login (previene session fixation)
function regenerarSesion(): void {
    session_regenerate_id(true);
}

// ── 2. CSRF ───────────────────────────────────────────────────────────────────

/** Genera (o reutiliza) el token CSRF de la sesión */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Imprime el campo hidden con el token CSRF */
function csrfField(): void {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/** Verifica el token CSRF del POST — termina la ejecución si falla */
function verificarCsrf(): void {
    $tokenPost    = $_POST['csrf_token']      ?? '';
    $tokenSesion  = $_SESSION['csrf_token']   ?? '';
    if (!$tokenPost || !hash_equals($tokenSesion, $tokenPost)) {
        http_response_code(403);
        die('Petición no válida (CSRF). <a href="' . BASE_URL . '">Volver</a>');
    }
}

// ── 3. Rate limiting en login (basado en sesión + tiempo) ────────────────────
define('LOGIN_MAX_INTENTOS',  5);
define('LOGIN_BLOQUEO_SEG',   300); // 5 minutos

function registrarIntentoLogin(): void {
    if (!isset($_SESSION['login_intentos'])) {
        $_SESSION['login_intentos']  = 0;
        $_SESSION['login_primer_int'] = time();
    }
    // Reiniciar contador si pasó el tiempo de bloqueo
    if (time() - $_SESSION['login_primer_int'] > LOGIN_BLOQUEO_SEG) {
        $_SESSION['login_intentos']   = 0;
        $_SESSION['login_primer_int'] = time();
    }
    $_SESSION['login_intentos']++;
}

function loginBloqueado(): bool {
    if (!isset($_SESSION['login_intentos'])) return false;
    $dentroVentana = (time() - ($_SESSION['login_primer_int'] ?? 0)) < LOGIN_BLOQUEO_SEG;
    return $dentroVentana && $_SESSION['login_intentos'] >= LOGIN_MAX_INTENTOS;
}

function resetIntentoLogin(): void {
    unset($_SESSION['login_intentos'], $_SESSION['login_primer_int']);
}

function tiempoRestanteBloqueo(): int {
    $transcurrido = time() - ($_SESSION['login_primer_int'] ?? 0);
    return max(0, LOGIN_BLOQUEO_SEG - $transcurrido);
}

// ── 4. Validación y sanitización de entrada ──────────────────────────────────

/** Limpia string: elimina tags, espacios extra */
function limpiarTexto(string $valor, int $maxLen = 255): string {
    return mb_substr(trim(strip_tags($valor)), 0, $maxLen);
}

/** Valida email */
function validarEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Valida fortaleza de contraseña (mín. 8 chars, 1 mayúscula, 1 número) */
function validarPassword(string $pass): array {
    $errores = [];
    if (strlen($pass) < 8)              $errores[] = 'Mínimo 8 caracteres.';
    if (!preg_match('/[A-Z]/', $pass))  $errores[] = 'Al menos una mayúscula.';
    if (!preg_match('/[0-9]/', $pass))  $errores[] = 'Al menos un número.';
    return $errores;
}

// ── 5. Validación segura de uploads ──────────────────────────────────────────
define('UPLOAD_MAX_BYTES',  3 * 1024 * 1024); // 3 MB
define('UPLOAD_MIME_ALLOW', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_EXT_ALLOW',  ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Valida y mueve un archivo de imagen subido.
 * Retorna el nombre del archivo guardado, o null si falla.
 */
function procesarImagenUpload(array $file, string $destino): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK)           return null;
    if ($file['size']  >  UPLOAD_MAX_BYTES)         return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_EXT_ALLOW))          return null;

    // Verificar MIME real con finfo (no confiar en la extensión)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, UPLOAD_MIME_ALLOW))        return null;

    // Verificar que sea imagen válida con getimagesize
    if (!getimagesize($file['tmp_name']))            return null;

    $nombre = uniqid('img_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destino . $nombre)) return null;

    return $nombre;
}

// ── 6. Headers de seguridad HTTP ─────────────────────────────────────────────
function enviarHeadersSeguridad(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // CSP: permite Bootstrap CDN, Google Fonts e íconos
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
           "img-src 'self' data: blob: http: https:; " .
           "connect-src 'self';");
}

enviarHeadersSeguridad();

// ── 7. Cookie "Recordar sesión" ───────────────────────────────────────────────
define('REMEMBER_COOKIE_NAME',   'gd_remember');
define('REMEMBER_COOKIE_DIAS',   30);
define('REMEMBER_COOKIE_SECRET', 'gd_s3cr3t_k3y_2024'); // clave HMAC interna

/**
 * Guarda una cookie firmada con los datos mínimos del usuario.
 * NO guarda la contraseña — solo id, nombre, email y rol.
 */
function guardarCookieRecordar(array $usuario): void {
    $payload = base64_encode(json_encode([
        'id'     => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email'  => $usuario['email'],
        'rol'    => $usuario['rol'],
        'exp'    => time() + (REMEMBER_COOKIE_DIAS * 86400),
    ]));

    $firma  = hash_hmac('sha256', $payload, REMEMBER_COOKIE_SECRET);
    $valor  = $payload . '.' . $firma;

    setcookie(
        REMEMBER_COOKIE_NAME,
        $valor,
        time() + (REMEMBER_COOKIE_DIAS * 86400),
        '/',
        '',       // dominio vacío = dominio actual
        false,    // secure: cambiar a true en producción con HTTPS
        true      // httpOnly: JS no puede leerla
    );
}

/**
 * Verifica y decodifica la cookie de sesión recordada.
 * Retorna los datos del usuario o null si la cookie es inválida/expirada.
 */
function recordarSesionCookie(string $valor): ?array {
    $partes = explode('.', $valor, 2);
    if (count($partes) !== 2) return null;

    [$payload, $firma] = $partes;

    // Verificar firma
    $firmaEsperada = hash_hmac('sha256', $payload, REMEMBER_COOKIE_SECRET);
    if (!hash_equals($firmaEsperada, $firma)) return null;

    $datos = json_decode(base64_decode($payload), true);
    if (!$datos) return null;

    // Verificar expiración
    if (($datos['exp'] ?? 0) < time()) {
        setcookie(REMEMBER_COOKIE_NAME, '', time() - 3600, '/', '', false, true);
        return null;
    }

    return $datos;
}
