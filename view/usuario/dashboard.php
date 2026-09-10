<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/categoria.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireLogin();

$pageTitle = 'Mi Inicio';
$model     = new Receta();
$catModel  = new Categoria();
$userModel = new Usuario();
$nombre    = $_SESSION['usuario_nombre'] ?? 'Usuario';
$email     = $_SESSION['usuario_email']  ?? '';
$userId    = $_SESSION['usuario_id'];
$perfil    = $userModel->buscarPorId($userId) ?: [];
$perfilNombre = $perfil['nombre'] ?? $nombre;
$perfilAvatar = $perfil['avatar'] ?? '';
$perfilBio = $perfil['bio'] ?? '';
$perfilRedes = $perfil['redes_sociales'] ?? '';
$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$stats      = $model->stats();
$favoritos  = $model->favoritosDeUsuario($userId);
$recientes  = $model->listarFiltradas([], 1, 3)['recetas'];
$categorias = $catModel->listarSimple();

?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <?php if ($exito): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- ══ BANNER DE BIENVENIDA ══ -->
    <div class="p-4 rounded-4 text-white mb-5"
         style="background:linear-gradient(135deg,var(--primary),var(--primary-dark))">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-800 mb-1">
                    Bienvenido, <?= htmlspecialchars($nombre) ?> 👋
                </h2>
                <p class="mb-0 opacity-75">
                    Hay <?= $stats['total'] ?> recetas esperándote hoy.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/index.php?route=recetas"
                   class="btn btn-light fw-700" style="color:var(--primary)">
                    <i class="bi bi-search me-2"></i>Explorar recetas
                </a>
            </div>
        </div>
    </div>

    <!-- ══ STATS RÁPIDAS ══ -->
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-lg-3">
            <a href="<?= BASE_URL ?>/index.php?route=recetas" class="text-decoration-none">
                <div class="stat-card"
                     style="background:linear-gradient(135deg,var(--primary),var(--primary-light))">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number"><?= $stats['total'] ?></div>
                            <div class="stat-label">Recetas disponibles</div>
                        </div>
                        <i class="bi bi-book stat-icon"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" style="background:linear-gradient(135deg,#8e44ad,#9b59b6)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $stats['categorias'] ?></div>
                        <div class="stat-label">Categorías</div>
                    </div>
                    <i class="bi bi-tags stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card"
                 style="background:linear-gradient(135deg,var(--accent),#4a7a28)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= count($favoritos) ?></div>
                        <div class="stat-label">Mis favoritos</div>
                    </div>
                    <i class="bi bi-heart stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card" style="background:linear-gradient(135deg,#2980b9,#0096c7)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $stats['publicadas'] ?></div>
                        <div class="stat-label">Publicadas</div>
                    </div>
                    <i class="bi bi-check-circle stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ LAYOUT PRINCIPAL: contenido + perfil lateral ══ -->
    <div class="row g-4">

        <!-- Columna izquierda (contenido) -->
        <div class="col-lg-8">

            <!-- Recetas recientes -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-800 mb-0">Recetas recientes</h4>
                <a href="<?= BASE_URL ?>/index.php?route=recetas"
                   class="btn btn-outline-primary btn-sm">
                    Ver todas <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row g-4 mb-5">
                <?php foreach ($recientes as $r): ?>
                <div class="col-md-6">
                    <div class="card receta-card h-100 position-relative">
                        <?php if (!empty($r['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $r['imagen'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($r['titulo']) ?>">
                        <?php else: ?>
                            <div class="receta-img-placeholder"
                                 style="background:linear-gradient(135deg,#c0392b22,#c0392b55)">
                                <i class="bi bi-egg-fried" style="color:#c0392b"></i>
                            </div>
                        <?php endif; ?>
                        <span class="receta-badge"><?= htmlspecialchars($r['categoria'] ?? '') ?></span>
                        <div class="card-body">
                            <h5 class="card-title fw-700 mb-1"><?= htmlspecialchars($r['titulo']) ?></h5>
                            <small class="text-muted">por <?= htmlspecialchars($r['chef_nombre'] ?? 'Chef') ?></small>
                            <div class="d-flex gap-3 text-muted small mt-2">
                                <span><i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min</span>
                                <span><i class="bi bi-bar-chart me-1"></i><?= $r['dificultad'] ?></span>
                                <?php if ($r['rating']): ?>
                                <span class="ms-auto fw-700" style="color:var(--secondary)">
                                    <i class="bi bi-star-fill me-1"></i><?= $r['rating'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                               class="btn btn-outline-primary btn-sm w-100">Ver receta</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Explorar por categoría -->
            <div class="mb-2">
                <h4 class="fw-800 mb-1">Explorar por categoría</h4>
                <p class="text-muted small mb-3">Haz clic en una categoría para ver sus recetas</p>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-5">
                <?php foreach ($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>/recetas?categoria=<?= urlencode($cat['nombre']) ?>"
                   class="categoria-chip"
                   style="--chip-color:<?= htmlspecialchars($cat['color']) ?>;--chip-bg:<?= htmlspecialchars($cat['bg']) ?>">
                    <?php if (!empty($cat['img'])): ?>
                        <img src="<?= BASE_URL ?>/img/Icon/<?= htmlspecialchars($cat['img']) ?>"
                             alt="" class="categoria-chip-img">
                    <?php else: ?>
                        <i class="bi <?= htmlspecialchars($cat['icono']) ?>"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($cat['nombre']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Favoritos -->
            <?php if (!empty($favoritos)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-800 mb-0">
                    <i class="bi bi-heart-fill text-danger me-2"></i>Mis favoritos
                </h4>
                <span class="text-muted small">
                    <?= count($favoritos) ?> receta<?= count($favoritos) !== 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="row g-3">
                <?php foreach ($favoritos as $r): ?>
                <div class="col-sm-6">
                    <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                       class="card text-decoration-none h-100 position-relative">
                        <div class="receta-img-placeholder"
                             style="height:130px;background:linear-gradient(135deg,#c0392b22,#c0392b44)">
                            <i class="bi bi-egg-fried" style="color:#c0392b;font-size:2.2rem"></i>
                        </div>
                        <span class="receta-badge"><?= htmlspecialchars($r['categoria'] ?? '') ?></span>
                        <div class="card-body py-2">
                            <h6 class="fw-700 mb-0"><?= htmlspecialchars($r['titulo']) ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min
                                <?php if ($r['rating']): ?>
                                · <i class="bi bi-star-fill text-warning me-1"></i><?= $r['rating'] ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Estado vacío de favoritos -->
            <div class="card border-0" style="background:var(--surface-3)">
                <div class="card-body text-center py-4">
                    <i class="bi bi-heart fs-1 text-muted opacity-50"></i>
                    <h6 class="fw-700 mt-2 mb-1">Aún no tienes favoritos</h6>
                    <p class="text-muted small mb-3">
                        Guarda las recetas que más te gusten para encontrarlas rápido.
                    </p>
                    <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Descubrir recetas
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /col-lg-8 -->

        <!-- Columna derecha: perfil -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-lg-top" style="top:80px" id="perfil">
                <div class="card-body text-center pt-4 pb-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-800 mb-3"
                         style="width:78px;height:78px;background:linear-gradient(135deg,var(--primary),var(--secondary));font-size:1.8rem">
                        <?= strtoupper(substr($nombre, 0, 1)) ?>
                    </div>
                    <h5 class="fw-800 mb-1"><?= htmlspecialchars($nombre) ?></h5>
                    <?php if ($email): ?>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($email) ?></p>
                    <?php endif; ?>
                    <span class="badge rounded-pill px-3 py-1 mb-3" style="background:var(--primary);color:#fff;font-size:.75rem">
                        <i class="bi bi-person-fill me-1"></i>Usuario
                    </span>

                    <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardarPerfil" class="border rounded-3 p-3 text-start mb-3" style="background:var(--surface-2)" enctype="multipart/form-data">
                        <?php csrfField(); ?>
                        <input type="hidden" name="usuario_id" value="<?= (int)$userId ?>">
                        <div class="fw-700 mb-2"><i class="bi bi-pencil-square me-2" style="color:var(--primary)"></i>Mi perfil</div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Nombre de usuario</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilNombre) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Foto de perfil</label>
                            <input type="text" name="avatar" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilAvatar) ?>" placeholder="URL de imagen o déjalo vacío para usar la subida">
                            <input type="file" name="avatar_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Biografía</label>
                            <textarea name="bio" class="form-control form-control-sm" rows="3" placeholder="Cuéntanos algo sobre ti..."><?= htmlspecialchars($perfilBio) ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Redes sociales</label>
                            <input type="text" name="redes_sociales" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilRedes) ?>" placeholder="Instagram / TikTok / X">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                            <i class="bi bi-save me-1"></i>Guardar perfil
                        </button>
                    </form>

                    <div class="row g-0 text-center mb-3">
                        <div class="col-6 border-end py-2">
                            <div class="fw-800 fs-5" style="color:var(--primary)"><?= count($favoritos) ?></div>
                            <div class="text-muted" style="font-size:.75rem">Favoritos</div>
                        </div>
                        <div class="col-6 py-2">
                            <div class="fw-800 fs-5" style="color:var(--accent)"><?= $stats['total'] ?></div>
                            <div class="text-muted" style="font-size:.75rem">Recetas</div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-1">
                        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-2"></i>Explorar recetas
                        </a>
                        <a href="<?= BASE_URL ?>/index.php?route=logout" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                        </a>
                    </div>
                </div>
            </div>
        </div><!-- /col-lg-4 -->

    </div><!-- /row -->

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
