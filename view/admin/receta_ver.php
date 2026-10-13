<?php
/**
 * view/admin/receta_ver.php
 * Vista de detalle de una receta para el administrador.
 * Variable inyectada por AdminRecetaController::ver():
 *   $receta   array  — datos completos con ingredientes y pasos
 *   $pageTitle string
 */
require_once __DIR__ . '/../../config/config.php';
requireRole(ROL_ADMIN);

// Auto-delegar si se carga sin variables
if (!isset($receta)) {
    require_once __DIR__ . '/../../controllers/AdminRecetaController.php';
    (new AdminRecetaController())->ver();
    exit;
}

$r       = $receta;
$rId     = (int)($r['id_receta'] ?? 0);
$baseUrl = BASE_URL;
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>
<?php
$topbarBadge = 'Admin';
$topbarBreadcrumb = [
    ['label' => 'Inicio',   'href' => $baseUrl . '/admin'],
    ['label' => 'Recetas',  'href' => $baseUrl . '/index.php?route=admin/recetas'],
    ['label' => htmlspecialchars($r['titulo'])],
];
$topbarAction = [
    'label' => 'Editar receta',
    'href'  => $baseUrl . '/index.php?route=admin/recetas/editar&id=' . $rId,
    'icon'  => 'bi-pencil',
    'class' => 'btn-primary',
];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5">
<div class="container-fluid px-3 mt-3" style="max-width:960px">

    <!-- ── Cabecera con imagen y datos principales ── -->
    <div class="card panel-card overflow-hidden mb-4">
        <div class="row g-0">

            <!-- Imagen -->
            <div class="col-lg-5 position-relative">
                <?php if (!empty($r['imagen'])): ?>
                <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                     alt="<?= htmlspecialchars($r['titulo']) ?>"
                     class="w-100 h-100"
                     style="object-fit:cover;min-height:280px;max-height:420px"
                     onerror="this.parentNode.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100\' style=\'min-height:280px;background:var(--surface-3)\'><i class=\'bi bi-egg-fried fs-1 text-muted\'></i></div>'">
                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100"
                     style="min-height:280px;background:var(--surface-3)">
                    <i class="bi bi-egg-fried fs-1 text-muted"></i>
                </div>
                <?php endif; ?>

                <!-- Badges sobre la imagen -->
                <div class="position-absolute top-0 start-0 p-2 d-flex flex-column gap-1">
                    <?php if ($r['publicada']): ?>
                    <span class="badge bg-success rounded-pill">
                        <i class="bi bi-eye me-1"></i>Publicada
                    </span>
                    <?php else: ?>
                    <span class="badge bg-secondary rounded-pill">
                        <i class="bi bi-eye-slash me-1"></i>Borrador
                    </span>
                    <?php endif; ?>
                    <?php if ($r['destacada']): ?>
                    <span class="badge rounded-pill"
                          style="background:#f4ab2e;color:#3b2a1a">
                        <i class="bi bi-star-fill me-1"></i>Destacada
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info principal -->
            <div class="col-lg-7">
                <div class="card-body p-4">
                    <!-- ID + Categoría -->
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge rounded-pill"
                              style="background:var(--surface-3);color:var(--text-muted);font-size:.75rem">
                            #<?= $rId ?>
                        </span>
                        <?php if (!empty($r['categoria_nombre'])): ?>
                        <span class="badge rounded-pill"
                              style="background:var(--primary);color:#fff">
                            <?= htmlspecialchars($r['categoria_nombre']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($r['pais_nombre'])): ?>
                        <span class="badge rounded-pill"
                              style="background:var(--surface-3);color:var(--text)">
                            <?php if (!empty($r['codigo_iso'])): ?>
                            <img src="<?= $baseUrl ?>/img/icon/flags/4x3/<?= strtolower($r['codigo_iso']) ?>.svg"
                                 alt="" style="height:12px;margin-right:4px;vertical-align:middle"
                                 onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?= htmlspecialchars($r['pais_nombre']) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Título -->
                    <h2 class="fw-800 mb-1" style="color:var(--text)">
                        <?= htmlspecialchars($r['titulo']) ?>
                    </h2>

                    <!-- Chef -->
                    <?php if (!empty($r['chef_nombre'])): ?>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-person-badge me-1"></i>
                        por <strong><?= htmlspecialchars($r['chef_nombre']) ?></strong>
                    </p>
                    <?php endif; ?>

                    <!-- Descripción -->
                    <?php if (!empty($r['descripcion'])): ?>
                    <p class="mb-4" style="color:var(--text);line-height:1.65">
                        <?= htmlspecialchars($r['descripcion']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Stats en fila -->
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:var(--primary);font-size:1.1rem">
                                    <?= (int)$r['tiempo_preparacion'] ?> min
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-clock me-1"></i>Tiempo
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:var(--secondary);font-size:1.1rem">
                                    <?= (int)$r['porciones'] ?>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-people me-1"></i>Porciones
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <?php
                                $dColor = match($r['dificultad']) {
                                    'Fácil'   => 'var(--bs-success)',
                                    'Difícil' => 'var(--bs-danger)',
                                    default   => '#f4ab2e',
                                };
                                ?>
                                <div class="fw-800" style="color:<?= $dColor ?>;font-size:1.1rem">
                                    <?= htmlspecialchars($r['dificultad']) ?>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-bar-chart me-1"></i>Nivel
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($r['calorias'])): ?>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <div class="fw-800" style="font-size:1.1rem">
                                    <?= (int)$r['calorias'] ?> kcal
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-fire me-1"></i>Calorías
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($r['rating'])): ?>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:#f4ab2e;font-size:1.1rem">
                                    <i class="bi bi-star-fill me-1"></i><?= $r['rating'] ?>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?= (int)$r['total_valoraciones'] ?> valoración<?= $r['total_valoraciones'] != 1 ? 'es' : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:var(--surface-3)">
                                <div class="fw-800" style="font-size:1.1rem">
                                    <?= number_format((int)$r['visitas']) ?>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-eye me-1"></i>Visitas
                                </div>
                            </div>
                        </div>
                    </div><!-- /row stats -->

                    <!-- Video URL -->
                    <?php if (!empty($r['video_url'])): ?>
                    <div class="mt-3">
                        <a href="<?= htmlspecialchars($r['video_url']) ?>"
                           target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-play-circle me-1"></i>Ver video
                        </a>
                    </div>
                    <?php endif; ?>

                </div><!-- /card-body -->
            </div><!-- /col info -->

        </div><!-- /row -->
    </div><!-- /card cabecera -->

    <!-- ── Ingredientes + Pasos en fila ── -->
    <div class="row g-4 mb-4">

        <!-- Ingredientes -->
        <div class="col-lg-5">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <i class="bi bi-list-check" style="color:var(--primary);font-size:1.1rem"></i>
                    <h5 class="mb-0 fw-800">Ingredientes</h5>
                    <span class="badge bg-secondary rounded-pill ms-auto">
                        <?= count($r['ingredientes']) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($r['ingredientes'])): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($r['ingredientes'] as $ing): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 py-2"
                            style="border-color:var(--border)">
                            <span class="text-muted" style="font-size:.8rem">•</span>
                            <span class="flex-grow-1">
                                <?= htmlspecialchars($ing['nombre']) ?>
                            </span>
                            <?php if (!empty($ing['cantidad']) || !empty($ing['unidad'])): ?>
                            <span class="badge rounded-pill fw-600"
                                  style="background:var(--surface-3);color:var(--text);font-size:.75rem;white-space:nowrap">
                                <?= htmlspecialchars(trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? ''))) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($ing['opcional'])): ?>
                            <span class="badge bg-light text-muted rounded-pill"
                                  style="font-size:.68rem">opcional</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="p-4 text-muted text-center small">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Sin ingredientes registrados.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pasos -->
        <div class="col-lg-7">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <i class="bi bi-journal-text" style="color:var(--secondary);font-size:1.1rem"></i>
                    <h5 class="mb-0 fw-800">Preparación</h5>
                    <span class="badge bg-secondary rounded-pill ms-auto">
                        <?= count($r['pasos']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($r['pasos'])): ?>
                    <ol class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <?php foreach ($r['pasos'] as $paso): ?>
                        <li class="d-flex gap-3 align-items-start">
                            <span class="d-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width:32px;height:32px;border-radius:50%;
                                         background:linear-gradient(135deg,var(--primary),var(--secondary));
                                         color:#fff;font-weight:800;font-size:.85rem;margin-top:1px">
                                <?= (int)$paso['numero'] ?>
                            </span>
                            <div class="flex-grow-1">
                                <p class="mb-0" style="line-height:1.6;color:var(--text)">
                                    <?= htmlspecialchars($paso['descripcion']) ?>
                                </p>
                                <?php if (!empty($paso['duracion'])): ?>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i><?= (int)$paso['duracion'] ?> min
                                </small>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else: ?>
                    <div class="text-muted text-center small py-3">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Sin pasos registrados.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row ingredientes+pasos -->

    <!-- ── Metadatos técnicos ── -->
    <div class="card panel-card mb-4">
        <div class="card-header py-2 px-3"
             style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <span class="fw-700 small text-muted">
                <i class="bi bi-info-circle me-1"></i>Metadatos
            </span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3 small text-muted">
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">ID receta</strong>
                    #<?= $rId ?>
                </div>
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">Imagen guardada</strong>
                    <?= !empty($r['imagen']) ? htmlspecialchars($r['imagen']) : '—' ?>
                </div>
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">ID Chef</strong>
                    <?= !empty($r['id_chef']) ? '#' . $r['id_chef'] : '—' ?>
                </div>
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">Creada</strong>
                    <?= !empty($r['created_at'])
                        ? date('d/m/Y H:i', strtotime($r['created_at']))
                        : '—' ?>
                </div>
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">Última edición</strong>
                    <?= !empty($r['updated_at'])
                        ? date('d/m/Y H:i', strtotime($r['updated_at']))
                        : '—' ?>
                </div>
                <div class="col-sm-4">
                    <strong class="d-block" style="color:var(--text)">Visitas totales</strong>
                    <?= number_format((int)($r['visitas'] ?? 0)) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Acciones ── -->
    <div class="d-flex flex-wrap gap-3 justify-content-between mb-4">
        <a href="<?= $baseUrl ?>/index.php?route=admin/recetas"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver al listado
        </a>
        <div class="d-flex gap-2">
            <!-- Ver en el catálogo público -->
            <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $rId ?>"
               target="_blank" rel="noopener"
               class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver pública
            </a>
            <!-- Editar -->
            <a href="<?= $baseUrl ?>/index.php?route=admin/recetas/editar&id=<?= $rId ?>"
               class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <!-- Eliminar -->
            <button type="button" class="btn btn-outline-danger" id="btnEliminarVer"
                    data-id="<?= $rId ?>"
                    data-titulo="<?= htmlspecialchars($r['titulo'], ENT_QUOTES) ?>">
                <i class="bi bi-trash3 me-1"></i>Eliminar
            </button>
        </div>
    </div>

