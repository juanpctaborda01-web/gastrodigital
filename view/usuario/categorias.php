<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/categoria.php';
require_once __DIR__ . '/../../models/receta.php';
requireLogin();

$pageTitle   = 'Categorías';
$catModel    = new Categoria();
$categorias  = $catModel->listarTodas();

$pageTitle = 'Categorías';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <div class="mb-5">
        <h2 class="fw-800 mb-1">Explorar por categoría</h2>
        <p class="text-muted">Encuentra recetas según tu tipo de cocina favorito</p>
    </div>

    <?php if (empty($categorias)): ?>
        <div class="text-center py-5">
            <i class="bi bi-tags fs-1 text-muted opacity-50 d-block mb-3"></i>
            <h5 class="text-muted">Aún no hay categorías registradas</h5>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($categorias as $cat): ?>
        <div class="col-sm-6 col-lg-4">
            <a href="<?= BASE_URL ?>/recetas?categoria=<?= urlencode($cat['nombre']) ?>"
               class="card text-decoration-none h-100 border-0 shadow-sm"
               style="transition:transform .18s,box-shadow .18s"
               onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.13)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body d-flex align-items-center gap-4 py-4">

                    <!-- Ícono: imagen Flaticon si existe, si no Bootstrap Icon -->
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:60px;height:60px;background:<?= htmlspecialchars($cat['bg']) ?>">
                        <?php if (!empty($cat['img'])): ?>
                            <img src="<?= BASE_URL ?>/img/Icon/<?= htmlspecialchars($cat['img']) ?>"
                                 alt="" style="width:34px;height:34px;object-fit:contain">
                        <?php else: ?>
                            <i class="bi <?= htmlspecialchars($cat['icono']) ?>"
                               style="color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.7rem"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Texto -->
                    <div class="flex-grow-1 overflow-hidden">
                        <h5 class="fw-800 mb-1 text-truncate"><?= htmlspecialchars($cat['nombre']) ?></h5>
                        <span class="text-muted small">
                            <?= (int)$cat['total_recetas'] ?>
                            receta<?= $cat['total_recetas'] != 1 ? 's' : '' ?>
                        </span>
                    </div>

                    <!-- Flecha -->
                    <i class="bi bi-arrow-right ms-auto"
                       style="color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.2rem"></i>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
