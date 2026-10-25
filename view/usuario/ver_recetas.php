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
$esChef = isLoggedIn() && hasRole(ROL_CHEF);
if ($esChef) {
    $filtros['excluir_chef_id'] = (int)$_SESSION['usuario_id'];
    $pageTitle = 'Recetas de otros chefs';
}

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
$chefAvatarUrl = $receta ? urlAvatarPerfil($receta['chef_avatar'] ?? null) : null;

$favoritosIds = [];
if (isLoggedIn()) {
    $recetaIdsVisibles = array_column($recetas, 'id');
    if ($receta) $recetaIdsVisibles[] = $receta['id'];
    $favoritosIds = $model->favoritosIds((int)$_SESSION['usuario_id'], $recetaIdsVisibles);
}
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

<?php if ($detalleId > 0 && $receta): ?>
    <?php
    // Datos de categoría para la vista de detalle
    $catNombreD = $receta['categoria'] ?? 'Otros';
    $catIconoD  = getCategoriaIcono($catNombreD);
    $catColorD  = getCategoriaColor($catNombreD);
    $isoFlagD   = strtolower($receta['pais_iso'] ?? '');
    $dColorD    = match($receta['dificultad'] ?? '') {
        'Fácil'   => '#27ae60',
        'Difícil' => '#c0392b',
        default   => '#f4ab2e',
    };
    ?>

    <!-- ── Botón volver ── -->
    <div class="mb-3">
        <a href="<?= BASE_URL ?>/index.php?route=recetas"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al catálogo
        </a>
    </div>

    <!-- ══ CABECERA: imagen + info principal (idéntico al panel admin) ═══════ -->
    <div class="card panel-card overflow-hidden mb-4">
        <div class="row g-0">

            <!-- Columna imagen -->
            <div class="col-lg-5 position-relative">
                <?php if (!empty($receta['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $receta['imagen'])): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($receta['imagen']) ?>"
                     alt="<?= htmlspecialchars($receta['titulo']) ?>"
                     class="w-100 h-100"
                     style="object-fit:cover;min-height:280px;max-height:420px"
                     onerror="this.parentNode.innerHTML='<div style=\'min-height:280px;background:var(--surface-3);display:flex;align-items:center;justify-content:center\'><img src=\'<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIconoD) ?>\' style=\'width:72px;opacity:.3\'></div>'">
                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100"
                     style="min-height:280px;background:linear-gradient(135deg,<?= $catColorD ?>15,<?= $catColorD ?>30)">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIconoD) ?>"
                         alt="<?= htmlspecialchars($catNombreD) ?>"
                         style="width:80px;height:80px;object-fit:contain;opacity:.4"
                         onerror="this.style.display='none'">
                </div>
                <?php endif; ?>

                <!-- Badges: estado arriba-izquierda -->
                <div class="position-absolute top-0 start-0 p-2 d-flex flex-column gap-1">
                    <?php if (!empty($receta['destacada'])): ?>
                    <span class="badge rounded-pill"
                          style="background:#f4ab2e;color:#3b2a1a;font-size:.72rem">
                        <i class="bi bi-star-fill me-1"></i>Destacada
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Badge categoría + bandera abajo-izquierda -->
                <div class="position-absolute bottom-0 start-0 p-2 d-flex align-items-center gap-2 receta-detail-badges">
                    <span class="receta-badge-icon">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIconoD) ?>"
                             alt="" class="receta-categoria-icono"
                             onerror="this.style.display='none'">
                        <span class="receta-categoria-nombre"><?= htmlspecialchars($catNombreD) ?></span>
                    </span>
                    <?php if ($isoFlagD): ?>
                    <img src="<?= BASE_URL ?>/img/icon/flags/4x3/<?= $isoFlagD ?>.svg"
                         alt="País"
                         style="width:28px;height:20px;border-radius:4px;border:2px solid rgba(255,255,255,.85);box-shadow:0 2px 6px rgba(0,0,0,.3)"
                         onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna info -->
            <div class="col-lg-7">
                <div class="card-body p-4">

                    <!-- País badge -->
                    <?php if (!empty($receta['pais_nombre'])): ?>
                    <div class="mb-2">
                        <span class="badge rounded-pill"
                              style="background:var(--surface-3);color:var(--text);font-size:.75rem">
                            <?php if ($isoFlagD): ?>
                            <img src="<?= BASE_URL ?>/img/icon/flags/4x3/<?= $isoFlagD ?>.svg"
                                 alt="" style="height:11px;margin-right:4px;vertical-align:middle"
                                 onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?= htmlspecialchars($receta['pais_nombre']) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Título -->
                    <h1 class="fw-800 mb-1"
                        style="font-size:clamp(1.4rem,3vw,2rem);color:var(--text)">
                        <?= htmlspecialchars($receta['titulo']) ?>
                    </h1>

                    <!-- Chef con avatar -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <?php if ($chefAvatarUrl): ?>
                        <img src="<?= htmlspecialchars($chefAvatarUrl) ?>" alt=""
                             class="rounded-circle flex-shrink-0"
                             style="width:30px;height:30px;object-fit:cover">
                        <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle
                                     text-white fw-800 flex-shrink-0"
                              style="width:30px;height:30px;background:var(--primary);font-size:.75rem">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($receta['chef_nombre'] ?? 'C', 0, 1))) ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-muted small">
                            por <strong><?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></strong>
                        </span>
                    </div>

                    <!-- Descripción -->
                    <?php if (!empty($receta['descripcion'])): ?>
                    <p class="mb-4" style="color:var(--text);line-height:1.7">
                        <?= htmlspecialchars($receta['descripcion']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Stats en grid (igual al admin) -->
                    <div class="row g-2 text-center mb-4">
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:var(--primary);font-size:1.1rem">
                                    <?= (int)$receta['tiempo_preparacion'] ?> min
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-clock me-1"></i>Tiempo
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:var(--secondary);font-size:1.1rem">
                                    <?= (int)($receta['porciones'] ?? 0) ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-people me-1"></i>Porciones
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:<?= $dColorD ?>;font-size:1.1rem">
                                    <?= htmlspecialchars($receta['dificultad'] ?? '—') ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-bar-chart me-1"></i>Nivel
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($receta['calorias'])): ?>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="font-size:1.1rem">
                                    <?= (int)$receta['calorias'] ?> kcal
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/postres.png"
                                         style="width:12px;height:12px;margin-right:2px"
                                         onerror="this.style.display='none'">Calorías
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($receta['rating'])): ?>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="color:#f4ab2e;font-size:1.1rem">
                                    <i class="bi bi-star-fill me-1"></i><?= $receta['rating'] ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <?= (int)($receta['total_valoraciones'] ?? 0) ?> valoración<?= ($receta['total_valoraciones'] ?? 0) != 1 ? 'es' : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--surface-3)">
                                <div class="fw-800" style="font-size:1.1rem">
                                    <?= number_format((int)($receta['visitas'] ?? 0)) ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-eye me-1"></i>Visitas
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (isLoggedIn()): ?>
                        <?php $detalleEsFavorito = in_array((int)$receta['id'], $favoritosIds, true); ?>
                        <button type="button"
                                class="btn btn-sm <?= $detalleEsFavorito ? 'btn-danger' : 'btn-outline-danger' ?> btn-fav"
                                data-id="<?= (int)$receta['id'] ?>"
                                aria-pressed="<?= $detalleEsFavorito ? 'true' : 'false' ?>">
                            <i class="bi bi-heart-fill me-1" aria-hidden="true"></i>
                            <span data-fav-label><?= $detalleEsFavorito ? 'Guardada en favoritos' : 'Me gusta' ?></span>
                        </button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/index.php?route=login"
                           class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-heart-fill me-1" aria-hidden="true"></i>
                            Iniciar sesión para guardar
                        </a>
                        <?php endif; ?>
                        <?php if (isLoggedIn()): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#modalReporteContenido"
                                data-tipo="receta" data-objetivo-id="<?= (int)$receta['id'] ?>">
                            <i class="bi bi-flag me-1"></i>Reportar receta
                        </button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/index.php?route=login" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-flag me-1"></i>Reportar receta
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($receta['video_url'])): ?>
                        <a href="<?= htmlspecialchars($receta['video_url']) ?>"
                           target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-play-circle me-1"></i>Ver video
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($receta['pasos'])): ?>
                        <a href="<?= BASE_URL ?>/index.php?route=cocinar&id=<?= (int)$receta['id'] ?>"
                           class="btn btn-sm fw-700"
                           style="background:linear-gradient(135deg,var(--primary),#b84700);color:#fff;border:none;box-shadow:0 4px 14px rgba(211,84,0,.28);gap:.35rem"
                           aria-label="Comenzar a cocinar <?= htmlspecialchars($receta['titulo']) ?>">
                            <i class="bi bi-fire me-1" aria-hidden="true"></i>
                            Comenzar a cocinar
                        </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ══ INGREDIENTES + PASOS ═══════════════════════════════════════════════ -->
    <div class="row g-4 mb-4">

        <!-- Ingredientes (lista-grupo idéntica al admin) -->
        <div class="col-lg-5">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/ensalada.png"
                         alt="" style="width:20px;height:20px;object-fit:contain"
                         onerror="this.style.display='none'">
                    <h5 class="mb-0 fw-800">Ingredientes</h5>
                    <span class="badge bg-secondary rounded-pill ms-auto" style="font-size:.72rem">
                        <?= count($receta['ingredientes'] ?? []) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($receta['ingredientes'])): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($receta['ingredientes'] as $ing): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 py-2"
                            style="border-color:var(--border)">
                            <span style="color:var(--primary)">•</span>
                            <span class="flex-grow-1"><?= htmlspecialchars($ing['nombre']) ?></span>
                            <?php if (!empty($ing['cantidad']) || !empty($ing['unidad'])): ?>
                            <span class="badge rounded-pill fw-600"
                                  style="background:var(--surface-3);color:var(--text);font-size:.72rem;white-space:nowrap">
                                <?= htmlspecialchars(trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? ''))) ?>
                            </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/ensalada.png"
                             style="width:36px;opacity:.2;display:block;margin:0 auto .5rem"
                             onerror="this.style.display='none'">
                        Sin ingredientes registrados.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pasos (numeración circular idéntica al admin) -->
        <div class="col-lg-7">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                         alt="" style="width:20px;height:20px;object-fit:contain"
                         onerror="this.style.display='none'">
                    <h5 class="mb-0 fw-800">Preparación</h5>
                    <span class="badge bg-secondary rounded-pill ms-auto" style="font-size:.72rem">
                        <?= count($receta['pasos'] ?? []) ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($receta['pasos'])): ?>
                    <ol class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <?php foreach ($receta['pasos'] as $paso): ?>
                        <li class="d-flex gap-3 align-items-start">
                            <span class="d-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width:32px;height:32px;border-radius:50%;
                                         background:linear-gradient(135deg,var(--primary),var(--secondary));
                                         color:#fff;font-weight:800;font-size:.85rem;margin-top:2px">
                                <?= (int)$paso['numero'] ?>
                            </span>
                            <div class="flex-grow-1">
                                <p class="mb-0" style="line-height:1.65;color:var(--text)">
                                    <?= htmlspecialchars($paso['descripcion']) ?>
                                </p>
                                <?php if (!empty($paso['duracion'])): ?>
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-clock me-1"></i><?= (int)$paso['duracion'] ?> min
                                </small>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else: ?>
                    <div class="text-center text-muted small py-3">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                             style="width:36px;opacity:.2;display:block;margin:0 auto .5rem"
                             onerror="this.style.display='none'">
                        Sin pasos registrados.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ VALORACIÓN + CHEF ══════════════════════════════════════════════════ -->
    <div class="row g-4 mb-4">

        <!-- Valorar receta -->
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <i class="bi bi-star-fill text-warning" aria-hidden="true"></i>
                    <h5 class="mb-0 fw-800">Valora esta receta</h5>
                </div>
                <div class="card-body">
                    <?php if (isLoggedIn()): ?>
                    <form id="formValoracion" class="d-grid gap-3">
                        <input type="hidden" name="receta_id" value="<?= (int)$receta['id'] ?>">
                        <div>
                            <label class="form-label small fw-600 mb-2">Tu puntuación</label>
                            <div class="d-flex align-items-center gap-2 val-star-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="cursor:pointer;margin:0">
                                    <input type="radio" name="puntuacion" value="<?= $i ?>"
                                           class="visually-hidden" <?= $i === 5 ? 'checked' : '' ?>>
                                    <i class="bi bi-star-fill val-star" data-val="<?= $i ?>"
                                       style="font-size:1.8rem;color:#ddd;cursor:pointer;transition:color .15s"></i>
                                </label>
                                <?php endfor; ?>
                                <span class="val-star-label ms-1 fw-700 small text-muted">5/5</span>
                            </div>
                        </div>
                        <textarea name="comentario" class="form-control form-control-sm" rows="3"
                                  placeholder="Tu comentario (opcional)"></textarea>
                        <button class="btn btn-primary btn-sm fw-700" type="submit">
                            <i class="bi bi-star-fill me-1" aria-hidden="true"></i>
                            Enviar valoración
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-star-fill text-warning" aria-hidden="true"
                           style="font-size:3rem;opacity:.3;display:block;margin:0 auto .75rem"></i>
                        <p class="text-muted small mb-3">Inicia sesión para valorar esta receta.</p>
                        <a href="<?= BASE_URL ?>/index.php?route=login" class="btn btn-primary btn-sm">
                            Iniciar sesión
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sobre el chef -->
        <div class="col-lg-6">
            <div class="card panel-card h-100">
                <div class="card-header py-3 d-flex align-items-center gap-2"
                     style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/cheque.png"
                         alt="" style="width:20px;height:20px;object-fit:contain"
                         onerror="this.style.display='none'">
                    <h5 class="mb-0 fw-800">Sobre el chef</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <?php if ($chefAvatarUrl): ?>
                        <img src="<?= htmlspecialchars($chefAvatarUrl) ?>"
                             alt="Avatar" class="rounded-circle flex-shrink-0"
                             style="width:52px;height:52px;object-fit:cover">
                        <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center rounded-circle
                                    flex-shrink-0 text-white fw-800"
                             style="width:52px;height:52px;background:linear-gradient(135deg,var(--primary),var(--secondary));font-size:1.2rem">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($receta['chef_nombre'] ?? 'C', 0, 1))) ?>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <div class="fw-700"><?= htmlspecialchars($receta['chef_nombre'] ?? 'Chef') ?></div>
                            <small class="text-muted">
                                <?= htmlspecialchars($receta['chef_bio'] ?? 'Chef destacado en GastroDigital') ?>
                            </small>
                        </div>
                        <?php if (isLoggedIn() && (int)$_SESSION['usuario_id'] !== (int)($receta['chef_id'] ?? 0)): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0"
                                id="btnSeguirChef">Seguir</button>
                        <?php endif; ?>
                    </div>
                    <?php if (isLoggedIn()): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                            data-bs-toggle="modal" data-bs-target="#modalValorarChef">
                        <i class="bi bi-star-fill me-1" aria-hidden="true"></i>
                        Valorar chef
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ COMENTARIOS ════════════════════════════════════════════════════════ -->
    <div class="card panel-card mb-4">
        <div class="card-header py-3 d-flex align-items-center gap-2"
             style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <i class="bi bi-chat-dots-fill" style="color:var(--accent);font-size:1.1rem"></i>
            <h5 class="mb-0 fw-800">Comentarios</h5>
            <span class="badge bg-secondary rounded-pill ms-auto" style="font-size:.72rem">
                <?= count($receta['comentarios'] ?? []) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (isLoggedIn()): ?>
            <form id="formComentario" class="mb-4">
                <input type="hidden" name="receta_id" value="<?= (int)$receta['id'] ?>">
                <textarea name="contenido" class="form-control form-control-sm mb-2" rows="3"
                          placeholder="Escribe un comentario..."></textarea>
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="bi bi-send me-1"></i>Publicar comentario
                </button>
            </form>
            <?php endif; ?>

            <?php if (!empty($receta['comentarios'])): ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($receta['comentarios'] as $c):
                    $autorAvatarUrl = urlAvatarPerfil($c['autor_avatar'] ?? null);
                ?>
                <div class="d-flex gap-3 align-items-start p-3 rounded-3"
                     style="background:var(--surface-2);border:1px solid var(--border)">
                    <?php if ($autorAvatarUrl): ?>
                    <img src="<?= htmlspecialchars($autorAvatarUrl) ?>"
                         alt="" class="rounded-circle flex-shrink-0"
                         style="width:38px;height:38px;object-fit:cover">
                    <?php else: ?>
                    <span class="d-flex align-items-center justify-content-center rounded-circle
                                 flex-shrink-0 text-white fw-800"
                          style="width:38px;height:38px;background:var(--primary);font-size:.85rem">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($c['autor'] ?? 'U', 0, 1))) ?>
                    </span>
                    <?php endif; ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-700 small"><?= htmlspecialchars($c['autor'] ?? 'Usuario') ?></span>
                            <small class="text-muted"><?= date('d/m/Y · H:i', strtotime($c['created_at'])) ?></small>
                            <?php if (isLoggedIn() && (int)$_SESSION['usuario_id'] !== (int)$c['id_usuario']): ?>
                            <button type="button" class="btn btn-link btn-sm text-muted ms-auto p-0"
                                    data-bs-toggle="modal" data-bs-target="#modalReporteContenido"
                                    data-tipo="comentario"
                                    data-objetivo-id="<?= (int)$c['id_comentario'] ?>"
                                    aria-label="Reportar comentario de <?= htmlspecialchars($c['autor'] ?? 'usuario') ?>"
                                    title="Reportar comentario">
                                <i class="bi bi-flag"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 small" style="color:var(--text);line-height:1.6">
                            <?= htmlspecialchars($c['contenido']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-chat-dots"
                   style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.75rem"></i>
                <p class="mb-0 small">Aún no hay comentarios. ¡Sé el primero!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($detalleId > 0 && !$receta): ?>
    <!-- Receta no encontrada -->
    <div class="mb-3">
        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al catálogo
        </a>
    </div>
    <div class="card border-0 shadow-sm text-center py-5" style="border-radius:18px">
        <div class="card-body">
            <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                 style="width:64px;opacity:.2;display:block;margin:0 auto 1rem"
                 onerror="this.style.display='none'">
            <h4 class="fw-800">Receta no encontrada</h4>
            <p class="text-muted mb-4">Es posible que esta receta haya sido eliminada o no esté disponible.</p>
            <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Explorar recetas
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- ══ CATÁLOGO CON FILTROS ══════════════════════════════════════════════ -->

    <?php
    $phTag     = 'Catálogo';
    $phTagImg  = BASE_URL . '/img/Icon/Categorias/plato-principal.png';
    $phTitle   = $esChef ? 'Recetas de otros chefs' : 'Explorar Recetas';
    $phSub     = $total . ' receta' . ($total !== 1 ? 's' : '') . ' encontrada' . ($total !== 1 ? 's' : '');
    $phArtImg  = BASE_URL . '/img/Icon/Categorias/plato-principal.png';
    include __DIR__ . '/../../assets/page_header.php';
    ?>

    <!-- Filtros -->
    <div class="gd-filtros-panel mb-4">
        <div class="gd-filtros-header">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= BASE_URL ?>/img/Icon/Categorias/salsas.png"
                     style="width:22px;height:22px;object-fit:contain"
                     onerror="this.style.display='none'">
                <h5 class="mb-0 fw-700">Filtros de búsqueda</h5>
            </div>
            <div class="gd-filtros-decoracion">
                <img src="<?= BASE_URL ?>/img/Icon/Categorias/postres.png" alt="" class="gd-filtro-icono">
                <img src="<?= BASE_URL ?>/img/Icon/Categorias/pasta.png"   alt="" class="gd-filtro-icono">
                <img src="<?= BASE_URL ?>/img/Icon/Categorias/sopa.png"    alt="" class="gd-filtro-icono">
            </div>
        </div>
        <form action="<?= BASE_URL ?>/index.php" method="GET" class="gd-filtros-form">
            <input type="hidden" name="route" value="recetas">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="gd-filtro-label">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                             style="width:14px;height:14px;margin-right:4px" onerror="this.style.display='none'">
                        Buscar por nombre o ingrediente
                    </label>
                    <input type="text" class="gd-filtro-input" name="q"
                           placeholder="Ej: Paella, Pasta, Arroz..."
                           value="<?= htmlspecialchars($filtros['q']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="gd-filtro-label">
                        <i class="bi bi-globe-americas me-1" aria-hidden="true"></i>
                        País de origen
                    </label>
                    <select class="gd-filtro-select" name="pais">
                        <option value="">🌍 Todos los países</option>
                        <?php foreach ($paisesDB as $pais): ?>
                        <option value="<?= htmlspecialchars($pais) ?>"
                            <?= $filtros['pais'] === $pais ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pais) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="gd-filtro-label">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/carne.png"
                             style="width:14px;height:14px;margin-right:4px" onerror="this.style.display='none'">
                        Nivel
                    </label>
                    <select class="gd-filtro-select" name="dificultad">
                        <option value="">Cualquiera</option>
                        <?php foreach (['Fácil','Media','Difícil'] as $d): ?>
                        <option value="<?= $d ?>" <?= $filtros['dificultad'] === $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="gd-filtro-label">
                        <i class="bi bi-hourglass-split me-1"></i>Tiempo máximo
                    </label>
                    <select class="gd-filtro-select" name="tiempo">
                        <option value="">Sin límite</option>
                        <option value="15"  <?= $filtros['tiempo'] === '15'  ? 'selected' : '' ?>>≤ 15 minutos</option>
                        <option value="30"  <?= $filtros['tiempo'] === '30'  ? 'selected' : '' ?>>≤ 30 minutos</option>
                        <option value="60"  <?= $filtros['tiempo'] === '60'  ? 'selected' : '' ?>>≤ 1 hora</option>
                        <option value="120" <?= $filtros['tiempo'] === '120' ? 'selected' : '' ?>>≤ 2 horas</option>
                    </select>
                </div>
            </div>
            <div class="gd-filtros-actions">
                <button type="submit" class="gd-btn-filtrar">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/salsas.png"
                         style="width:16px;height:16px;object-fit:contain;margin-right:6px;filter:brightness(0) invert(1)"
                         onerror="this.style.display='none'">
                    Aplicar filtros
                </button>
                <?php if (array_filter($filtros)): ?>
                <a href="<?= BASE_URL ?>/index.php?route=recetas" class="gd-btn-limpiar">
                    <i class="bi bi-x-circle me-2"></i>Limpiar todo
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Grid de recetas -->
    <?php if (empty($recetas)): ?>
    <div class="empty-state">
        <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
             style="width:64px;opacity:.2;display:block;margin:0 auto 1rem"
             onerror="this.style.display='none'">
        <h5>No se encontraron recetas</h5>
        <p>Intenta con otros filtros o <a href="<?= BASE_URL ?>/index.php?route=recetas">ver todas</a>.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($recetas as $r):
            $catNombre = !empty($r['categoria']) ? $r['categoria'] : 'Otros';
            $color     = getCategoriaColor($catNombre);
            $catIcono  = getCategoriaIcono($catNombre);
            $esFav     = in_array((int)$r['id'], $favoritosIds, true);
            $isoFlag   = strtolower($r['pais_iso'] ?? '');
            $rootPath  = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $imgField  = trim($r['imagen'] ?? '');
            $tieneImg  = $imgField && file_exists($rootPath . '/uploads/' . $imgField);
            $chefAvUrl = urlAvatarPerfil($r['chef_avatar'] ?? null);
        ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card receta-card h-100 position-relative" style="overflow:hidden">
                <?php if ($tieneImg): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($imgField) ?>"
                         class="card-img-top" alt="<?= htmlspecialchars($r['titulo']) ?>"
                         style="height:220px;object-fit:cover">
                <?php else: ?>
                    <div class="receta-img-placeholder"
                         style="height:220px;background:linear-gradient(135deg,<?= htmlspecialchars($color) ?>18,<?= htmlspecialchars($color) ?>38)">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIcono) ?>"
                             alt="<?= htmlspecialchars($catNombre) ?>"
                             style="width:56px;height:56px;object-fit:contain;opacity:.45"
                             onerror="this.style.display='none'">
                    </div>
                <?php endif; ?>

                <!-- Badge categoría -->
                <span class="receta-badge-icon">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIcono) ?>"
                         alt="" class="receta-categoria-icono" onerror="this.style.display='none'">
                    <span class="receta-categoria-nombre"><?= htmlspecialchars($catNombre) ?></span>
                </span>

                <!-- Badge destacada -->
                <?php if (!empty($r['destacada'])): ?>
                <span class="badge badge-destacada position-absolute"
                      style="top:10px;right:10px;z-index:3;font-size:.68rem">
                    <i class="bi bi-star-fill me-1"></i>Destacada
                </span>
                <?php endif; ?>

                <!-- Bandera -->
                <?php if ($isoFlag): ?>
                <img src="<?= BASE_URL ?>/img/icon/flags/4x3/<?= $isoFlag ?>.svg"
                     alt="País"
                     class="receta-flag-badge position-absolute"
                     style="<?= !empty($r['destacada']) ? 'top:42px;' : 'top:11px;' ?> right:11px;"
                     onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title fw-700 mb-1"><?= htmlspecialchars($r['titulo']) ?></h5>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <?php if ($chefAvUrl): ?>
                        <img src="<?= htmlspecialchars($chefAvUrl) ?>" alt=""
                             class="rounded-circle" style="width:22px;height:22px;object-fit:cover">
                        <?php endif; ?>
                        <small class="text-muted">
                            por <?= htmlspecialchars($r['chef_nombre'] ?? 'Chef desconocido') ?>
                        </small>
                    </div>
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
                    <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                       class="btn btn-primary btn-sm flex-grow-1">
                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                             style="width:14px;height:14px;object-fit:contain;margin-right:4px;filter:brightness(0) invert(1)"
                             onerror="this.style.display='none'">
                        Ver receta
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <button class="btn btn-sm <?= $esFav ? 'btn-danger' : 'btn-outline-danger' ?> btn-fav"
                            data-id="<?= $r['id'] ?>"
                            title="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                            aria-label="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                            aria-pressed="<?= $esFav ? 'true' : 'false' ?>">
                        <i class="bi bi-heart<?= $esFav ? '-fill' : '' ?>" aria-hidden="true"></i>
                    </button>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/index.php?route=login"
                       class="btn btn-sm btn-outline-danger"
                       title="Inicia sesión para guardar en favoritos"
                       aria-label="Inicia sesión para guardar en favoritos">
                        <i class="bi bi-heart" aria-hidden="true"></i>
                    </a>
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
                <a class="page-link"
                   href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPag; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link"
                   href="?<?= http_build_query(array_merge($filtros, ['pagina' => $p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPag ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])) ?>">
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

