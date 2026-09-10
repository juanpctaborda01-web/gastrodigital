<?php
require_once __DIR__ . '/../../config/config.php';

// Restaurar sesión desde cookie "recordarme"
if (!isLoggedIn() && isset($_COOKIE['gd_remember'])) {
    $datos = recordarSesionCookie($_COOKIE['gd_remember']);
    if ($datos) {
        $_SESSION['usuario_id']     = $datos['id'];
        $_SESSION['usuario_nombre'] = $datos['nombre'];
        $_SESSION['usuario_email']  = $datos['email'];
        $_SESSION['rol']            = $datos['rol'];
    }
}

if (isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case ROL_ADMIN: redirect('admin');       break;
        case ROL_CHEF:  redirect('mis-recetas'); break;
        default:        redirect('dashboard');
    }
}

$pageTitle  = 'Iniciar Sesión';
$error      = $_SESSION['error']      ?? null;
$exito      = $_SESSION['exito']      ?? null;
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['error'], $_SESSION['exito'], $_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | GastroDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/main.css?v=<?= filemtime(__DIR__ . '/../../styles/main.css') ?>">
</head>
<body class="auth-page">

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="<?= BASE_URL ?>/img/GastroDigitalLogo2.png" alt="GastroDigital" height="72" class="mb-3">
            <h1 class="auth-title">Bienvenido de vuelta</h1>
            <p class="auth-subtitle">Inicia sesión para continuar cocinando</p>
        </div>

        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($exito) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/login" method="POST" novalidate>
            <?php csrfField(); ?>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope auth-input-icon"></i>
                    <input type="email" class="form-control auth-input"
                           id="email" name="email"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? $loginEmail) ?>"
                           required autocomplete="email">
                </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock auth-input-icon"></i>
                    <input type="password" class="form-control auth-input auth-input--has-btn"
                           id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" class="auth-eye-btn" id="togglePassword" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Recordarme -->
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" value="1">
                    <label class="form-check-label small" for="recordarme">
                        Mantener mi sesión iniciada por 30 días
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-700 fs-6">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
            </button>
        </form>

        <div class="auth-divider">o</div>

        <p class="text-center text-muted small mb-0">
            ¿No tienes cuenta?
            <a href="<?= BASE_URL ?>/register" class="fw-700">Regístrate gratis</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
});
</script>
</body>
</html>
