<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';
require_once __DIR__ . '/../../models/receta.php';

$pageTitle = 'Explorar Recetas';
$model     = new Receta();

$filtros = [
    'q'          => trim($_GET['q']          ?? ''),
    'categoria'  => trim($_GET['categoria']  ?? ''),
    'pais'       => trim($_GET['pais']       ?? ''),
    'dificultad' => trim($_GET['dificultad'] ?? ''),
    'tiempo'     => trim($_GET['tiempo']     ?? ''),
];

$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$detalleId = (int)($_GET['id'] ?? 0);

if ($detalleId > 0) {
    $receta = $model->obtenerCompleta($detalleId);
} else {
    $receta = null;
}

$resultado = $model->listarFiltradas($filtros, $pagina);
$recetas   = $resultado['recetas'];
$totalPag  = $resultado['paginas'];
$total     = $resultado['total'];
$paisesDB  = $model->listarPaises();

$favoritosIds = [];
if (isLoggedIn()) {
    foreach ($model->favoritosDeUsuario($_SESSION['usuario_id']) as $f) {
        $favoritosIds[] = $f['id'];
    }
}
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

<?php if ($detalleId > 0 && $receta): ?>
    <div class="mb-3">
        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al catálogo
        </a>
    </div>

    <div class="card panel-card overflow-hidden mb-4">
        <div class="row g-0">
            <div class="col-lg-6">
                <?php if (!empty($receta['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($receta['imagen']) ?>"
                         class="img-fluid h-100 w-100" style="object-fit:cover;min-height:320px" alt="<?= htmlspecialchars($receta['titulo']) ?>">
                <?php else: ?>
                    <div class="h-100 d-flex align-items-center justify-content-center" style="min-height:320px;background:linear-gradient(135deg,var(--surface-3),var(--surface))">
                        <i class="bi bi-egg-fried fs-1" style="color:var(--primary)"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge rounded-pill" style="background:var(--primary);color:#fff"><?= htmlspecialchars($receta['categoria'] ?? 'Sin categoría') ?></span>
                        <span class="badge rounded-pill" style="background:var(--surface-3);color:var(--text)"><i class="bi bi-clock me-1"></i><?= (int)$receta['tiempo_preparacion'] ?> min</span>
                        <span class="badge rounded-pill" style="background:var(--surface-3);color:var(--text)"><i class="bi bi-bar-chart me-1"></i><?= htmlspecialchars($receta['dificultad']) ?></span>
                    </div>
                    <h2 class="fw-800 mb-2"><?= htmlspecialchars($receta['titulo']) ?></h2>
                    <p class="text-muted mb-3">por <?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></p>
                    <p class="mb-4"><?= htmlspecialchars($receta['descripcion'] ?? 'Sin descripción disponible.') ?></p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if (isLoggedIn()): ?>
                        <?php $detalleEsFavorito = $model->esFavorito((int)$_SESSION['usuario_id'], (int)$receta['id']); ?>
                        <button type="button"
                                class="btn btn-sm <?= $detalleEsFavorito ? 'btn-danger' : 'btn-outline-danger' ?> btn-fav"
                                data-id="<?= (int)$receta['id'] ?>"
                                aria-pressed="<?= $detalleEsFavorito ? 'true' : 'false' ?>">
                            <i class="bi <?= $detalleEsFavorito ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                            <span><?= $detalleEsFavorito ? 'Guardada en favoritos' : 'Me gusta' ?></span>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReporteReceta">
                            <i class="bi bi-flag me-1"></i>Reportar receta
                        </button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/index.php?route=login" class="btn btn-outline-danger btn-sm"> 
                            <i class="bi bi-flag me-1"></i>Inicia sesión para reportar
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 text-sm small">
                        <div class="col-6">
                            <div class="fw-700">Visitas</div>
                            <div class="text-muted"><?= number_format((int)($receta['visitas'] ?? 0)) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="fw-700">Valoración</div>
                            <div class="text-muted"><?= $receta['rating'] ? $receta['rating'] : 'Sin valorar' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3"><i class="bi bi-list-check me-2" style="color:var(--primary)"></i>Ingredientes</h5>
                    <?php if (!empty($receta['ingredientes'])): ?>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($receta['ingredientes'] as $ing): ?>
                                <li class="mb-2">
                                    <?php if (!empty($ing['cantidad'])): ?>
                                        <strong><?= htmlspecialchars($ing['cantidad']) ?><?= !empty($ing['unidad']) ? ' ' . htmlspecialchars($ing['unidad']) : '' ?></strong>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($ing['nombre']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No hay ingredientes registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3"><i class="bi bi-journal-text me-2" style="color:var(--secondary)"></i>Pasos</h5>
                    <?php if (!empty($receta['pasos'])): ?>
                        <ol class="mb-0 ps-3">
                            <?php foreach ($receta['pasos'] as $paso): ?>
                                <li class="mb-2"><?= htmlspecialchars($paso['descripcion']) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="text-muted mb-0">No hay pasos registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3"><i class="bi bi-star-fill me-2" style="color:var(--secondary)"></i>Valora esta receta</h5>
                    <?php if (isLoggedIn()): ?>
                    <form id="formValoracion" class="d-grid gap-2">
                        <input type="hidden" name="receta_id" value="<?= (int)$receta['id'] ?>">
                        <div class="d-flex gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="form-check-label">
                                <input type="radio" name="puntuacion" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                <span class="ms-1"><?= $i ?></span>
                            </label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comentario" class="form-control form-control-sm" rows="3" placeholder="Tu comentario (opcional)"></textarea>
                        <button class="btn btn-primary btn-sm" type="submit">Enviar valoración</button>
                    </form>
                    <?php else: ?>
                    <p class="text-muted mb-0">Inicia sesión para valorar y comentar esta receta.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3"><i class="bi bi-person-badge me-2" style="color:var(--primary)"></i>Sobre el chef</h5>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="fw-700"><?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($receta['chef_bio'] ?? 'Chef destacado en GastroDigital') ?></small>
                        </div>
                        <?php if (isLoggedIn() && (int)$_SESSION['usuario_id'] !== (int)$receta['chef_id']): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSeguirChef">Seguir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalValorarChef">
                            <i class="bi bi-star me-1"></i>Valorar chef
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card panel-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-800 mb-0"><i class="bi bi-chat-dots me-2" style="color:var(--accent)"></i>Comentarios</h5>
            </div>
            <?php if (isLoggedIn()): ?>
            <form id="formComentario" class="mb-4">
                <input type="hidden" name="receta_id" value="<?= (int)$receta['id'] ?>">
                <textarea name="contenido" class="form-control form-control-sm mb-2" rows="3" placeholder="Escribe un comentario..."></textarea>
                <button class="btn btn-primary btn-sm" type="submit">Publicar comentario</button>
            </form>
            <?php endif; ?>
            <?php if (!empty($receta['comentarios'])): ?>
                <?php foreach ($receta['comentarios'] as $c): ?>
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fw-700"><?= htmlspecialchars($c['autor'] ?? 'Usuario') ?></div>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small>
                            </div>
                            <?php if (isLoggedIn()): ?>
                            <button type="button" class="btn btn-link btn-sm p-0 text-danger" data-bs-toggle="modal" data-bs-target="#modalReporteComentario" data-objetivo-id="<?= (int)$c['id'] ?>">
                                <i class="bi bi-flag me-1"></i>Reportar
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 text-muted"><?= htmlspecialchars($c['contenido']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted mb-0">Aún no hay comentarios en esta receta.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Encabezado -->
    <div class="mb-4">
        <h2 class="fw-800 mb-1">Explorar Recetas</h2>
        <p class="text-muted">
            <?= $total ?> receta<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?>
        </p>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>/index.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="route" value="recetas">
                <div class="col-md-4">
                    <label class="form-label small fw-600">Buscar</label>
                    <input type="text" class="form-control" name="q"
                           placeholder="Nombre o ingrediente..."
                           value="<?= htmlspecialchars($filtros['q']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-600">País</label>
                    <select class="form-select" name="pais" onchange="this.form.submit()">
                        <option value="">Todos los países</option>
                        <?php foreach ($paisesDB as $pais): ?>
                        <option value="<?= htmlspecialchars($pais) ?>"
                            <?= $filtros['pais'] === $pais ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pais) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-600">Dificultad</label>
                    <select class="form-select" name="dificultad">
                        <option value="">Cualquiera</option>
                        <?php foreach (['Fácil','Media','Difícil'] as $d): ?>
                        <option value="<?= $d ?>" <?= $filtros['dificultad'] === $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-600">Tiempo</label>
                    <select class="form-select" name="tiempo">
                        <option value="">Todo</option>
                        <option value="15"  <?= $filtros['tiempo'] === '15'  ? 'selected' : '' ?>>≤ 15 min</option>
                        <option value="30"  <?= $filtros['tiempo'] === '30'  ? 'selected' : '' ?>>≤ 30 min</option>
                        <option value="60"  <?= $filtros['tiempo'] === '60'  ? 'selected' : '' ?>>≤ 1 hora</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <?php if (array_filter($filtros)): ?>
                    <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-secondary" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Grid de recetas -->
    <?php if (empty($recetas)): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <h5>No se encontraron recetas</h5>
        <p>Intenta con otros filtros o <a href="<?= BASE_URL ?>/index.php?route=recetas">ver todas</a>.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($recetas as $r):
            $catKey = strtolower($r['categoria'] ?? '');
            $color  = $CATEGORIAS_COLOR[$catKey] ?? '#c0392b';
            $esFav  = in_array($r['id'], $favoritosIds);
        ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card receta-card h-100 position-relative">
                <?php if ($r['imagen'] && file_exists(ROOT_PATH . '/uploads/' . $r['imagen'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                         class="card-img-top" alt="<?= htmlspecialchars($r['titulo']) ?>">
                <?php else: ?>
                    <div class="receta-img-placeholder"
                         style="background:linear-gradient(135deg,<?= $color ?>22,<?= $color ?>55)">
                        <i class="bi bi-egg-fried" style="color:<?= $color ?>"></i>
                    </div>
                <?php endif; ?>

                <span class="receta-badge"><?= htmlspecialchars($r['categoria'] ?? '') ?></span>
                <?php if ($r['destacada']): ?>
                <span class="position-absolute top-0 end-0 m-2 badge"
                      style="background:var(--secondary);color:#3b2a1a">
                    <i class="bi bi-star-fill me-1"></i>Destacada
                </span>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title fw-700 mb-1"><?= htmlspecialchars($r['titulo']) ?></h5>
                    <small class="text-muted">por <?= htmlspecialchars($r['chef_nombre'] ?? 'Chef') ?></small>
                    <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                        <span><i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min</span>
                        <span><i class="bi bi-bar-chart me-1"></i><?= $r['dificultad'] ?></span>
                        <?php if ($r['rating']): ?>
                        <span class="ms-auto fw-700" style="color:var(--secondary)">
                            <i class="bi bi-star-fill me-1"></i><?= $r['rating'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-0 pb-3 d-flex gap-2">
                    <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-eye me-1"></i>Ver receta
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <button class="btn btn-sm <?= $esFav ? 'btn-danger' : 'btn-outline-danger' ?> btn-fav"
                            data-id="<?= $r['id'] ?>"
                            title="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                            aria-label="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                            aria-pressed="<?= $esFav ? 'true' : 'false' ?>">
                        <i class="bi <?= $esFav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPag > 1): ?>
    <nav class="d-flex justify-content-center mt-5">
        <ul class="pagination">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPag; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPag ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

</div>
</main>

<!-- Modal reporte receta -->
<div class="modal fade" id="modalReporteReceta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reportar receta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formReporteReceta">
          <input type="hidden" name="tipo" value="receta">
          <input type="hidden" name="objetivo_id" value="<?= (int)$receta['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <select class="form-select" name="motivo" required>
              <option value="">Selecciona un motivo</option>
              <option value="contenido inapropiado">Contenido inapropiado</option>
              <option value="spam">Spam</option>
              <option value="copycat">Contenido duplicado o copiado</option>
              <option value="información falsa">Información falsa</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="3" placeholder="Explica brevemente el problema"></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Enviar reporte</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal valorar chef -->
<div class="modal fade" id="modalValorarChef" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Valorar chef</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formValorarChef">
          <input type="hidden" name="chef_id" value="<?= (int)$receta['chef_id'] ?>">
          <div class="mb-3">
            <label class="form-label">Puntuación</label>
            <select class="form-select" name="puntuacion">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> estrella<?= $i > 1 ? 's' : '' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Comentario</label>
            <textarea class="form-control" name="comentario" rows="3" placeholder="Tu opinión sobre el chef"></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Guardar valoración</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal reporte comentario -->
<div class="modal fade" id="modalReporteComentario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reportar comentario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formReporteComentario">
          <input type="hidden" name="tipo" value="comentario">
          <input type="hidden" name="objetivo_id" id="reporteComentarioId" value="0">
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <select class="form-select" name="motivo" required>
              <option value="">Selecciona un motivo</option>
              <option value="ofensivo">Ofensivo</option>
              <option value="spam">Spam</option>
              <option value="amenaza">Amenaza o acoso</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="3" placeholder="Describe el problema"></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Enviar reporte</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars(csrfToken()) ?>';

document.querySelectorAll('.btn-fav').forEach(btn => {
    btn.addEventListener('click', function () {
        const self = this;
        self.disabled = true;
        fetch('<?= BASE_URL ?>/index.php?route=favorito', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'receta_id=' + this.dataset.id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'No se pudo actualizar el favorito');
            return data;
        })
        .then(data => {
            if (!data || typeof data.activo !== 'boolean') throw new Error('Respuesta inválida');
            self.classList.toggle('btn-danger',         data.activo);
            self.classList.toggle('btn-outline-danger', !data.activo);
            self.querySelector('i').className = data.activo ? 'bi bi-heart-fill' : 'bi bi-heart';
            self.title = data.activo ? 'Quitar de favoritos' : 'Guardar en favoritos';
            self.setAttribute('aria-label', self.title);
            self.setAttribute('aria-pressed', data.activo ? 'true' : 'false');
            const label = self.querySelector('span');
            if (label) label.textContent = data.activo ? 'Guardada en favoritos' : 'Me gusta';
            self.disabled = false;
        })
        .catch(error => {
            self.disabled = false;
            alert(error.message || 'No se pudo guardar el favorito. Inténtalo de nuevo.');
        });
    });
});

const formValoracion = document.getElementById('formValoracion');
if (formValoracion) {
  formValoracion.addEventListener('submit', function (e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=valorar', {
      method: 'POST',
      body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(() => location.reload());
  });
}

const formComentario = document.getElementById('formComentario');
if (formComentario) {
  formComentario.addEventListener('submit', function (e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=comentar', {
      method: 'POST',
      body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(() => location.reload());
  });
}

const btnSeguirChef = document.getElementById('btnSeguirChef');
if (btnSeguirChef) {
  btnSeguirChef.addEventListener('click', function () {
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=seguirChef', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'chef_id=<?= (int)$receta['chef_id'] ?>&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
    }).then(r => r.json()).then(() => location.reload());
  });
}

const formValorarChef = document.getElementById('formValorarChef');
if (formValorarChef) {
  formValorarChef.addEventListener('submit', function (e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=valorarChef', {
      method: 'POST',
      body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(() => location.reload());
  });
}

const formReporteReceta = document.getElementById('formReporteReceta');
if (formReporteReceta) {
  formReporteReceta.addEventListener('submit', function (e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=reportar', {
      method: 'POST',
      body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(() => {
      alert('Reporte enviado correctamente.');
      bootstrap.Modal.getInstance(document.getElementById('modalReporteReceta')).hide();
    });
  });
}

const formReporteComentario = document.getElementById('formReporteComentario');
if (formReporteComentario) {
  formReporteComentario.addEventListener('submit', function (e) {\n    e.preventDefault();
    const data = new FormData(this);
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=reportar', {
      method: 'POST',
      body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(() => {
      alert('Reporte enviado correctamente.');
      bootstrap.Modal.getInstance(document.getElementById('modalReporteComentario')).hide();
    });
  });
}

const modalReporteComentario = document.getElementById('modalReporteComentario');
if (modalReporteComentario) {
  modalReporteComentario.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const input = document.getElementById('reporteComentarioId');
    input.value = btn.getAttribute('data-objetivo-id') || '0';
  });
}
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
