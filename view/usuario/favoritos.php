<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';
require_once __DIR__ . '/../../models/receta.php';

requireLogin();

$pageTitle = 'Mis Favoritos';
$model     = new Receta();
$favoritos = $model->favoritosDeUsuario((int)$_SESSION['usuario_id']);
$baseUrl   = rtrim(BASE_URL, '/');
$icoBase   = $baseUrl . '/img/Icon/Categorias/';
$icoAnim   = $baseUrl . '/img/Icon/Icon-Animados/';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="pb-5">
<div class="container">

    <!-- HERO UNIFICADO -->
    <div class="gd-section-hero mt-3 mb-4">
        <div class="gd-section-hero-body">
            <span class="section-tag">
                <i class="bi bi-heart-fill me-1"></i>Mi colección
            </span>
            <h1 class="gd-section-hero-title">Mis Favoritos</h1>
            <p class="gd-section-hero-sub">
                <?= count($favoritos) ?> receta<?= count($favoritos) !== 1 ? 's' : '' ?> guardadas para volver a cocinarlas
            </p>
        </div>
        <div class="gd-section-hero-art d-none d-md-block">
            <img src="<?= $icoAnim ?>heart.gif" alt="" style="filter:none;opacity:.35">
        </div>
    </div>

    <?php if (empty($favoritos)): ?>
    <div class="empty-state">
        <img src="<?= $icoAnim ?>heart.gif" alt="" style="width:80px;height:80px;opacity:.5;margin:0 auto 1rem;display:block">
        <h5>Aún no tienes recetas favoritas</h5>
        <p>Explora el catálogo y guarda las que más te gusten.</p>
        <a href="<?= $baseUrl ?>/recetas" class="btn btn-primary mt-2">
            <i class="bi bi-search me-1"></i>Explorar recetas
        </a>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($favoritos as $receta):
            // Bug fix: normalizar clave de categoría
            $catKey  = strtolower(trim($receta['categoria'] ?? ''));
            $color   = $CATEGORIAS_COLOR[$catKey] ?? '#c0392b';
            $icoFile = getCategoriaIcono($receta['categoria'] ?? '');
            $paisIso = strtolower($receta['pais_iso'] ?? '');
        ?>
        <div class="col-sm-6 col-lg-4" id="favorito-<?= (int)$receta['id'] ?>">
            <article class="card receta-card h-100 position-relative overflow-hidden">

                <!-- Imagen -->
                <?php if (!empty($receta['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])): ?>
                    <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($receta['imagen']) ?>"
                         class="card-img-top" alt="<?= htmlspecialchars($receta['titulo']) ?>">
                <?php else: ?>
                    <div class="receta-img-placeholder"
                         style="background:linear-gradient(135deg,<?= $color ?>22,<?= $color ?>55)">
                        <img src="<?= $icoBase ?><?= $icoFile ?>" alt=""
                             style="width:64px;height:64px;opacity:.5"
                             onerror="this.style.display='none'">
                    </div>
                <?php endif; ?>

                <!-- Badge categoría con icono PNG -->
                <span class="receta-badge-icon">
                    <img src="<?= $icoBase ?><?= $icoFile ?>"
                         alt="<?= htmlspecialchars($receta['categoria'] ?? '') ?>"
                         class="receta-categoria-icono"
                         onerror="this.style.display='none'">
                    <span class="receta-categoria-nombre"><?= htmlspecialchars($receta['categoria'] ?? '') ?></span>
                </span>

                <!-- Flag país -->
                <?php if ($paisIso): ?>
                <img src="<?= $baseUrl ?>/img/icon/flags/4x3/<?= $paisIso ?>.svg"
                     alt="País" class="receta-flag-badge position-absolute"
                     style="top:11px;right:11px;z-index:2"
                     onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title fw-700 mb-1 text-truncate"><?= htmlspecialchars($receta['titulo']) ?></h5>
                    <small class="text-muted">por <?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></small>
                    <div class="d-flex align-items-center gap-2 text-muted small mt-2 flex-wrap">
                        <span><i class="bi bi-clock me-1"></i><?= (int)$receta['tiempo_preparacion'] ?> min</span>
                        <?php if (!empty($receta['rating'])): ?>
                        <span class="ms-auto fw-700 d-flex align-items-center gap-1" style="color:var(--gold)">
                            <img src="<?= $icoAnim ?>stars.gif" alt="" style="width:14px;height:14px">
                            <?= $receta['rating'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-0 pb-3 d-flex gap-2">
                    <a href="<?= $baseUrl ?>/recetas?id=<?= (int)$receta['id'] ?>"
                       class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-eye me-1"></i>Ver receta
                    </a>
                    <button type="button"
                            class="btn btn-sm btn-danger btn-remove-fav"
                            data-id="<?= (int)$receta['id'] ?>"
                            title="Quitar de favoritos">
                        <img src="<?= $icoAnim ?>heart.gif" alt="" style="width:16px;height:16px">
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

document.querySelectorAll('.btn-remove-fav').forEach(button => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const res = await fetch('<?= $baseUrl ?>/favorito', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ receta_id: button.dataset.id, csrf_token: CSRF_TOKEN })
            });
            const data = await res.json();
            if (data.activo === false) {
                const card = document.getElementById('favorito-' + button.dataset.id);
                if (card) {
                    card.style.transition = 'opacity .3s, transform .3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(.95)';
                    setTimeout(() => {
                        card.remove();
                        if (!document.querySelector('[id^="favorito-"]')) location.reload();
                    }, 300);
                }
            }
        } catch (e) {
            alert('No se pudo quitar el favorito. Intenta de nuevo.');
        }
        button.disabled = false;
    });
});
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
