<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';
require_once __DIR__ . '/../../models/categoria.php';

$pageTitle  = 'Explorar Categorías';
$catModel   = new Categoria();
$categorias = $catModel->listarTodas();
$baseUrl    = rtrim(BASE_URL, '/');
$icoBase    = $baseUrl . '/img/Icon/Categorias/';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <!-- ENCABEZADO -->
    <div class="text-center mb-5">
        <span class="section-tag">
            <i class="bi bi-grid-3x3-gap me-1"></i>Catálogo
        </span>
        <h1 class="section-title">Categorías Gastronómicas</h1>
        <p class="section-subtitle">
            Descubre recetas por tipo de plato, ocasión o ingrediente principal
        </p>
    </div>

    <?php if (empty($categorias)): ?>
    <div class="empty-state">
        <i class="bi bi-grid"></i>
        <h5>Aún no hay categorías disponibles</h5>
        <a href="<?= $baseUrl ?>/recetas" class="btn btn-primary mt-2">
            <i class="bi bi-search me-1"></i>Ver todas las recetas
        </a>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($categorias as $cat):
            // Usar el icono desde la BD directamente
            $icoFile = !empty($cat['icono']) ? $cat['icono'] : getCategoriaIcono($cat['nombre'] ?? '');
            $icoPath = $icoBase . $icoFile;
            $color   = htmlspecialchars($cat['color'] ?? '#c0392b');
            $bg      = htmlspecialchars($cat['bg']    ?? '#fdecea');
            $total   = (int)($cat['total_recetas'] ?? 0);
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= $baseUrl ?>/recetas?categoria=<?= urlencode($cat['nombre']) ?>"
               class="cat-card text-decoration-none"
               style="--cat-color:<?= $color ?>;--cat-bg:<?= $bg ?>">

                <!-- Icono -->
                <div class="cat-card-icon">
                    <img src="<?= $icoPath ?>"
                         alt="<?= htmlspecialchars($cat['nombre']) ?>"
                         class="cat-card-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <!-- Fallback Bootstrap icon -->
                    <span class="cat-card-bi" style="display:none;color:var(--cat-color)">
                        <i class="bi <?= htmlspecialchars($cat['icono'] ?? 'bi-egg-fried') ?>"></i>
                    </span>
                </div>

                <!-- Info -->
                <h5 class="cat-card-nombre"><?= htmlspecialchars($cat['nombre']) ?></h5>
                <p class="cat-card-count" style="color:var(--cat-color)">
                    <?= $total ?> receta<?= $total !== 1 ? 's' : '' ?>
                </p>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>

<style>
/* ── Cat card ── */
.cat-card {
    display: flex; flex-direction: column; align-items: center;
    text-align: center;
    padding: 1.75rem 1rem 1.25rem;
    background: var(--cat-bg, #fdf3e7);
    border: 2px solid transparent;
    border-radius: 18px;
    color: var(--text);
    height: 100%;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    position: relative; overflow: hidden;
}
.cat-card::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.45), transparent);
    border-radius: inherit;
}
.cat-card:hover {
    transform: translateY(-8px);
    border-color: var(--cat-color, var(--gold));
    box-shadow: 0 14px 34px rgba(0,0,0,.13);
    color: var(--text);
}

/* Icono */
.cat-card-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(255,255,255,.7);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem;
    box-shadow: 0 6px 18px rgba(0,0,0,.1);
    position: relative; z-index: 1;
    transition: transform .22s ease;
}
.cat-card:hover .cat-card-icon {
    transform: scale(1.1) rotate(-5deg);
}
.cat-card-img {
    width: 46px; height: 46px; object-fit: contain;
}
.cat-card-bi {
    font-size: 2rem; align-items: center; justify-content: center;
}

/* Texto */
.cat-card-nombre {
    font-size: .9rem; font-weight: 700; color: var(--dark);
    margin: 0 0 .3rem; line-height: 1.3; position: relative; z-index: 1;
}
.cat-card-count {
    font-size: .75rem; font-weight: 600; margin: 0;
    position: relative; z-index: 1;
}

/* Dark mode */
[data-theme="dark"] .cat-card,
.dark .cat-card {
    background: #1e1408; border-color: rgba(232,168,56,.06);
}
[data-theme="dark"] .cat-card-nombre,
.dark .cat-card-nombre { color: #e0d0bc; }
[data-theme="dark"] .cat-card-icon,
.dark .cat-card-icon { background: rgba(255,255,255,.08); }
</style>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