<!-- Modal valorar chef -->
<div class="modal fade" id="modalValorarChef" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800">
            <i class="bi bi-star-fill text-warning me-2" aria-hidden="true"></i>
            Valorar chef
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formValorarChef">
          <input type="hidden" name="chef_id" value="<?= $receta ? (int)$receta['chef_id'] : 0 ?>">
          <div class="mb-3">
            <label class="form-label fw-600 small">Puntuación</label>
            <div class="d-flex gap-2 align-items-center val-star-group">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <label class="form-check-label d-flex align-items-center" style="cursor:pointer">
                <input type="radio" name="puntuacion" value="<?= $i ?>"
                       class="visually-hidden" <?= $i === 5 ? 'checked' : '' ?>>
                <i class="bi bi-star-fill val-star" data-val="<?= $i ?>"
                   style="font-size:1.5rem;color:#ddd;cursor:pointer;transition:color .15s"></i>
              </label>
              <?php endfor; ?>
              <span class="val-star-label ms-1 fw-700 small text-muted">5/5</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Comentario <span class="text-muted">(opcional)</span></label>
            <textarea class="form-control" name="comentario" rows="3"
                      placeholder="Tu opinión sobre el chef"></textarea>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary fw-700" type="submit">
                <i class="bi bi-star-fill me-1" aria-hidden="true"></i>
                Guardar valoración
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal para reportar recetas y comentarios -->
<div class="modal fade" id="modalReporteContenido" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800" id="tituloModalReporte">
            <i class="bi bi-flag text-warning me-2"></i><span id="textoTituloReporte">Reportar receta</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formReporteContenido">
          <input type="hidden" name="tipo" id="reporteTipo" value="receta">
          <input type="hidden" name="objetivo_id" id="reporteObjetivoId" value="0">
          <div class="mb-3">
            <label class="form-label fw-600 small">Motivo</label>
            <select class="form-select" name="motivo" required>
              <option value="">Selecciona un motivo</option>
              <option value="ofensivo">Ofensivo o agresivo</option>
              <option value="spam">Spam</option>
              <option value="amenaza">Amenaza o acoso</option>
              <option value="inapropiado">Contenido inapropiado</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Descripción <span class="text-muted">(opcional)</span></label>
            <textarea class="form-control" name="descripcion" rows="3"
                      placeholder="Describe el problema"></textarea>
          </div>
          <div class="d-grid">
            <button class="btn btn-warning fw-700 text-dark" type="submit">
              <i class="bi bi-flag me-1"></i>Enviar reporte
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars(csrfToken()) ?>';

