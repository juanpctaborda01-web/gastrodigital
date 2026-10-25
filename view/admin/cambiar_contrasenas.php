<?php
/**
 * view/admin/cambiar_contrasenas.php
 * Solo accesible por administradores.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireRole(ROL_ADMIN);

$pageTitle = 'Cambiar contraseñas';
$userModel = new Usuario();
$baseUrl   = rtrim(BASE_URL, '/');

// ── Leer todos los usuarios ───────────────────────────────────────────────────
$todos = $userModel->listarTodos() ?: [];

// Normalizar PK: puede ser 'id' o 'id_usuario' según el schema
foreach ($todos as &$u) {
    if (!isset($u['id'])) {
        $u['id'] = $u['id_usuario'] ?? 0;
    }
}
unset($u);

// ── Usuario seleccionado ──────────────────────────────────────────────────────
$filtroId   = (int)($_GET['id'] ?? 0);
$usuarioSel = null;
if ($filtroId) {
    foreach ($todos as $u) {
        if ((int)$u['id'] === $filtroId) { $usuarioSel = $u; break; }
    }
}

// ── Mensajes flash ────────────────────────────────────────────────────────────
$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

// URL base para los links de la lista
$routeBase = $baseUrl . '/index.php?route=admin/cambiar-contrasenas';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>
<?php
$topbarBadge      = 'Admin';
$topbarBreadcrumb = [
    ['label' => 'Inicio',   'href' => $baseUrl . '/index.php?route=admin'],
    ['label' => 'Usuarios', 'href' => $baseUrl . '/index.php?route=admin/usuarios'],
    ['label' => 'Cambiar contraseñas'],
];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5">
<div class="container" style="max-width:860px">

    <?php
    $phTag     = 'Seguridad';
    $phTagImg  = BASE_URL . '/img/Icon/ICON-Sobrantes/cheque.png';
    $phTitle   = 'Cambiar contraseñas';
    $phSub     = 'El administrador puede cambiar la contraseña de cualquier usuario.';
    $phArtImg  = BASE_URL . '/img/Icon/ICON-Sobrantes/profile.gif';
    include __DIR__ . '/../../assets/page_header.php';
    ?>

    <!-- Alertas flash -->
    <?php if ($exito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── Columna izquierda: lista de usuarios ── -->
        <div class="col-md-5">
            <div class="card panel-card">
                <div class="card-header">
                    <i class="bi bi-people me-1"></i>
                    Usuarios del sistema
                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size:.65rem">
                        <?= count($todos) ?>
                    </span>
                </div>
                <div class="card-body p-0" style="max-height:420px;overflow-y:auto">
                    <?php if (empty($todos)): ?>
                    <p class="text-muted small p-3 mb-0">No hay usuarios registrados.</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todos as $u):
                            $uid      = (int)$u['id'];
                            $activo   = ($uid === $filtroId);
                            $rolBadge = match($u['rol'] ?? '') {
                                'admin'   => 'danger',
                                'chef'    => 'warning text-dark',
                                default   => 'secondary',
                            };
                        ?>
                        <a href="<?= $routeBase ?>&amp;id=<?= $uid ?>"
                           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-3
                                  <?= $activo ? 'active' : '' ?>">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center
                                        rounded-circle fw-800 small"
                                 style="width:34px;height:34px;
                                        background:<?= $activo ? '#fff' : 'var(--cream-2)' ?>;
                                        color:<?= $activo ? 'var(--orange)' : 'var(--text)' ?>;
                                        flex-shrink:0">
                                <?= strtoupper(substr($u['nombre'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-700 small text-truncate">
                                    <?= htmlspecialchars($u['nombre'] ?? '—') ?>
                                </div>
                                <div class="text-truncate" style="font-size:.7rem;opacity:.65">
                                    <?= htmlspecialchars($u['email'] ?? '') ?>
                                </div>
                            </div>
                            <span class="badge bg-<?= $rolBadge ?> rounded-pill"
                                  style="font-size:.6rem;flex-shrink:0">
                                <?= strtoupper($u['rol'] ?? 'USR') ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Columna derecha: formulario ── -->
        <div class="col-md-7">
            <?php if (!$filtroId): ?>
            <!-- Sin usuario seleccionado -->
            <div class="card panel-card h-100">
                <div class="card-body d-flex flex-column align-items-center
                             justify-content-center text-muted py-5">
                    <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:.25"></i>
                    <p class="fw-600 mt-3 mb-1">Selecciona un usuario</p>
                    <small>Haz clic en un nombre de la lista</small>
                </div>
            </div>

            <?php elseif (!$usuarioSel): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Usuario no encontrado (ID <?= $filtroId ?>).
                <a href="<?= $routeBase ?>" class="alert-link ms-2">Ver lista</a>
            </div>

            <?php else: ?>
            <!-- Formulario de cambio -->
            <div class="card panel-card">
                <div class="card-header">
                    <i class="bi bi-key me-1"></i>
                    Nueva contraseña para
                    <strong><?= htmlspecialchars($usuarioSel['nombre'] ?? '') ?></strong>
                </div>
                <div class="card-body">

                    <!-- Info del usuario -->
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded"
                         style="background:var(--cream-2)">
                        <div class="d-flex align-items-center justify-content-center
                                    rounded-circle fw-800 flex-shrink-0"
                             style="width:46px;height:46px;background:var(--orange);
                                    color:#fff;font-size:1.2rem">
                            <?= strtoupper(substr($usuarioSel['nombre'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-800"><?= htmlspecialchars($usuarioSel['nombre'] ?? '') ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($usuarioSel['email'] ?? '') ?></div>
                            <span class="badge bg-<?= match($usuarioSel['rol'] ?? '') {
                                'admin' => 'danger', 'chef' => 'warning text-dark', default => 'secondary'
                            } ?> rounded-pill" style="font-size:.62rem">
                                <?= strtoupper($usuarioSel['rol'] ?? 'USUARIO') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form method="POST"
                          action="<?= $baseUrl ?>/index.php?route=admin/cambiar-contrasenas-post"
                          id="formCambiarPass"
                          novalidate>
                        <?php csrfField(); ?>
                        <input type="hidden" name="usuario_id" value="<?= $filtroId ?>">

                        <!-- Nueva contraseña -->
                        <div class="mb-3">
                            <label class="form-label fw-700" for="nueva_password">
                                Nueva contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       id="nueva_password"
                                       name="nueva_password"
                                       class="form-control"
                                       placeholder="Mínimo 8 caracteres"
                                       required
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleVer('nueva_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="mt-2" style="height:4px;background:var(--cream-3);border-radius:4px">
                                <div id="strengthBar"
                                     style="height:4px;border-radius:4px;width:0;transition:width .3s,background .3s"></div>
                            </div>
                            <small id="strengthTxt" class="text-muted"></small>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="mb-4">
                            <label class="form-label fw-700" for="confirmar_password">
                                Confirmar contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       id="confirmar_password"
                                       name="confirmar_password"
                                       class="form-control"
                                       placeholder="Repite la contraseña"
                                       required
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleVer('confirmar_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small id="matchTxt" class="d-block mt-1"></small>
                        </div>

                        <!-- Requisitos -->
                        <ul class="small ps-4 mb-4" style="line-height:1.9;color:var(--text-m)">
                            <li id="r-len">Al menos 8 caracteres</li>
                            <li id="r-upper">Al menos 1 mayúscula</li>
                            <li id="r-num">Al menos 1 número</li>
                        </ul>

                        <!-- Botones -->
                        <div class="d-flex gap-2">
                            <a href="<?= $routeBase ?>"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary flex-grow-1"
                                    id="btnSubmit">
                                <i class="bi bi-key me-2"></i>Guardar contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row -->
</div><!-- /container -->
</main>

<script>
(function () {
    // Mostrar / ocultar contraseña
    window.toggleVer = function (id, btn) {
        const inp = document.getElementById(id);
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    };

    const inp1 = document.getElementById('nueva_password');
    const inp2 = document.getElementById('confirmar_password');
    const bar  = document.getElementById('strengthBar');
    const barTxt = document.getElementById('strengthTxt');
    const matchTxt = document.getElementById('matchTxt');
    const btnSubmit = document.getElementById('btnSubmit');
    const r = {
        len   : document.getElementById('r-len'),
        upper : document.getElementById('r-upper'),
        num   : document.getElementById('r-num'),
    };

    function tick(el, ok) {
        if (!el) return;
        el.style.color    = ok ? '#27ae60' : 'var(--text-m)';
        el.style.fontWeight = ok ? '700' : '400';
    }

    function evalPass() {
        if (!inp1) return;
        const v = inp1.value;
        const len   = v.length >= 8;
        const upper = /[A-Z]/.test(v);
        const num   = /[0-9]/.test(v);
        const score = [len, upper, num].filter(Boolean).length;

        tick(r.len,   len);
        tick(r.upper, upper);
        tick(r.num,   num);

        const colors = ['#e74c3c', '#f39c12', '#27ae60'];
        const labels = ['Débil', 'Media', 'Fuerte'];
        if (bar) {
            bar.style.width      = (score / 3 * 100) + '%';
            bar.style.background = v.length ? (colors[score - 1] || '#eee') : 'transparent';
        }
        if (barTxt) barTxt.textContent = v.length ? (labels[score - 1] || '') : '';

        evalMatch();
    }

    function evalMatch() {
        if (!inp2 || !matchTxt) return;
        if (!inp2.value) { matchTxt.textContent = ''; return; }
        const ok = inp1.value === inp2.value;
        matchTxt.textContent  = ok ? '✅ Las contraseñas coinciden' : '❌ No coinciden';
        matchTxt.style.color  = ok ? '#27ae60' : '#e74c3c';
        matchTxt.style.fontWeight = '600';
    }

    if (inp1) inp1.addEventListener('input', evalPass);
    if (inp2) inp2.addEventListener('input', evalMatch);

    // Validación antes de enviar
    const form = document.getElementById('formCambiarPass');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!inp1 || inp1.value.length < 8) {
                e.preventDefault();
                inp1.classList.add('is-invalid');
                inp1.focus();
                return;
            }
            if (inp1.value !== inp2.value) {
                e.preventDefault();
                inp2.classList.add('is-invalid');
                inp2.focus();
                return;
            }
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando…';
            }
        });

        // Limpiar invalid al escribir
        [inp1, inp2].forEach(inp => {
            if (inp) inp.addEventListener('input', () => inp.classList.remove('is-invalid'));
        });
    }
})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
