<?php
require_once __DIR__ . '/../../config/config.php';

if (isset($_COOKIE['gd_remember'])) {
    setcookie('gd_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

// Si ya está logueado, redirigir según rol
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
$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | GastroDigital</title>
    <meta name="description" content="Inicia sesión en GastroDigital y descubre recetas de todo el mundo.">
    <link href="<?= $baseUrl ?>/assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/vendor/nunito/nunito.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/main.css?v=<?= filemtime(__DIR__ . '/../../styles/main.css') ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/auth.css?v=<?= filemtime(__DIR__ . '/../../styles/auth.css') ?>">
</head>
<body class="auth-page">
<?php include __DIR__ . '/../../assets/context_help.php'; ?>

<div class="gd-auth-wrapper">

    <!-- Panel izquierdo decorativo -->
    <div class="gd-auth-side">
        <div class="gd-auth-deco gd-auth-deco-1"></div>
        <div class="gd-auth-deco gd-auth-deco-2"></div>
        <div class="gd-auth-side-content">
            <img src="<?= $baseUrl ?>/img/logo-oficial-gastrodigital-optimized.png"
                 alt="GastroDigital"
                 class="gd-auth-side-logo"
                 loading="eager"
                 decoding="async"
                 fetchpriority="high">
            <p class="gd-auth-side-sub">TU COCINA DIGITAL</p>
            <p class="gd-auth-side-desc">
                Descubre recetas de todo el mundo, guarda tus favoritas y comparte tu pasión por la gastronomía.
            </p>
            <ul class="gd-auth-side-features">
                <li>
                    <i class="bi bi-globe-americas"></i>
                    Recetas de más de 30 países
                </li>
                <li>
                    <i class="bi bi-heart-fill"></i>
                    Guarda y organiza tus favoritas
                </li>
                <li>
                    <i class="bi bi-chat-dots-fill"></i>
                    Chefbot — asistente inteligente
                </li>
                <li>
                    <i class="bi bi-star-fill"></i>
                    Valora y comenta recetas
                </li>
            </ul>
        </div>
    </div>

    <!-- Panel derecho: formulario -->
    <div class="gd-auth-form-panel">
        <!-- Botón volver -->
        <a href="<?= $baseUrl ?>/" class="gd-auth-back-btn">
            <i class="bi bi-arrow-left"></i>
            <span>Volver al inicio</span>
        </a>

        <div class="gd-auth-form-card">
            <div class="gd-auth-header">
                <div class="gd-auth-header-icon">
                    <i class="bi bi-door-open-fill"></i>
                </div>
                <h1 class="gd-auth-title">Bienvenido de vuelta</h1>
                <p class="gd-auth-subtitle">Inicia sesión para seguir cocinando</p>
            </div>

            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="gd-auth-alert gd-auth-alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($exito): ?>
                <div class="gd-auth-alert gd-auth-alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($exito) ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form action="<?= $baseUrl ?>/index.php?route=login" method="POST" novalidate class="gd-auth-form">
                <?php csrfField(); ?>

                <div class="gd-input-group">
                    <label for="email">Correo electrónico</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email"
                               placeholder="tu@correo.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? $loginEmail) ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <div class="gd-input-group">
                    <label for="password">Contraseña</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               required autocomplete="current-password">
                        <button type="button" class="gd-input-toggle" id="togglePassword" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="gd-auth-submit" id="btnLogin">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Iniciar sesión
                </button>
            </form>

            <div class="gd-auth-divider">o</div>

            <div class="gd-auth-footer">
                ¿No tienes cuenta?
                <a href="<?= $baseUrl ?>/index.php?route=register">Regístrate gratis</a>
            </div>
        </div>
    </div>

</div>

<script defer src="<?= $baseUrl ?>/assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
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
