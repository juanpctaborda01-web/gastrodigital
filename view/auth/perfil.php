<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireLogin();

$pageTitle = 'Mi Perfil';
$userModel = new Usuario();
$userId    = (int)$_SESSION['usuario_id'];
$perfil    = $userModel->buscarPorId($userId) ?: [];
$nombre    = $perfil['nombre']   ?? ($_SESSION['usuario_nombre'] ?? '');
$email     = $perfil['email']    ?? ($_SESSION['usuario_email']  ?? '');
$avatar    = $perfil['avatar']   ?? '';
$bio       = $perfil['bio']      ?? '';
$rol       = $perfil['rol']      ?? ($_SESSION['rol'] ?? 'usuario');
$redes     = $perfil['redes_sociales'] ?? '';
$espec     = $perfil['especialidad']   ?? '';
$iniciales = strtoupper(substr($nombre, 0, 1));

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$dashUrl = match($rol) {
    'admin' => BASE_URL . '/admin',
    'chef'  => BASE_URL . '/mis-recetas',
    default => BASE_URL . '/dashboard',
};

$rolColors = [
    'admin'   => ['bg' => '#fdecea', 'text' => '#c0392b'],
    'chef'    => ['bg' => 'rgba(232,168,56,.15)', 'text' => '#c98d20'],
    'usuario' => ['bg' => 'rgba(52,152,219,.12)', 'text' => '#2471a3'],
];
$rc = $rolColors[$rol] ?? $rolColors['usuario'];
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-9">

    <!-- ALERTAS -->
    <?php if ($exito): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST"
          action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardarPerfil"
          enctype="multipart/form-data">
        <?php csrfField(); ?>
        <input type="hidden" name="usuario_id" value="<?= $userId ?>">

        <!-- ══ CARD PRINCIPAL ══ -->
        <div class="perfil-card">

            <!-- ── Columna avatar ── -->
            <div class="perfil-avatar-col">
                <div class="perfil-avatar-wrap" id="avatarWrap">
                    <?php if ($avatar && (str_starts_with($avatar, 'http') || file_exists(ROOT_PATH . '/uploads/' . $avatar))): ?>
                        <img src="<?= str_starts_with($avatar, 'http') ? htmlspecialchars($avatar) : BASE_URL . '/uploads/' . htmlspecialchars($avatar) ?>"
                             alt="Avatar" class="perfil-avatar-img" id="avatarPreview">
                    <?php else: ?>
                        <div class="perfil-avatar-initials" id="avatarPreview"><?= $iniciales ?></div>
                    <?php endif; ?>
                    <label for="avatarFile" class="perfil-avatar-overlay" title="Cambiar foto">
                        <i class="bi bi-camera-fill"></i>
                    </label>
                </div>
                <input type="file" id="avatarFile" name="avatar_file"
                       accept="image/jpeg,image/png,image/webp"
                       class="d-none" data-preview="avatarPreviewImg">
                <h4 class="perfil-nombre mt-3"><?= htmlspecialchars($nombre) ?></h4>
                <span class="perfil-rol-badge" style="background:<?= $rc['bg'] ?>;color:<?= $rc['text'] ?>">
                    <?= ucfirst($rol) ?>
                </span>
                <p class="text-muted small mt-2 mb-0"><?= htmlspecialchars($email) ?></p>
            </div>

            <!-- ── Columna formulario ── -->
            <div class="perfil-form-col">

                <!-- Sección: Información personal -->
                <div class="perfil-section">
                    <div class="perfil-section-header">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>Información personal</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= htmlspecialchars($nombre) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control bg-light"
                                   value="<?= htmlspecialchars($email) ?>" readonly disabled>
                            <small class="text-muted">No se puede cambiar</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Biografía</label>
                            <textarea name="bio" class="form-control" rows="3"
                                      placeholder="Cuéntanos sobre tus gustos culinarios..."><?= htmlspecialchars($bio) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sección: Avatar URL -->
                <div class="perfil-section">
                    <div class="perfil-section-header">
                        <i class="bi bi-image"></i>
                        <span>Foto de perfil</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">URL de imagen (opcional)</label>
                            <input type="text" name="avatar" class="form-control"
                                   value="<?= htmlspecialchars($avatar) ?>"
                                   placeholder="https://ejemplo.com/foto.jpg">
                            <small class="text-muted">O haz clic en el avatar de arriba para subir un archivo</small>
                        </div>
                    </div>
                </div>

                <?php if ($rol === 'chef'): ?>
                <!-- Sección: Chef -->
                <div class="perfil-section">
                    <div class="perfil-section-header">
                        <i class="bi bi-award"></i>
                        <span>Perfil de Chef</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Especialidad</label>
                            <input type="text" name="especialidad" class="form-control"
                                   value="<?= htmlspecialchars($espec) ?>"
                                   placeholder="Ej. Comida italiana, repostería...">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Redes sociales</label>
                            <input type="text" name="redes_sociales" class="form-control"
                                   value="<?= htmlspecialchars($redes) ?>"
                                   placeholder="Instagram, TikTok...">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botones -->
                <div class="d-flex gap-3 justify-content-end mt-2">
                    <a href="<?= $dashUrl ?>" class="btn btn-outline-secondary fw-600">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                    <button type="submit" class="btn btn-primary fw-700 px-4">
                        <i class="bi bi-save me-2"></i>Guardar cambios
                    </button>
                </div>

            </div><!-- /perfil-form-col -->
        </div><!-- /perfil-card -->
    </form>

