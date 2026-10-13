<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/usuarios.php';

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

// Obtener lista de países desde la base de datos
$userModel = new Usuario();
$paises    = $userModel->listarPaises();

$baseUrl = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | GastroDigital</title>
    <meta name="description" content="Crea tu cuenta en GastroDigital y únete a la comunidad gastronómica.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/main.css?v=2.3">
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/auth.css?v=3.0">
</head>
<body class="auth-page">
<?php include __DIR__ . '/../../assets/context_help.php'; ?>

<div class="gd-auth-wrapper">

    <!-- Panel izquierdo decorativo -->
    <div class="gd-auth-side">
        <div class="gd-auth-deco gd-auth-deco-1"></div>
        <div class="gd-auth-deco gd-auth-deco-2"></div>
        <div class="gd-auth-side-content">
            <img src="<?= $baseUrl ?>/img/logoGastroDigital.jpeg" alt="GastroDigital" class="gd-auth-side-logo">
            <h2 class="gd-auth-side-title">GastroDigital</h2>
            <p class="gd-auth-side-sub">TU COCINA DIGITAL</p>
            <p class="gd-auth-side-desc">
                Únete a miles de amantes de la cocina. Descubre, crea y comparte recetas increíbles.
            </p>
            <ul class="gd-auth-side-features">
                <li>
                    <i class="bi bi-person-fill-check"></i>
                    Perfil personalizado
                </li>
                <li>
                    <i class="bi bi-egg-fried"></i>
                    Acceso a recetas exclusivas
                </li>
                <li>
                    <i class="bi bi-trophy-fill"></i>
                    Crea como Chef y comparte
                </li>
                <li>
                    <i class="bi bi-globe2"></i>
                    Comunidad global de cocineros
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
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1 class="gd-auth-title">Crea tu cuenta</h1>
                <p class="gd-auth-subtitle">Únete a la comunidad de cocineros</p>
            </div>

            <!-- Alerta de error -->
            <?php if ($error): ?>
                <div class="gd-auth-alert gd-auth-alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form action="<?= $baseUrl ?>/index.php?route=register" method="POST" novalidate class="gd-auth-form" id="registerForm">
                <?php csrfField(); ?>

                <!-- Tipo de cuenta -->
                <div class="gd-form-section">
                    <i class="bi bi-person-badge me-1"></i> Tipo de cuenta
                </div>
                <div class="gd-role-selector">
                    <div class="gd-role-option gd-role-usuario">
                        <input type="radio" name="rol" id="rolUsuario" value="usuario" checked>
                        <label class="gd-role-card" for="rolUsuario">
                            <div class="gd-role-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="gd-role-name">Comensal</span>
                            <span class="gd-role-desc">Explora y guarda recetas</span>
                        </label>
                        <span class="gd-role-check"><i class="bi bi-check"></i></span>
                    </div>
                    <div class="gd-role-option gd-role-chef">
                        <input type="radio" name="rol" id="rolChef" value="chef">
                        <label class="gd-role-card" for="rolChef">
                            <div class="gd-role-icon">
                                <i class="bi bi-fire"></i>
                            </div>
                            <span class="gd-role-name">Chef</span>
                            <span class="gd-role-desc">Crea y publica recetas</span>
                        </label>
                        <span class="gd-role-check"><i class="bi bi-check"></i></span>
                    </div>
                </div>

                <!-- Datos personales -->
                <div class="gd-form-section">
                    <i class="bi bi-person-vcard me-1"></i> Datos personales
                </div>

                <div class="gd-input-group">
                    <label for="nombre">Nombre completo</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-person"></i></span>
                        <input type="text" id="nombre" name="nombre"
                               placeholder="Tu nombre completo"
                               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                               required autocomplete="name">
                    </div>
                </div>

                <div class="gd-input-group">
                    <label for="email">Correo electrónico</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email"
                               placeholder="tu@correo.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <div class="gd-input-row">
                    <div class="gd-input-group">
                        <label for="genero">Género <span class="text-muted small">(opcional)</span></label>
                        <div class="gd-gender-selector">
                            <?php
                            $generoSel = $_POST['genero'] ?? '';
                            $generos = [
                                ['id' => 'masculino', 'icon' => 'genero-masculino.png', 'label' => 'Masculino'],
                                ['id' => 'femenino',  'icon' => 'genero-femenino.png',  'label' => 'Femenino'],
                                ['id' => 'otro',      'icon' => 'tercer-genero.png',           'label' => 'Otro'],
                                ['id' => 'prefiero no decirlo',      'icon' => 'no.png',           'label' => 'Prefiero No Decirlo'],
                            ];
                            foreach ($generos as $g):
                                $checked = $generoSel === $g['id'] ? 'checked' : '';
                            ?>
                            <label class="gd-gender-option <?= $checked ?>">
                                <input type="radio" name="genero" value="<?= $g['id'] ?>" <?= $checked ?>>
                                <div class="gd-gender-card">
                                    <img src="<?= $baseUrl ?>/img/Icon/Icon-genero/<?= $g['icon'] ?>" 
                                         alt="<?= $g['label'] ?>" 
                                         class="gd-gender-icon">
                                    <span class="gd-gender-label"><?= $g['label'] ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="gd-input-group">
                        <label for="pais_id">País de origen</label>
                        <div class="gd-input-wrap">
                            <span class="gd-input-icon"><i class="bi bi-globe-americas"></i></span>
                            <select id="pais_id" name="pais_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($paises as $pais): ?>
                                    <option value="<?= $pais['id_pais'] ?>"
                                        <?= ($_POST['pais_id'] ?? '') == $pais['id_pais'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pais['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="gd-form-section">
                    <i class="bi bi-shield-lock me-1"></i> Seguridad
                </div>

                <div class="gd-input-group">
                    <label for="password">Contraseña</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               placeholder="Mínimo 8 caracteres"
                               required autocomplete="new-password" minlength="8">
                        <button type="button" class="gd-input-toggle" id="togglePass" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <div class="gd-strength-bar" id="strengthBar" style="display:none">
                        <div class="gd-strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="gd-strength-text" id="strengthText"></div>
                </div>

                <div class="gd-input-group">
                    <label for="confirmar_password">Confirmar contraseña</label>
                    <div class="gd-input-wrap">
                        <span class="gd-input-icon"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" id="confirmar_password" name="confirmar_password"
                               placeholder="Repite tu contraseña"
                               required autocomplete="new-password">
                        <button type="button" class="gd-input-toggle" id="togglePass2" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                    <div class="gd-match-msg" id="matchMsg" style="display:none"></div>
                </div>

                <!-- Términos -->
                <div class="gd-auth-check">
                    <input type="checkbox" id="aceptaTerminos" required>
                    <label for="aceptaTerminos">
                        Acepto los
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos">términos y condiciones</a>
                    </label>
                </div>

                <div class="gd-auth-check">
                    <input type="checkbox" id="recordarme" name="recordarme" value="1">
                    <label for="recordarme">
                        <i class="bi bi-bookmark-check me-1"></i>
                        Mantener mi sesión iniciada por 30 días
                    </label>
                </div>

                <button type="submit" class="gd-auth-submit" id="submitBtn" disabled>
                    <i class="bi bi-rocket-takeoff-fill"></i>
                    Crear mi cuenta
                </button>
            </form>

            <!-- Modal Términos y Condiciones -->
            <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-800" id="modalTerminosLabel">
                                <i class="bi bi-file-earmark-text me-2" style="color:var(--primary-dark)"></i>Términos y Condiciones
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
                            <h6 class="fw-semibold text-dark">4. Tipos de cuenta</h6>
                            <p>Al registrarte como <strong>Chef</strong>, podrás crear y publicar recetas. Como <strong>Comensal</strong>, podrás explorar, guardar favoritas y comentar. Ambos roles pueden valorar recetas.</p>
                            <h6 class="fw-semibold text-dark">5. Contacto</h6>
                            <p class="mb-0">Para cualquier consulta, puedes contactarnos a través de la plataforma.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-sm" id="btnAceptarDesdeModal" data-bs-dismiss="modal"
                                    style="background:var(--primary);color:#fff;font-weight:700">
                                <i class="bi bi-check-circle me-1"></i>Aceptar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gd-auth-divider">o</div>

            <div class="gd-auth-footer">
                ¿Ya tienes cuenta?
                <a href="<?= $baseUrl ?>/index.php?route=login">Inicia sesión</a>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
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

    // Indicador de fortaleza
    const passInput    = document.getElementById('password');
    const strengthBar  = document.getElementById('strengthBar');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    passInput.addEventListener('input', function () {
        const val = this.value;
        if (!val) { strengthBar.style.display = 'none'; strengthText.textContent = ''; return; }
        strengthBar.style.display = 'block';

        let score = 0;
        if (val.length >= 8)  score++;
        if (val.length >= 12) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { label: '🔴 Muy débil',  color: '#ef233c', width: '20%' },
            { label: '🟠 Débil',      color: '#ff7b2e', width: '40%' },
            { label: '🟡 Regular',    color: '#ffd166', width: '60%' },
            { label: '🟢 Fuerte',     color: '#06d6a0', width: '80%' },
            { label: '💪 Muy fuerte', color: '#06d6a0', width: '100%' },
        ];
        const lvl = levels[Math.min(score - 1, 4)] || levels[0];
        strengthFill.style.width           = lvl.width;
        strengthFill.style.backgroundColor = lvl.color;
        strengthText.textContent           = lvl.label;
        strengthText.style.color           = lvl.color;
    });

    // Coincidencia de contraseñas
    const confirmInput = document.getElementById('confirmar_password');
    const matchMsg     = document.getElementById('matchMsg');

    confirmInput.addEventListener('input', function () {
        if (!this.value) { matchMsg.style.display = 'none'; return; }
        matchMsg.style.display = 'block';
        if (this.value === passInput.value) {
            matchMsg.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Las contraseñas coinciden';
            matchMsg.style.color = '#06d6a0';
        } else {
            matchMsg.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Las contraseñas no coinciden';
            matchMsg.style.color = '#ef233c';
        }
    });

    // Habilitar botón solo si acepta términos
    const checkbox  = document.getElementById('aceptaTerminos');
    const submitBtn = document.getElementById('submitBtn');

    checkbox.addEventListener('change', function () {
        submitBtn.disabled = !this.checked;
    });

    // Aceptar desde modal
    document.getElementById('btnAceptarDesdeModal').addEventListener('click', function () {
        checkbox.checked   = true;
        submitBtn.disabled = false;
    });
})();
</script>
</body>
</html>
