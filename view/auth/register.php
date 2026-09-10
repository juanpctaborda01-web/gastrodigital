<?php
require_once __DIR__ . '/../../config/config.php';

if (isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case ROL_ADMIN: redirect('admin');       break;
        case ROL_CHEF:  redirect('mis-recetas'); break;
        default:        redirect('dashboard');
    }
}

$pageTitle = 'Crear Cuenta';
$error     = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
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
    <div class="auth-card" style="max-width:480px">

        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="<?= BASE_URL ?>/img/GastroDigitalLogo2.png" alt="GastroDigital" height="72" class="mb-3">
            <h1 class="auth-title">Crea tu cuenta</h1>
            <p class="auth-subtitle">Únete a la comunidad de cocineros</p>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register" method="POST" novalidate id="registerForm">
            <?php csrfField(); ?>

            <!-- Nombre -->
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre completo</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-person auth-input-icon"></i>
                    <input type="text" class="form-control auth-input"
                           id="nombre" name="nombre"
                           placeholder="Tu nombre"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                           required autocomplete="name">
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope auth-input-icon"></i>
                    <input type="email" class="form-control auth-input"
                           id="email" name="email"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
                           placeholder="Mínimo 8 caracteres"
                           required autocomplete="new-password" minlength="8">
                    <button type="button" class="auth-eye-btn" id="togglePass" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                </div>
                <!-- Indicador de fortaleza -->
                <div class="mt-2" id="strengthBar" style="display:none">
                    <div class="progress" style="height:4px;border-radius:2px">
                        <div class="progress-bar" id="strengthProgress" role="progressbar"></div>
                    </div>
                    <small id="strengthText" class="text-muted d-block mt-1"></small>
                </div>
            </div>

            <!-- Confirmar contraseña -->
            <div class="mb-3">
                <label for="confirmar_password" class="form-label">Confirmar contraseña</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock-fill auth-input-icon"></i>
                    <input type="password" class="form-control auth-input auth-input--has-btn"
                           id="confirmar_password" name="confirmar_password"
                           placeholder="Repite tu contraseña"
                           required autocomplete="new-password">
                    <button type="button" class="auth-eye-btn" id="togglePass2" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
                <small id="matchMsg" class="d-block mt-1" style="display:none!important"></small>
            </div>

            <!-- Términos -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aceptaTerminos" required>
                    <label class="form-check-label small" for="aceptaTerminos">
                        Acepto los
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="fw-600">
                            términos y condiciones
                        </a>
                    </label>
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

            <button type="submit" class="btn btn-primary w-100 py-2 fw-700 fs-6" id="submitBtn" disabled>
                <i class="bi bi-person-plus me-2"></i>Crear cuenta
            </button>
        </form>

        <!-- Modal Términos -->
        <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTerminosLabel">
                            <i class="bi bi-file-text me-2"></i>Términos y Condiciones
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body small text-muted">
                        <h6 class="fw-700 text-dark mb-1">1. Uso de la plataforma</h6>
                        <p>GastroDigital es una plataforma orientada a la gestión y difusión de recetas culinarias. Al registrarse, el usuario acepta utilizarla de forma responsable y conforme a las leyes aplicables.</p>
                        <h6 class="fw-700 text-dark mb-1">2. Datos personales</h6>
                        <p>Los datos proporcionados (nombre, correo y contraseña) se usarán exclusivamente para autenticar al usuario. No se compartirán con terceros.</p>
                        <h6 class="fw-700 text-dark mb-1">3. Contenido publicado</h6>
                        <p>El usuario es responsable del contenido que publique. Queda prohibido material ofensivo, ilegal o que infrinja derechos de autor.</p>
                        <h6 class="fw-700 text-dark mb-1">4. Modificaciones</h6>
                        <p>GastroDigital se reserva el derecho de modificar estos términos. Los cambios serán notificados a través de la plataforma.</p>
                        <h6 class="fw-700 text-dark mb-1">5. Contacto</h6>
                        <p class="mb-0">Para consultas: GastroDigital73@gmail.com</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAceptarDesdeModal" data-bs-dismiss="modal">
                            <i class="bi bi-check-circle me-1"></i>Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-divider">o</div>

        <p class="text-center text-muted small mb-0">
            ¿Ya tienes cuenta?
            <a href="<?= BASE_URL ?>/login" class="fw-700">Inicia sesión</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    const passInput    = document.getElementById('password');
    const confirmInput = document.getElementById('confirmar_password');
    const checkbox     = document.getElementById('aceptaTerminos');
    const submitBtn    = document.getElementById('submitBtn');
    const bar          = document.getElementById('strengthBar');
    const progress     = document.getElementById('strengthProgress');
    const strengthText = document.getElementById('strengthText');
    const matchMsg     = document.getElementById('matchMsg');

    // Toggle contraseñas
    function togglePass(btnId, inputId, iconId) {
        document.getElementById(btnId).addEventListener('click', function () {
            const inp  = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            inp.type   = inp.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    togglePass('togglePass',  'password',           'eyeIcon1');
    togglePass('togglePass2', 'confirmar_password', 'eyeIcon2');

    // Indicador de fortaleza
    const levels = [
        { label: 'Muy débil',  color: '#ef233c', width: '20%'  },
        { label: 'Débil',      color: '#ff7b2e', width: '40%'  },
        { label: 'Regular',    color: '#ffd166', width: '60%'  },
        { label: 'Fuerte',     color: '#06d6a0', width: '80%'  },
        { label: 'Muy fuerte', color: '#06d6a0', width: '100%' },
    ];

    function passValida(v) {
        return v.length >= 8 && /[A-Z]/.test(v) && /[0-9]/.test(v);
    }

    passInput.addEventListener('input', function () {
        const v = this.value;
        if (!v) { bar.style.display = 'none'; revisarHabilitacion(); return; }
        bar.style.display = 'block';

        let score = 0;
        if (v.length >= 8)           score++;
        if (v.length >= 12)          score++;
        if (/[A-Z]/.test(v))         score++;
        if (/[0-9]/.test(v))         score++;
        if (/[^A-Za-z0-9]/.test(v))  score++;

        const lvl = levels[Math.min(score - 1, 4)] ?? levels[0];
        progress.style.width           = lvl.width;
        progress.style.backgroundColor = lvl.color;
        strengthText.textContent       = passValida(v) ? lvl.label : lvl.label + ' — falta mayúscula o número';
        strengthText.style.color       = lvl.color;

        if (confirmInput.value) checkMatch();
        revisarHabilitacion();
    });

    // Coincidencia
    function checkMatch() {
        matchMsg.style.cssText = 'display:block!important';
        if (confirmInput.value === passInput.value) {
            matchMsg.textContent = '✓ Las contraseñas coinciden';
            matchMsg.style.color = '#06d6a0';
        } else {
            matchMsg.textContent = '✗ Las contraseñas no coinciden';
            matchMsg.style.color = '#ef233c';
        }
    }

    confirmInput.addEventListener('input', function () {
        if (!this.value) { matchMsg.style.cssText = 'display:none!important'; }
        else checkMatch();
        revisarHabilitacion();
    });

    checkbox.addEventListener('change', revisarHabilitacion);

    // Habilitar submit solo si todo es correcto
    function revisarHabilitacion() {
        submitBtn.disabled = !(
            checkbox.checked &&
            passValida(passInput.value) &&
            passInput.value === confirmInput.value &&
            confirmInput.value.length > 0
        );
    }

    // Aceptar desde el modal
    document.getElementById('btnAceptarDesdeModal').addEventListener('click', function () {
        checkbox.checked = true;
        revisarHabilitacion();
    });
})();
</script>
</body>
</html>