</div>
</div>
</div>
</main>

<style>
/* ── Perfil Card Layout ── */
.perfil-card {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 0;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(28,16,8,.12);
    border: 1px solid var(--border);
}

@media (max-width: 768px) {
    .perfil-card { grid-template-columns: 1fr; }
}

/* Columna avatar */
.perfil-avatar-col {
    display: flex; flex-direction: column; align-items: center;
    padding: 2.5rem 1.5rem;
    background: linear-gradient(160deg, var(--dark) 0%, var(--dark-2) 100%);
    text-align: center;
}

/* Wrap del avatar */
.perfil-avatar-wrap {
    position: relative; width: 100px; height: 100px;
    border-radius: 50%; cursor: pointer;
}

.perfil-avatar-img {
    width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
    box-shadow: 0 4px 18px rgba(0,0,0,.35);
    border: 3px solid rgba(232,168,56,.4);
}

.perfil-avatar-initials {
    width: 100%; height: 100%; border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--orange));
    color: #fff; font-size: 2.2rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 18px rgba(211,84,0,.4);
    border: 3px solid rgba(232,168,56,.4);
}

.perfil-avatar-overlay {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.4rem;
    opacity: 0; transition: opacity .2s; cursor: pointer;
}
.perfil-avatar-wrap:hover .perfil-avatar-overlay { opacity: 1; }

.perfil-nombre {
    color: #fff; font-weight: 800; font-size: 1.05rem; margin-bottom: .4rem;
}

.perfil-rol-badge {
    padding: .25rem .75rem; border-radius: 20px;
    font-size: .75rem; font-weight: 700; text-transform: uppercase;
}

/* Columna formulario */
.perfil-form-col { padding: 2rem 2.5rem; }

/* Secciones del formulario */
.perfil-section {
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
}
.perfil-section:last-of-type { border-bottom: none; margin-bottom: .5rem; }

.perfil-section-header {
    display: flex; align-items: center; gap: .6rem;
    font-size: .82rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--orange); margin-bottom: 1rem;
}
.perfil-section-header i { font-size: 1rem; }

/* Labels */
.form-label { font-weight: 600; font-size: .88rem; color: var(--text); margin-bottom: .35rem; }
</style>

<script>
// Preview de avatar al seleccionar archivo
document.getElementById('avatarFile')?.addEventListener('change', function () {
    if (!this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('avatarWrap');
        // Reemplazar contenido del preview
        const existing = wrap.querySelector('.perfil-avatar-img, .perfil-avatar-initials');
        if (existing) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'perfil-avatar-img';
            img.alt = 'Avatar';
            existing.replaceWith(img);
        }
    };
    reader.readAsDataURL(this.files[0]);
});
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
