<?php
require_once __DIR__ . '/../../config/config.php';
requireRole(ROL_ADMIN);

$pageTitle = 'Moderación';
$pdo = Conexion::getInstancia()->getPDO();

// ── Acciones AJAX de moderación ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mod'])) {
    header('Content-Type: application/json; charset=utf-8');
    $tokenPost   = $_POST['csrf_token']    ?? '';
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    if (!$tokenPost || !hash_equals($tokenSesion, $tokenPost)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF inválido']); exit();
    }
    $accion = $_POST['accion_mod'];
    $id     = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID inválido']); exit(); }

    try {
        if ($accion === 'del_comentario') {
            $pdo->prepare("DELETE FROM comentarios WHERE id_comentario = ?")->execute([$id]);
            echo json_encode(['exito' => true]);
        } elseif ($accion === 'del_valoracion') {
            $pdo->prepare("DELETE FROM valoraciones WHERE id_valoracion = ?")->execute([$id]);
            echo json_encode(['exito' => true]);
        } elseif ($accion === 'estado_reporte') {
            $estado = $_POST['estado'] ?? '';
            if (!in_array($estado, ['revisado', 'desestimado'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Estado de reporte inválido']);
                exit();
            }
            $stmt = $pdo->prepare("
                UPDATE reportes
                SET estado = ?, revisado_por = ?
                WHERE id_reporte = ?
            ");
            $stmt->execute([$estado, (int)$_SESSION['usuario_id'], $id]);
            if ($stmt->rowCount() === 0) {
                $existe = $pdo->prepare("SELECT 1 FROM reportes WHERE id_reporte = ?");
                $existe->execute([$id]);
                if (!$existe->fetchColumn()) {
                    http_response_code(404);
                    echo json_encode(['error' => 'El reporte ya no existe']);
                    exit();
                }
            }
            echo json_encode(['exito' => true, 'estado' => $estado]);
        } else {
            echo json_encode(['error' => 'Acción desconocida']);
        }
    } catch (Throwable $e) {
        error_log('[Moderacion] No se pudo procesar la acción ' . $accion . ': ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo completar la acción']);
    }
    exit();
}

// ── Comentarios recientes para moderar ───────────────────────────────────────
$comentarios = [];
$errorComentarios = false;
try {
    $stmtCom = $pdo->query("
        SELECT c.id_comentario AS id, c.contenido, c.created_at,
               u.nombre AS autor, u.id_usuario AS autor_id, u.avatar AS autor_avatar,
               r.titulo AS receta_titulo, r.id_receta AS receta_id
        FROM comentarios c
        JOIN usuarios u ON u.id_usuario = c.id_usuario
        JOIN recetas  r ON r.id_receta  = c.id_receta
        ORDER BY c.created_at DESC LIMIT 50
    ");
    $comentarios = $stmtCom->fetchAll();
} catch (Throwable $e) {
    $errorComentarios = true;
    error_log('[Moderacion] No se pudieron cargar comentarios: ' . $e->getMessage());
}

// ── Valoraciones recientes ────────────────────────────────────────────────────
$valoraciones = [];
$errorValoraciones = false;
try {
    $stmtVal = $pdo->query("
        SELECT v.id_valoracion AS id, v.puntuacion, v.comentario, v.created_at,
               u.nombre AS autor, r.titulo AS receta_titulo, r.id_receta AS receta_id
        FROM valoraciones v
        JOIN usuarios u ON u.id_usuario = v.id_usuario
        JOIN recetas  r ON r.id_receta  = v.id_receta
        ORDER BY v.created_at DESC LIMIT 50
    ");
    $valoraciones = $stmtVal->fetchAll();
} catch (Throwable $e) {
    $errorValoraciones = true;
    error_log('[Moderacion] No se pudieron cargar valoraciones: ' . $e->getMessage());
}

// ── Reportes pendientes ───────────────────────────────────────────────────────
$reportes = [];
$errorReportes = false;
try {
    $stmtRep = $pdo->query("
        SELECT rep.id_reporte AS id, rep.motivo, rep.descripcion, rep.estado, rep.created_at,
               rep.id_receta, rep.id_comentario, rep.id_valoracion,
               rep.reportado_por,
               u_rep.nombre AS reportado_por_nombre,
               r.titulo AS receta_titulo,
               c.contenido AS comentario_reportado,
               v.puntuacion AS valoracion_reportada,
               v.comentario AS comentario_valoracion,
               u_obj.nombre AS usuario_reportado
        FROM reportes rep
        LEFT JOIN usuarios u_rep ON u_rep.id_usuario = rep.reportado_por
        LEFT JOIN recetas  r     ON r.id_receta = rep.id_receta
        LEFT JOIN comentarios c ON c.id_comentario = rep.id_comentario
        LEFT JOIN valoraciones v ON v.id_valoracion = rep.id_valoracion
        LEFT JOIN usuarios u_obj ON u_obj.id_usuario = rep.id_usuario
        ORDER BY CASE WHEN rep.estado = 'pendiente' THEN 0 ELSE 1 END,
                 rep.created_at DESC
        LIMIT 100
    ");
    $reportes = $stmtRep->fetchAll();
} catch (Throwable $e) {
    $errorReportes = true;
    error_log('[Moderacion] No se pudieron cargar reportes: ' . $e->getMessage());
}

$pendientes = array_filter($reportes, fn($r) => $r['estado'] === 'pendiente');

// ── Características ────────────────────────────────────────────────────────────
$caracteristicas = [];
try {
    $stmtCar = $pdo->query("SELECT id_caracteristica AS id, nombre, icono FROM caracteristicas ORDER BY nombre ASC");
    $caracteristicas = $stmtCar->fetchAll();
} catch (Throwable $e) {}

$baseUrl  = rtrim(BASE_URL, '/');
$csrfTok  = csrfToken();

// ── Tab activo ────────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'comentarios';
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<?php
$topbarBadge      = 'Admin';
$topbarBreadcrumb = [
    ['label'=>'Panel','href'=> $baseUrl . '/admin'],
    ['label'=>'Moderación'],
];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5" style="min-height:100vh;">
<div class="container-fluid px-4" style="max-width:1400px">

    <?php
    $phTag     = 'Moderación';
    $phTagImg  = $baseUrl . '/img/Icon/ICON-Sobrantes/moderador.png';
    $phTitle   = 'Moderación de contenido';
    $phSub     = 'Gestiona comentarios, valoraciones y reportes de usuarios'
               . (count($pendientes) > 0
                  ? ' · <span class="badge rounded-pill" style="background:rgba(192,57,43,.18);color:#c0392b;font-size:.72rem">' . count($pendientes) . ' reporte' . (count($pendientes) !== 1 ? 's' : '') . ' pendiente' . (count($pendientes) !== 1 ? 's' : '') . '</span>'
                  : '');
    $phArtImg  = $baseUrl . '/img/Icon/ICON-Sobrantes/moderador.png';
    include __DIR__ . '/../../assets/page_header.php';
    ?>

    <!-- ══ RESUMEN RÁPIDO ═════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:rgba(52,152,219,.12)">
                        <i class="bi bi-chat-dots" style="color:#2471a3;font-size:1.3rem"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4 lh-1"><?= count($comentarios) ?></div>
                        <div class="text-muted small">Comentarios</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:rgba(232,168,56,.12)">
                        <i class="bi bi-star-fill" style="color:var(--gold);font-size:1.3rem"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4 lh-1"><?= count($valoraciones) ?></div>
                        <div class="text-muted small">Valoraciones</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:rgba(192,57,43,.1)">
                        <i class="bi bi-flag-fill" style="color:var(--red);font-size:1.3rem"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4 lh-1"><?= count($pendientes) ?></div>
                        <div class="text-muted small">Reportes pendientes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:rgba(46,204,113,.1)">
                        <i class="bi bi-list-check" style="color:#1e8449;font-size:1.3rem"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4 lh-1"><?= count($caracteristicas) ?></div>
                        <div class="text-muted small">Características</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PESTAÑAS ═══════════════════════════════════════════════════════════ -->
    <ul class="nav nav-tabs mb-4 fw-700" id="modTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'comentarios' ? 'active' : '' ?>"
               href="?tab=comentarios">
                <i class="bi bi-chat-dots me-1"></i>Comentarios
                <span class="badge rounded-pill ms-1" style="background:rgba(52,152,219,.15);color:#2471a3;font-size:.7rem"><?= count($comentarios) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'valoraciones' ? 'active' : '' ?>"
               href="?tab=valoraciones">
                <i class="bi bi-star me-1"></i>Valoraciones
                <span class="badge rounded-pill ms-1" style="background:rgba(232,168,56,.15);color:var(--gold-d);font-size:.7rem"><?= count($valoraciones) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'reportes' ? 'active' : '' ?> position-relative"
               href="?tab=reportes">
                <i class="bi bi-flag me-1"></i>Reportes
                <?php if (count($pendientes)): ?>
                <span class="badge rounded-pill ms-1" style="background:var(--primary);color:#fff;font-size:.7rem"><?= count($pendientes) ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'caracteristicas' ? 'active' : '' ?>"
               href="?tab=caracteristicas">
                <i class="bi bi-list-check me-1"></i>Características
            </a>
        </li>
    </ul>

    <!-- ══ TAB: COMENTARIOS ═══════════════════════════════════════════════════ -->
    <?php if ($tab === 'comentarios'): ?>
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1c4068,#2471a3)">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="fw-800 mb-0 text-white">
                    <i class="bi bi-chat-dots me-2"></i>Gestión de Comentarios
                </h5>
                <span class="badge rounded-pill" style="background:rgba(255,255,255,.2);color:#fff"><?= count($comentarios) ?> registros</span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($errorComentarios): ?>
            <div class="alert alert-danger m-3 mb-0" role="alert">
                No se pudieron cargar los comentarios. Recarga la página o revisa el registro del servidor.
            </div>
            <?php elseif (empty($comentarios)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-dots fs-1 opacity-25 d-block mb-2"></i>
                <p class="mb-0">No hay comentarios para moderar.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaComentarios">
                    <thead style="background:#f8f9fc">
                        <tr>
                            <th class="ps-4" style="width:160px">Autor</th>
                            <th>Comentario</th>
                            <th style="width:180px">Receta</th>
                            <th style="width:100px">Fecha</th>
                            <th class="text-center pe-4" style="width:80px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comentarios as $c): ?>
                        <tr id="com-row-<?= $c['id'] ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center
                                                text-white fw-700 flex-shrink-0 overflow-hidden"
                                         style="width:32px;height:32px;background:linear-gradient(135deg,var(--primary),var(--secondary));font-size:.75rem">
                                        <?php $autorAvatarUrl = urlAvatarPerfil($c['autor_avatar'] ?? null); ?>
                                        <?php if ($autorAvatarUrl): ?>
                                            <img src="<?= htmlspecialchars($autorAvatarUrl) ?>"
                                                 alt="Avatar de <?= htmlspecialchars($c['autor']) ?>"
                                                 style="width:100%;height:100%;object-fit:cover"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                            <span style="display:none"><?= htmlspecialchars(mb_strtoupper(mb_substr($c['autor'], 0, 1))) ?></span>
                                        <?php else: ?>
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($c['autor'], 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="fw-700 small"><?= htmlspecialchars($c['autor']) ?></span>
                                </div>
                            </td>
                            <td>
                                <p class="mb-0 small" style="max-width:360px;line-height:1.5">
                                    <?= htmlspecialchars(mb_substr($c['contenido'], 0, 200)) ?>
                                    <?= mb_strlen($c['contenido']) > 200 ? '…' : '' ?>
                                </p>
                            </td>
                            <td>
                                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$c['receta_id'] ?>"
                                   class="text-muted small text-decoration-none d-flex align-items-center gap-1"
                                   target="_blank">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:.72rem"></i>
                                    <?= htmlspecialchars(mb_substr($c['receta_titulo'], 0, 40)) ?>
                                </a>
                            </td>
                            <td>
                                <span class="text-muted small"><?= date('d/m/Y\nH:i', strtotime($c['created_at'])) ?></span>
                            </td>
                            <td class="text-center pe-4">
                                <button class="btn btn-sm btn-outline-danger mod-del-btn"
                                        style="border-radius:8px;width:34px;height:34px;padding:0"
                                        data-accion="del_comentario"
                                        data-id="<?= $c['id'] ?>"
                                        data-row="com-row-<?= $c['id'] ?>"
                                        title="Eliminar comentario">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: VALORACIONES ══════════════════════════════════════════════════ -->
    <?php if ($tab === 'valoraciones'): ?>
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#7a5c00,#c98d20)">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="fw-800 mb-0 text-white">
                    <i class="bi bi-star-fill me-2"></i>Moderación de Valoraciones
                </h5>
                <span class="badge rounded-pill" style="background:rgba(255,255,255,.2);color:#fff"><?= count($valoraciones) ?> registros</span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($errorValoraciones): ?>
            <div class="alert alert-danger m-3 mb-0" role="alert">
                No se pudieron cargar las valoraciones. Recarga la página o revisa el registro del servidor.
            </div>
            <?php elseif (empty($valoraciones)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-star fs-1 opacity-25 d-block mb-2"></i>
                <p class="mb-0">No hay valoraciones para moderar.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#f8f9fc">
                        <tr>
                            <th class="ps-4" style="width:160px">Autor</th>
                            <th style="width:130px">Rating</th>
                            <th>Comentario</th>
                            <th style="width:180px">Receta</th>
                            <th style="width:100px">Fecha</th>
                            <th class="text-center pe-4" style="width:80px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valoraciones as $v): ?>
                        <tr id="val-row-<?= $v['id'] ?>">
                            <td class="ps-4">
                                <span class="fw-700 small"><?= htmlspecialchars($v['autor']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?= $i<=$v['puntuacion']?'-fill':'' ?>"
                                       style="font-size:.85rem;color:<?= $i<=$v['puntuacion']?'var(--gold)':'#ddd' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="fw-700 small ms-1" style="color:var(--gold-d)"><?= $v['puntuacion'] ?></span>
                                </div>
                            </td>
                            <td>
                                <p class="mb-0 small text-muted" style="max-width:300px">
                                    <?= htmlspecialchars(mb_substr($v['comentario'] ?? '—', 0, 180)) ?>
                                </p>
                            </td>
                            <td>
                                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$v['receta_id'] ?>"
                                   class="text-muted small text-decoration-none d-flex align-items-center gap-1"
                                   target="_blank">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:.72rem"></i>
                                    <?= htmlspecialchars(mb_substr($v['receta_titulo'], 0, 40)) ?>
                                </a>
                            </td>
                            <td>
                                <span class="text-muted small"><?= date('d/m/Y', strtotime($v['created_at'])) ?></span>
                            </td>
                            <td class="text-center pe-4">
                                <button class="btn btn-sm btn-outline-danger mod-del-btn"
                                        style="border-radius:8px;width:34px;height:34px;padding:0"
                                        data-accion="del_valoracion"
                                        data-id="<?= $v['id'] ?>"
                                        data-row="val-row-<?= $v['id'] ?>"
                                        title="Eliminar valoración">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: REPORTES ══════════════════════════════════════════════════════ -->
    <?php if ($tab === 'reportes'): ?>
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#6b1a1a,#c0392b)">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="fw-800 mb-0 text-white">
                    <i class="bi bi-flag-fill me-2"></i>Reportes de usuarios
                </h5>
                <?php if (count($pendientes)): ?>
                <span class="badge rounded-pill" style="background:rgba(255,255,255,.25);color:#fff">
                    <?= count($pendientes) ?> pendiente<?= count($pendientes) !== 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($errorReportes): ?>
            <div class="alert alert-danger m-3 mb-0" role="alert">
                No se pudieron cargar los reportes. Recarga la página o revisa el registro del servidor.
            </div>
            <?php elseif (empty($reportes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-flag fs-1 opacity-25 d-block mb-2"></i>
                <p class="mb-0">No hay reportes registrados.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#f8f9fc">
                        <tr>
                            <th class="ps-4">Reportado por</th>
                            <th>Tipo</th>
                            <th>Motivo</th>
                            <th>Descripción</th>
                            <th>Receta</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $rep): ?>
                        <tr id="reporte-row-<?= (int)$rep['id'] ?>">
                            <td class="ps-4">
                                <span class="fw-700 small"><?= htmlspecialchars($rep['reportado_por_nombre'] ?? 'Desconocido') ?></span>
                            </td>
                            <td>
                                <?php
                                $tipo_rep = $rep['id_receta'] ? 'receta' : ($rep['id_comentario'] ? 'comentario' : ($rep['id_valoracion'] ? 'valoración' : 'usuario'));
                                $badge_color = ['receta'=>'primary','comentario'=>'info','valoración'=>'warning','usuario'=>'danger'][$tipo_rep] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge_color ?> rounded-pill" style="font-size:.7rem"><?= $tipo_rep ?></span>
                            </td>
                            <td><span class="small fw-600"><?= htmlspecialchars($rep['motivo'] ?? '') ?></span></td>
                            <td>
                                <p class="mb-0 small text-muted" style="max-width:250px">
                                    <?= htmlspecialchars(mb_substr($rep['descripcion'] ?? '', 0, 120)) ?>
                                </p>
                                <?php if ($rep['comentario_reportado']): ?>
                                <p class="mb-0 mt-1 small text-body" style="max-width:250px">
                                    Comentario: <?= htmlspecialchars(mb_substr($rep['comentario_reportado'], 0, 120)) ?>
                                </p>
                                <?php elseif ($rep['valoracion_reportada'] !== null): ?>
                                <p class="mb-0 mt-1 small text-body">
                                    Valoración: <?= (int)$rep['valoracion_reportada'] ?>/5
                                    <?= !empty($rep['comentario_valoracion'])
                                        ? ' · ' . htmlspecialchars(mb_substr($rep['comentario_valoracion'], 0, 100))
                                        : '' ?>
                                </p>
                                <?php elseif ($rep['usuario_reportado']): ?>
                                <p class="mb-0 mt-1 small text-body">
                                    Usuario: <?= htmlspecialchars($rep['usuario_reportado']) ?>
                                </p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rep['receta_titulo']): ?>
                                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$rep['id_receta'] ?>"
                                   class="text-muted small" target="_blank">
                                    <?= htmlspecialchars(mb_substr($rep['receta_titulo'], 0, 35)) ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $estadoColor = match ($rep['estado']) {
                                    'pendiente' => 'bg-warning text-dark',
                                    'desestimado' => 'bg-secondary',
                                    default => 'bg-success',
                                };
                                ?>
                                <span class="badge rounded-pill <?= $estadoColor ?>"
                                      style="font-size:.7rem">
                                    <?= ucfirst($rep['estado']) ?>
                                </span>
                            </td>
                            <td><span class="text-muted small"><?= date('d/m/Y', strtotime($rep['created_at'])) ?></span></td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Resolver reporte">
                                    <button type="button" class="btn btn-outline-success reporte-estado-btn"
                                            data-id="<?= (int)$rep['id'] ?>" data-estado="revisado"
                                            title="Marcar como revisado" aria-label="Marcar como revisado">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary reporte-estado-btn"
                                            data-id="<?= (int)$rep['id'] ?>" data-estado="desestimado"
                                            title="Desestimar reporte" aria-label="Desestimar reporte">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: CARACTERÍSTICAS ═══════════════════════════════════════════════ -->
    <?php if ($tab === 'caracteristicas'): ?>
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div class="card-header border-0 py-3 px-4 d-flex align-items-center justify-content-between"
             style="background:linear-gradient(135deg,#1a4d2e,#27ae60)">
            <h5 class="fw-800 mb-0 text-white">
                <i class="bi bi-list-check me-2"></i>Características culinarias
            </h5>
            <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3)"
                    onclick="alert('Funcionalidad en desarrollo')">
                <i class="bi bi-plus-circle me-1"></i>Nueva característica
            </button>
        </div>
        <div class="card-body p-4">
            <?php if (empty($caracteristicas)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-list-check fs-1 opacity-25 d-block mb-2"></i>
                <p class="mb-0">No hay características registradas.</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($caracteristicas as $car): ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="border rounded-3 p-3 text-center h-100 bg-light">
                        <i class="bi <?= htmlspecialchars($car['icono'] ?? 'bi-tag') ?> fs-3 text-secondary mb-2 d-block"></i>
                        <h6 class="fw-700 mb-0 small"><?= htmlspecialchars($car['nombre']) ?></h6>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->
</main>

<!-- Modal confirmación eliminar -->
<div class="modal fade" id="modalConfirmMod" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-body text-center p-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3"
             style="width:52px;height:52px;background:rgba(192,57,43,.1)">
            <i class="bi bi-trash3 fs-4 text-danger"></i>
        </div>
        <h6 class="fw-800 mb-1">¿Eliminar este contenido?</h6>
        <p class="small text-muted mb-4">Esta acción no se puede deshacer.</p>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger btn-sm flex-grow-1" id="btnConfirmElim">
                <i class="bi bi-trash3 me-1"></i>Eliminar
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Toast notificación -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
  <div id="toastMod" class="toast align-items-center border-0" role="alert" aria-live="assertive">
    <div class="d-flex">
      <div class="toast-body fw-600" id="toastModBody"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($csrfTok) ?>;
let pendingBtn = null;

function showToast(msg, ok = true) {
    const toast = document.getElementById('toastMod');
    const body  = document.getElementById('toastModBody');
    body.textContent = msg;
    toast.classList.toggle('bg-success', ok);
    toast.classList.toggle('text-white', ok);
    toast.classList.toggle('bg-danger', !ok);
    toast.classList.toggle('text-white', !ok);
    bootstrap.Toast.getOrCreateInstance(toast, {delay:3000}).show();
}

document.querySelectorAll('.mod-del-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        pendingBtn = btn;
        new bootstrap.Modal(document.getElementById('modalConfirmMod')).show();
    });
});

document.querySelectorAll('.reporte-estado-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const estadoOriginal = btn.dataset.estado;
        btn.disabled = true;
        try {
            const response = await fetch(location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion_mod: 'estado_reporte',
                    id: btn.dataset.id,
                    estado: estadoOriginal,
                    csrf_token: CSRF_TOKEN
                })
            });
            const data = await response.json();
            if (!response.ok || !data.exito) {
                throw new Error(data.error || 'No se pudo actualizar el reporte.');
            }
            showToast(estadoOriginal === 'revisado' ? 'Reporte marcado como revisado' : 'Reporte desestimado', true);
            setTimeout(() => location.reload(), 500);
        } catch (error) {
            showToast(error.message || 'Error de conexión', false);
            btn.disabled = false;
        }
    });
});

document.getElementById('btnConfirmElim').addEventListener('click', async () => {
    if (!pendingBtn) return;
    const accion = pendingBtn.dataset.accion;
    const id     = pendingBtn.dataset.id;
    const rowId  = pendingBtn.dataset.row;

    bootstrap.Modal.getInstance(document.getElementById('modalConfirmMod')).hide();
    pendingBtn.disabled = true;
    pendingBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
        const res  = await fetch(location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion_mod=${encodeURIComponent(accion)}&id=${id}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
        });
        const data = await res.json();

        if (data.exito) {
            const row = document.getElementById(rowId);
            if (row) {
                row.style.transition = 'opacity .3s,transform .3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => row.remove(), 300);
            }
            showToast('Eliminado correctamente', true);
        } else {
            showToast(data.error || 'Error al eliminar', false);
            pendingBtn.disabled = false;
            pendingBtn.innerHTML = '<i class="bi bi-trash3"></i>';
        }
    } catch (e) {
        showToast('Error de conexión', false);
        pendingBtn.disabled = false;
        pendingBtn.innerHTML = '<i class="bi bi-trash3"></i>';
    }
    pendingBtn = null;
});
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
