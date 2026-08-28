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
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
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
    <div class="auth-card" style="max-width:480px">

        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-2">
                <img src="<?= BASE_URL ?>/img/logoGastroDigital.jpeg" alt="GastroDigital" height="80">
            </div>
            <h1 class="auth-title">Crea tu cuenta</h1>
            <p class="auth-subtitle">Únete a la comunidad de cocineros</p>
        </div>

        <!-- Alerta de error -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario — POST va al router (index.php) no al controller directo -->
        <form action="<?= BASE_URL ?>/index.php?route=register" method="POST" novalidate id="registerForm">
            <?php csrfField(); ?>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre completo</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0"
                           id="nombre" name="nombre"
                           placeholder="Tu nombre"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                           required autocomplete="name">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0"
                           id="email" name="email"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
                           placeholder="Mínimo 8 caracteres"
                           required autocomplete="new-password"
                           minlength="8">
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            id="togglePass" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                </div>
                <!-- Indicador de fortaleza -->
                <div class="mt-2" id="strengthBar" style="display:none">
                    <div class="progress" style="height:4px;border-radius:2px">
                        <div class="progress-bar" id="strengthProgress" role="progressbar"></div>
                    </div>
                    <small id="strengthText" class="text-muted"></small>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirmar_password" class="form-label">Confirmar contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock-fill text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0 border-end-0"
                           id="confirmar_password" name="confirmar_password"
                           placeholder="Repite tu contraseña"
                           required autocomplete="new-password">
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            id="togglePass2" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
                <div id="matchMsg" class="small mt-1" style="display:none"></div>
            </div>

            <!-- Términos y condiciones (solo en registro) -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aceptaTerminos" required>
                    <label class="form-check-label small" for="aceptaTerminos">
                        Acepto los
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="fw-semibold">
                            términos y condiciones
                        </a>
                    </label>
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

            <button type="submit" class="btn btn-primary w-100 py-2 fs-6" id="submitBtn" disabled>
                <i class="bi bi-person-plus me-2"></i>Crear cuenta
            </button>
        </form>

        <!-- Modal Términos y Condiciones -->
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
                        <h6 class="fw-semibold text-dark">1. Uso de la plataforma</h6>
                        <p>GastroDigital es una plataforma orientada a la gestión y difusión de recetas culinarias. Al registrarse, el usuario acepta utilizar la plataforma de forma responsable y conforme a las leyes aplicables.</p>

                        <h6 class="fw-semibold text-dark">2. Datos personales</h6>
                        <p>Los datos proporcionados durante el registro (nombre, correo electrónico y contraseña) serán utilizados exclusivamente para autenticar al usuario dentro de la plataforma. No se compartirán con terceros.</p>

                        <h6 class="fw-semibold text-dark">3. Contenido publicado</h6>
                        <p>El usuario es responsable del contenido que publique (recetas, imágenes, comentarios). Queda prohibido publicar material ofensivo, ilegal o que infrinja derechos de autor.</p>

                        <h6 class="fw-semibold text-dark">4. Modificaciones</h6>
                        <p>GastroDigital se reserva el derecho de modificar estos términos en cualquier momento. Los cambios serán notificados a través de la plataforma.</p>

                        <h6 class="fw-semibold text-dark">5. Contacto</h6>
                        <p class="mb-0">Para cualquier consulta relacionada con estos términos, puedes contactarnos a través de la plataforma.</p>
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

        <div class="text-center">
            <p class="mb-0 text-muted small">
                ¿Ya tienes cuenta?
                <a href="<?= BASE_URL ?>/index.php?route=login" class="fw-700">Inicia sesión</a>
            </p>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle contraseñas
    function togglePassword(btnId, inputId, iconId) {
        document.getElementById(btnId).addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            input.type  = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    togglePassword('togglePass',  'password',           'eyeIcon1');
    togglePassword('togglePass2', 'confirmar_password', 'eyeIcon2');

    // Indicador de fortaleza de contraseña
    const passInput = document.getElementById('password');
    const bar       = document.getElementById('strengthBar');
    const progress  = document.getElementById('strengthProgress');
    const text      = document.getElementById('strengthText');

    passInput.addEventListener('input', function () {
        const val = this.value;
        if (!val) { bar.style.display = 'none'; return; }
        bar.style.display = 'block';

        let score = 0;
        if (val.length >= 8)  score++;
        if (val.length >= 12) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { label: 'Muy débil',  color: '#ef233c', width: '20%' },
            { label: 'Débil',      color: '#ff7b2e', width: '40%' },
            { label: 'Regular',    color: '#ffd166', width: '60%' },
            { label: 'Fuerte',     color: '#06d6a0', width: '80%' },
            { label: 'Muy fuerte', color: '#06d6a0', width: '100%' },
        ];
        const lvl = levels[Math.min(score - 1, 4)] || levels[0];
        progress.style.width           = lvl.width;
        progress.style.backgroundColor = lvl.color;
        text.textContent               = lvl.label;
        text.style.color               = lvl.color;
    });

    // Verificar coincidencia de contraseñas
    const confirmInput = document.getElementById('confirmar_password');
    const matchMsg     = document.getElementById('matchMsg');

    confirmInput.addEventListener('input', function () {
        if (!this.value) { matchMsg.style.display = 'none'; return; }
        matchMsg.style.display = 'block';
        if (this.value === passInput.value) {
            matchMsg.textContent  = '✓ Las contraseñas coinciden';
            matchMsg.style.color  = '#06d6a0';
        } else {
            matchMsg.textContent  = '✗ Las contraseñas no coinciden';
            matchMsg.style.color  = '#ef233c';
        }
    });

    // Habilitar botón solo si acepta términos
    const checkbox  = document.getElementById('aceptaTerminos');
    const submitBtn = document.getElementById('submitBtn');

    checkbox.addEventListener('change', function () {
        submitBtn.disabled = !this.checked;
    });

    // Aceptar desde el modal marca el checkbox automáticamente
    document.getElementById('btnAceptarDesdeModal').addEventListener('click', function () {
        checkbox.checked    = true;
        submitBtn.disabled  = false;
    });
</script>
</body>
</html>