</div><!-- /container -->
</main>

<!-- Modal confirmación eliminación -->
<div class="modal fade" id="modalEliminarVer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-800">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    Eliminar receta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0">
                    ¿Seguro que deseas eliminar
                    <strong><?= htmlspecialchars($r['titulo']) ?></strong>?
                </p>
                <p class="text-muted small mt-2 mb-0">
                    Se eliminarán también sus ingredientes, pasos, favoritos,
                    comentarios y valoraciones. Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfEliminarVer">
                    <i class="bi bi-trash3 me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF     = <?= json_encode(csrfToken()) ?>;
    const BASE_URL = <?= json_encode(BASE_URL) ?>;
    const modal    = new bootstrap.Modal(document.getElementById('modalEliminarVer'));

    document.getElementById('btnEliminarVer').addEventListener('click', () => modal.show());

    document.getElementById('btnConfEliminarVer').addEventListener('click', function () {
        const id  = document.getElementById('btnEliminarVer').dataset.id;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';

        fetch(BASE_URL + '/index.php?route=admin/recetas/eliminar', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ receta_id: parseInt(id), csrf_token: CSRF }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                modal.hide();
                window.location.href = BASE_URL + '/index.php?route=admin/recetas';
            } else {
                alert(data.error || 'No se pudo eliminar la receta.');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-trash3 me-1"></i>Sí, eliminar';
            }
        })
        .catch(() => {
            alert('Error de conexión. Intenta de nuevo.');
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-trash3 me-1"></i>Sí, eliminar';
        });
    });
})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
