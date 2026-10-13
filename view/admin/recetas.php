<?php
/**
 * view/admin/recetas.php
 * Listado de recetas para el administrador.
 * Variables inyectadas por AdminRecetaController::listado():
 *   $recetas, $total, $paginas, $pagina, $filtros,
 *   $categorias, $paises, $chefs, $pageTitle
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/categoria.php';
requireRole(ROL_ADMIN);

// Si se cargó directamente (sin pasar por el controlador), delegar
if (!isset($recetas)) {
    require_once __DIR__ . '/../../controllers/AdminRecetaController.php';
    (new AdminRecetaController())->listado();
    exit;
}

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$baseUrl = BASE_URL;
$qActual = $filtros['q']           ?? '';
$fCat    = $filtros['id_categoria'] ?? '';
$fPais   = $filtros['id_pais']      ?? '';
$fChef   = $filtros['id_chef']      ?? '';
$fDif    = $filtros['dificultad']   ?? '';
$fPub    = $filtros['publicada']    ?? '';
$fDest   = $filtros['destacada']    ?? '';

// URL base para paginación + filtros activos
$qBase = http_build_query(array_filter([
    'route'        => 'admin/recetas',
    'q'            => $qActual,
    'id_categoria' => $fCat,
    'id_pais'      => $fPais,
    'id_chef'      => $fChef,
    'dificultad'   => $fDif,
    'publicada'    => $fPub !== '' ? $fPub : null,
    'destacada'    => $fDest !== '' ? $fDest : null,
], fn($v) => $v !== null && $v !== ''));
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>
<?php
$topbarBadge      = 'Admin';
$topbarBreadcrumb = [
    ['label' => 'Inicio',  'href' => $baseUrl . '/admin'],
    ['label' => 'Recetas'],
];
$topbarAction = [
    'label' => 'Nueva receta',
    'href'  => $baseUrl . '/index.php?route=admin/recetas/nueva',
    'icon'  => 'bi-plus-circle',
    'class' => 'btn-primary',
];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5">
<div class="container-fluid px-3">

<?php if ($exito): ?>
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($exito) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Cabecera + stats rápidas ── -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 mb-3">
    <div>
        <h2 class="fw-800 mb-0" style="color:var(--text)">Recetas</h2>
        <p class="text-muted mb-0 small">
            <strong><?= $total ?></strong> receta<?= $total !== 1 ? 's' : '' ?> en total
        </p>
    </div>
    <a href="<?= $baseUrl ?>/index.php?route=admin/recetas/nueva"
       class="btn btn-primary d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle"></i> Nueva receta
    </a>
</div>

<!-- ── Panel de filtros ── -->
<div class="card panel-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= $baseUrl ?>/index.php" id="formFiltros">
            <input type="hidden" name="route" value="admin/recetas">
            <div class="row g-2 align-items-end">
                <!-- Búsqueda -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-600 mb-1">
                        <i class="bi bi-search me-1"></i>Buscar
                    </label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Título de la receta…"
                           value="<?= htmlspecialchars($qActual) ?>">
                </div>
                <!-- Categoría -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-600 mb-1">Categoría</label>
                    <select name="id_categoria" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= (string)$fCat === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Chef -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-600 mb-1">Chef</label>
                    <select name="id_chef" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($chefs as $c): ?>
                        <option value="<?= $c['id_usuario'] ?>"
                            <?= (string)$fChef === (string)$c['id_usuario'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Dificultad -->
                <div class="col-4 col-md-1">
                    <label class="form-label small fw-600 mb-1">Dificultad</label>
                    <select name="dificultad" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach (['Fácil', 'Media', 'Difícil'] as $d): ?>
                        <option value="<?= $d ?>" <?= $fDif === $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Publicada -->
                <div class="col-4 col-md-1">
                    <label class="form-label small fw-600 mb-1">Publicada</label>
                    <select name="publicada" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" <?= $fPub === '1' ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= $fPub === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <!-- Destacada -->
                <div class="col-4 col-md-1">
                    <label class="form-label small fw-600 mb-1">Destacada</label>
                    <select name="destacada" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" <?= $fDest === '1' ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= $fDest === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <!-- Botones -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                    <a href="<?= $baseUrl ?>/index.php?route=admin/recetas"
                       class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ── Tabla de recetas ── -->
<div class="card panel-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaRecetas">
            <thead style="background:var(--surface-3)">
                <tr>
                    <th class="ps-3" style="width:50px">ID</th>
                    <th style="width:64px">Img</th>
                    <th>Título</th>
                    <th class="d-none d-lg-table-cell">Categoría</th>
                    <th class="d-none d-xl-table-cell">Chef</th>
                    <th class="d-none d-md-table-cell" style="width:90px">Dificultad</th>
                    <th class="d-none d-md-table-cell" style="width:75px">Tiempo</th>
                    <th style="width:75px">Estado</th>
                    <th class="d-none d-lg-table-cell" style="width:90px">Fecha</th>
                    <th class="text-end pe-3" style="width:130px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recetas)): ?>
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        No se encontraron recetas con los filtros actuales.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recetas as $r): ?>
                <tr>
                    <td class="ps-3">
                        <span class="text-muted small">#<?= $r['id_receta'] ?></span>
                    </td>
                    <td>
                        <?php if (!empty($r['imagen'])): ?>
                        <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                             alt="" class="rounded"
                             style="width:52px;height:40px;object-fit:cover"
                             onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                        <div class="rounded d-none align-items-center justify-content-center bg-light"
                             style="width:52px;height:40px">
                            <i class="bi bi-egg-fried text-muted"></i>
                        </div>
                        <?php else: ?>
                        <div class="rounded d-flex align-items-center justify-content-center"
                             style="width:52px;height:40px;background:var(--surface-3)">
                            <i class="bi bi-egg-fried text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-700 text-truncate" style="max-width:220px">
                            <?= htmlspecialchars($r['titulo']) ?>
                        </div>
                        <?php if ($r['rating']): ?>
                        <small class="text-muted">
                            <i class="bi bi-star-fill" style="color:#f4ab2e;font-size:.7rem"></i>
                            <?= $r['rating'] ?>
                        </small>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="badge rounded-pill"
                              style="background:var(--surface-3);color:var(--text);font-weight:600">
                            <?= htmlspecialchars($r['categoria_nombre'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="d-none d-xl-table-cell text-muted small">
                        <?= htmlspecialchars($r['chef_nombre'] ?? '—') ?>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <?php
                        $dBadge = match($r['dificultad']) {
                            'Fácil'   => ['bg-success',  '🟢'],
                            'Difícil' => ['bg-danger',   '🔴'],
                            default   => ['bg-warning text-dark', '🟡'],
                        };
                        ?>
                        <span class="badge <?= $dBadge[0] ?> rounded-pill">
                            <?= $r['dificultad'] ?>
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">
                        <i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <?php if ($r['publicada']): ?>
                            <span class="badge bg-success rounded-pill" style="font-size:.65rem">
                                <i class="bi bi-eye me-1"></i>Pública
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary rounded-pill" style="font-size:.65rem">
                                <i class="bi bi-eye-slash me-1"></i>Borrador
                            </span>
                            <?php endif; ?>
                            <?php if ($r['destacada']): ?>
                            <span class="badge rounded-pill"
                                  style="background:#f4ab2e;color:#3b2a1a;font-size:.65rem">
                                <i class="bi bi-star-fill me-1"></i>Destac.
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">
                        <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="<?= $baseUrl ?>/index.php?route=admin/recetas/ver&id=<?= $r['id_receta'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="Ver receta">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= $baseUrl ?>/index.php?route=admin/recetas/editar&id=<?= $r['id_receta'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Editar receta">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-eliminar"
                                    data-id="<?= $r['id_receta'] ?>"
                                    data-titulo="<?= htmlspecialchars($r['titulo'], ENT_QUOTES) ?>"
                                    title="Eliminar receta">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /table-responsive -->
</div><!-- /card -->

<!-- ── Paginación ── -->
<?php if ($paginas > 1): ?>
<nav class="d-flex justify-content-center mt-4" aria-label="Paginación de recetas">
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>&pagina=<?= $pagina - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php
        $inicio = max(1, $pagina - 2);
        $fin    = min($paginas, $pagina + 2);
        if ($inicio > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= $qBase ?>&pagina=1">1</a></li>
            <?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $inicio; $p <= $fin; $p++): ?>
        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>&pagina=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($fin < $paginas): ?>
            <?php if ($fin < $paginas - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= $qBase ?>&pagina=<?= $paginas ?>"><?= $paginas ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>&pagina=<?= $pagina + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

</div><!-- /container-fluid -->
</main>

<!-- ── Modal de confirmación de eliminación ── -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
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
                    ¿Seguro que deseas eliminar la receta
                    <strong id="modalTituloReceta"></strong>?
                </p>
                <p class="text-muted small mt-2 mb-0">
                    Esta acción eliminará también sus ingredientes, pasos,
                    favoritos, comentarios y valoraciones. No se puede deshacer.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
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
    let idPendiente = null;

    // Abrir modal de confirmación
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function () {
            idPendiente = this.dataset.id;
            document.getElementById('modalTituloReceta').textContent = '"' + this.dataset.titulo + '"';
            new bootstrap.Modal(document.getElementById('modalEliminar')).show();
        });
    });

    // Confirmar eliminación via fetch
    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        if (!idPendiente) return;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';

        fetch(BASE_URL + '/index.php?route=admin/recetas/eliminar', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ receta_id: parseInt(idPendiente), csrf_token: CSRF }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                // Quitar fila de la tabla sin recargar
                const fila = document.querySelector(`.btn-eliminar[data-id="${idPendiente}"]`)?.closest('tr');
                if (fila) fila.remove();
                bootstrap.Modal.getInstance(document.getElementById('modalEliminar')).hide();
                // Mostrar toast de éxito
                mostrarToast('Receta eliminada correctamente.', 'success');
            } else {
                mostrarToast(data.error || 'No se pudo eliminar la receta.', 'danger');
            }
        })
        .catch(() => mostrarToast('Error de conexión. Recarga la página.', 'danger'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-trash3 me-1"></i>Sí, eliminar';
            idPendiente = null;
        });
    });

    function mostrarToast(msg, tipo) {
        const t = document.createElement('div');
        t.className = `alert alert-${tipo} alert-dismissible position-fixed top-0 end-0 m-3 shadow`;
        t.style.cssText = 'z-index:9999;min-width:280px;animation:fadeInUp .25s ease';
        t.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