/* ── Toast helper ─────────────────────────────────────────────── */
function showToastReceta(msg, ok = true) {
    let t = document.getElementById('toastRecetaGlobal');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toastRecetaGlobal';
        t.className = 'toast align-items-center border-0 position-fixed bottom-0 end-0 m-3';
        t.style.zIndex = 1100;
        t.setAttribute('role','alert');
        t.innerHTML = `<div class="d-flex"><div class="toast-body fw-600" id="toastRecetaBody"></div>
                       <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        document.body.appendChild(t);
    }
    t.className = `toast align-items-center border-0 position-fixed bottom-0 end-0 m-3 ${ok ? 'bg-success' : 'bg-danger'} text-white`;
    document.getElementById('toastRecetaBody').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t, {delay: 3000}).show();
}

/* ── Estrellas interactivas ───────────────────────────────────── */
(function() {
    document.querySelectorAll('.val-star-group').forEach(group => {
        const form = group.closest('form');
        const stars = group.querySelectorAll('.val-star');
        const label = group.querySelector('.val-star-label');
        let cur = Number(form?.querySelector('input[name="puntuacion"]:checked')?.value || 5);

        function setStars(val) {
            stars.forEach((star, index) => {
                star.style.color = index < val ? '#e8a838' : '#ddd';
            });
            if (label) label.textContent = val + '/5';
            const radio = form?.querySelector(`input[name="puntuacion"][value="${val}"]`);
            if (radio) radio.checked = true;
        }

        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', () => setStars(index + 1));
            star.addEventListener('mouseleave', () => setStars(cur));
            star.addEventListener('click', () => {
                cur = index + 1;
                setStars(cur);
            });
        });
        setStars(cur);
    });
})();

/* ── Valorar receta ───────────────────────────────────────────── */
const formValoracion = document.getElementById('formValoracion');
if (formValoracion) {
  formValoracion.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=valorar', {
      method: 'POST',
      body: new URLSearchParams([...new FormData(this).entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(d => {
        if (d.error) throw new Error(d.error);
        showToastReceta('✅ Valoración guardada', true);
        setTimeout(() => location.reload(), 700);
    }).catch(err => {
        showToastReceta(err.message || 'Error', false);
        if (btn) { btn.disabled = false; }
    });
  });
}

/* ── Comentar receta ──────────────────────────────────────────── */
const formComentario = document.getElementById('formComentario');
if (formComentario) {
  formComentario.addEventListener('submit', function (e) {
    e.preventDefault();
    const ta = this.querySelector('textarea[name="contenido"]');
    const btn = this.querySelector('button[type="submit"]');
    if (!ta || !ta.value.trim()) { ta && ta.classList.add('is-invalid'); return; }
    ta.classList.remove('is-invalid');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Publicando…'; }
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=comentar', {
      method: 'POST',
      body: new URLSearchParams([...new FormData(this).entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(d => {
        if (d.error) throw new Error(d.error);
        showToastReceta('💬 Comentario publicado', true);
        setTimeout(() => location.reload(), 700);
    }).catch(err => {
        showToastReceta(err.message || 'Error', false);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send me-1"></i>Publicar comentario'; }
    });
  });
}

/* ── Seguir chef ──────────────────────────────────────────────── */
const btnSeguirChef = document.getElementById('btnSeguirChef');
if (btnSeguirChef) {
  btnSeguirChef.addEventListener('click', function () {
    this.disabled = true;
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=seguirChef', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'chef_id=<?= $receta ? (int)$receta['chef_id'] : 0 ?>&csrf_token=' + encodeURIComponent(CSRF_TOKEN)
    }).then(r => r.json()).then(d => {
        showToastReceta(d.siguiendo ? '✅ Siguiendo al chef' : 'Dejaste de seguirle', true);
        setTimeout(() => location.reload(), 700);
    }).catch(() => { this.disabled = false; showToastReceta('Error', false); });
  });
}

/* ── Valorar chef ─────────────────────────────────────────────── */
const formValorarChef = document.getElementById('formValorarChef');
if (formValorarChef) {
  formValorarChef.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=valorarChef', {
      method: 'POST',
      body: new URLSearchParams([...new FormData(this).entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(d => {
        if (d.error) throw new Error(d.error);
        showToastReceta('⭐ Valoración del chef guardada', true);
        bootstrap.Modal.getInstance(document.getElementById('modalValorarChef')).hide();
        setTimeout(() => location.reload(), 800);
    }).catch(err => {
        showToastReceta(err.message || 'Error', false);
        if (btn) btn.disabled = false;
    });
  });
}

/* ── Reportar receta o comentario ────────────────────────────── */
const formReporteContenido = document.getElementById('formReporteContenido');
if (formReporteContenido) {
  formReporteContenido.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=reportar', {
      method: 'POST',
      body: new URLSearchParams([...new FormData(this).entries(), ['csrf_token', CSRF_TOKEN]])
    }).then(r => r.json()).then(d => {
        if (d.error) throw new Error(d.error);
        showToastReceta('🚩 Reporte enviado correctamente.', true);
        bootstrap.Modal.getInstance(document.getElementById('modalReporteContenido')).hide();
        this.reset();
        if (btn) { btn.disabled = false; }
    }).catch(err => {
        showToastReceta(err.message || 'Error', false);
        if (btn) btn.disabled = false;
    });
  });
}

/* ── Identificar contenido que se reporta ─────────────────────── */
const modalReporte = document.getElementById('modalReporteContenido');
if (modalReporte) {
  modalReporte.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('reporteTipo').value = btn.getAttribute('data-tipo') || 'receta';
    document.getElementById('reporteObjetivoId').value = btn.getAttribute('data-objetivo-id') || '0';
    document.getElementById('textoTituloReporte').textContent =
      btn.getAttribute('data-tipo') === 'comentario' ? 'Reportar comentario' : 'Reportar receta';
  });
}
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
