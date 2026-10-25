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
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="pb-5">
<div class="container">

    <?php
    $phTag     = 'Mi colección';
    $phTagImg  = BASE_URL . '/img/Icon/Icon-Animados/heart.gif';
    $phTitle   = 'Mis Favoritos';
    $phSub     = count($favoritos) . ' receta' . (count($favoritos) !== 1 ? 's' : '') . ' guardadas para volver a cocinarlas';
    $phArtImg  = BASE_URL . '/img/Icon/Icon-Animados/heart.gif';
    include __DIR__ . '/../../assets/page_header.php';
    ?>

    <?php if (empty($favoritos)): ?>
    <div class="empty-state">
        <i class="bi bi-heartbreak" aria-hidden="true" style="font-size:5rem;opacity:.5;display:block;margin:0 auto 1rem"></i>
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
                     onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title fw-700 mb-1 text-truncate"><?= htmlspecialchars($receta['titulo']) ?></h5>
                    <small class="text-muted">por <?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></small>
                    <div class="d-flex align-items-center gap-2 text-muted small mt-2 flex-wrap">
                        <span><i class="bi bi-clock me-1"></i><?= (int)$receta['tiempo_preparacion'] ?> min</span>
                        <?php if (!empty($receta['rating'])): ?>
                        <span class="ms-auto fw-700 d-flex align-items-center gap-1" style="color:var(--gold)">
                            <i class="bi bi-star-fill" aria-hidden="true"></i>
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
                            class="btn btn-sm btn-danger btn-fav"
                            data-id="<?= (int)$receta['id'] ?>"
                            title="Quitar de favoritos"
                            aria-label="Quitar de favoritos"
                            aria-pressed="true">
                        <i class="bi bi-heart-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
