<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';
require_once __DIR__ . '/../../models/receta.php';

requireLogin();

$pageTitle = 'Recetas favoritas';
$model = new Receta();
$favoritos = $model->favoritosDeUsuario((int)$_SESSION['usuario_id']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="fw-800 mb-1"><i class="bi bi-heart-fill text-danger me-2"></i>Recetas favoritas</h2>
                <p class="text-muted mb-0">Tus recetas guardadas para volver a cocinarlas cuando quieras.</p>
            </div>
            <span class="text-muted small">
                <?= count($favoritos) ?> receta<?= count($favoritos) !== 1 ? 's' : '' ?> guardada<?= count($favoritos) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (empty($favoritos)): ?>
            <div class="empty-state">
                <i class="bi bi-heart"></i>
                <h5>Aún no tienes recetas favoritas</h5>
                <p>Guarda tus recetas preferidas desde el catálogo para encontrarlas aquí.</p>
                <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Explorar recetas
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($favoritos as $receta):
                    $catKey = strtolower($receta['categoria'] ?? '');
                    $color = $CATEGORIAS_COLOR[$catKey] ?? '#c0392b';
                ?>
                    <div class="col-sm-6 col-lg-4" id="favorito-<?= (int)$receta['id'] ?>">
                        <article class="card receta-card h-100 position-relative">
                            <?php if (!empty($receta['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])): ?>
                                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($receta['imagen']) ?>"
                                     class="card-img-top" alt="<?= htmlspecialchars($receta['titulo']) ?>">
                            <?php else: ?>
                                <div class="receta-img-placeholder"
                                     style="background:linear-gradient(135deg,<?= $color ?>22,<?= $color ?>55)">
                                    <i class="bi bi-egg-fried" style="color:<?= $color ?>"></i>
                                </div>
                            <?php endif; ?>

                            <span class="receta-badge"><?= htmlspecialchars($receta['categoria'] ?? '') ?></span>
                            <div class="card-body">
                                <h5 class="card-title fw-700 mb-1"><?= htmlspecialchars($receta['titulo']) ?></h5>
                                <small class="text-muted">por <?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></small>
                                <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                                    <span><i class="bi bi-clock me-1"></i><?= (int)$receta['tiempo_preparacion'] ?> min</span>
                                    <?php if ($receta['rating']): ?>
                                        <span class="ms-auto fw-700" style="color:var(--secondary)">
                                            <i class="bi bi-star-fill me-1"></i><?= $receta['rating'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-footer bg-transparent border-0 pb-3 d-flex gap-2">
                                <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int)$receta['id'] ?>"
                                   class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="bi bi-eye me-1"></i>Ver receta
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-remove-fav"
                                        data-id="<?= (int)$receta['id'] ?>" title="Quitar de favoritos">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars(csrfToken()) ?>';

document.querySelectorAll('.btn-remove-fav').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        const body = new URLSearchParams({
            receta_id: button.dataset.id,
            csrf_token: CSRF_TOKEN
        });

        try {
            const response = await fetch('<?= BASE_URL ?>/index.php?route=favorito', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });
            const data = await response.json();
            if (!response.ok || data.activo !== false) throw new Error('No se pudo actualizar');
            document.getElementById('favorito-' + button.dataset.id)?.remove();
            if (!document.querySelector('.btn-remove-fav')) window.location.reload();
        } catch (error) {
            button.disabled = false;
            alert('No se pudo quitar la receta de favoritos. Inténtalo de nuevo.');
        }
    });
});
</script>
