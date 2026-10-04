<?php
// ── Solo presentación: carga datos via modelo, sin lógica de negocio ──
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/categoria.php';
requireRole(ROL_ADMIN);

$pageTitle  = 'Categorías';
$model      = new Categoria();
$categorias = $model->listarTodas();

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <div class="mb-4">
        <h2 class="fw-800 mb-0">Categorías</h2>
        <p class="text-muted"><?= count($categorias) ?> categorías registradas</p>
    </div>

    <?php if ($exito): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Lista -->
        <div class="col-lg-8">
            <div class="row g-3">
                <?php foreach ($categorias as $cat): ?>
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;background:<?= htmlspecialchars($cat['bg']) ?>;flex-shrink:0;overflow:hidden">
                                <?php 
                                $icono = htmlspecialchars($cat['icono']);
                                if (str_ends_with($icono, '.png') || str_ends_with($icono, '.jpg') || str_ends_with($icono, '.svg')): 
                                ?>
                                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= $icono ?>" 
                                         alt="<?= htmlspecialchars($cat['nombre']) ?>"
                                         style="width:32px;height:32px;object-fit:contain"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block'">
                                    <i class="bi bi-egg-fried" 
                                       style="display:none;color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.4rem"></i>
                                <?php else: ?>
                                    <i class="bi <?= $icono ?>"
                                       style="color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.4rem"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-700"><?= htmlspecialchars($cat['nombre']) ?></div>
                                <small class="text-muted">
                                    <?= $cat['total_recetas'] ?> receta<?= $cat['total_recetas'] != 1 ? 's' : '' ?>
                                </small>
                            </div>
                            <!-- Formulario POST → CategoriaController -->
                            <form method="POST"
                                  action="<?= BASE_URL ?>/controllers/CategoriaController.php"
                                  onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id"     value="<?= $cat['id'] ?>">
                                <?php csrfField(); ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Formulario nueva categoría → CategoriaController -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-700 mb-3">Nueva categoría</h5>
                    <form method="POST" action="<?= BASE_URL ?>/controllers/CategoriaController.php">
                        <?php csrfField(); ?>
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label small fw-600">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required placeholder="Ej: Peruana">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-600">Icono</label>
                            <input type="text" class="form-control" name="icono" placeholder="Ej: desayuno.png o bi-egg-fried" required>
                            <small class="text-muted">
                                PNG desde <code>/img/Icon/Categorias/</code> o clase Bootstrap de 
                                <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a>
                            </small>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-600">Color texto</label>
                                <input type="color" class="form-control form-control-color w-100" name="color" value="#e56a26">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-600">Color fondo</label>
                                <input type="color" class="form-control form-control-color w-100" name="bg" value="#fff1e6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-2"></i>Crear categoría
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
