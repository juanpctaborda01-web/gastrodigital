<?php
require_once __DIR__ . '/../../config/config.php';

// Si ya hay cookie de sesión guardada, restaurar sesión automáticamente
if (!isLoggedIn() && isset($_COOKIE['gd_remember'])) {
    $datos = recordarSesionCookie($_COOKIE['gd_remember']);
    if ($datos) {
        $_SESSION['usuario_id']     = $datos['id'];
        $_SESSION['usuario_nombre'] = $datos['nombre'];
        $_SESSION['usuario_email']  = $datos['email'];
        $_SESSION['rol']            = $datos['rol'];
    }
}

// Si ya está logueado (sesión activa o cookie restaurada), redirigir según rol
if (isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case ROL_ADMIN: redirect('admin');       break;
        case ROL_CHEF:  redirect('mis-recetas'); break;
        default:        redirect('dashboard');
    }
}

$pageTitle = 'Iniciar Sesión';
$error  = $_SESSION['error']  ?? null;
$exito  = $_SESSION['exito']  ?? null;
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['error'], $_SESSION['exito']);
unset($_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | GastroDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/main.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-2">
                <img src="<?= BASE_URL ?>/img/logoGastroDigital.jpeg" alt="GastroDigital" height="80">
            </div>
            <h1 class="auth-title">Bienvenido de vuelta</h1>
            <p class="auth-subtitle">Inicia sesión para continuar cocinando</p>
        </div>

        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($exito): ?>
            <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($exito) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario — POST va al router (index.php) no al controller directo -->
        <form action="<?= BASE_URL ?>/index.php?route=login" method="POST" novalidate>
            <?php csrfField(); ?>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0"
                           id="email" name="email"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? $loginEmail) ?>"
                           required autocomplete="email">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0 border-end-0"
                           id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            id="togglePassword" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Recordar sesión -->
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" value="1">
                    <label class="form-check-label small" for="recordarme">
                        <i class="bi bi-bookmark-check me-1 text-muted"></i>
                        Mantener mi sesión iniciada por 30 días
                    </label>
                </div>
            </div>

            <button type="submit" id="btnLogin" class="btn btn-primary w-100 py-2 fs-6">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
            </button>
        </form>

        <div class="auth-divider">o</div>

        <div class="text-center">
            <p class="mb-0 text-muted small">
                ¿No tienes cuenta?
                <a href="<?= BASE_URL ?>/index.php?route=register" class="fw-700">Regístrate gratis</a>
            </p>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
</body>
</html>
