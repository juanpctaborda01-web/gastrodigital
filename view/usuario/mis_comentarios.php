<?php
/**
 * view/usuario/mis_comentarios.php
 * Mis Comentarios y Valoraciones
 */
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$pageTitle = 'Mis Comentarios';
$pdo       = Conexion::getInstancia()->getPDO();
$userId    = (int)$_SESSION['usuario_id'];
$baseUrl   = rtrim(BASE_URL, '/');

// ── Eliminar comentario propio ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_comentario'])) {
    verificarCsrf();
    $idCom = (int)($_POST['id_comentario'] ?? 0);
    if ($idCom) {
        try {
            $s = $pdo->prepare("DELETE FROM comentarios WHERE id_comentario = ? AND id_usuario = ?");
            $s->execute([$idCom, $userId]);
            if ($s->rowCount() === 0) {
                $pdo->prepare("DELETE FROM comentarios WHERE id = ? AND id_usuario = ?")->execute([$idCom, $userId]);
            }
            $_SESSION['exito'] = 'Comentario eliminado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo eliminar el comentario.';
        }
    }
    header('Location: ' . $baseUrl . '/index.php?route=mis-comentarios');
    exit;
}

// ── Cargar comentarios ────────────────────────────────────────────────────────
$comentarios = [];
try {
    $s = $pdo->prepare("
        SELECT c.id_comentario AS id,
               c.contenido, c.created_at, c.id_receta,
               r.titulo    AS receta_titulo,
               r.imagen    AS receta_imagen,
               r.dificultad,
               cat.nombre  AS categoria
        FROM comentarios c
        LEFT JOIN recetas    r   ON r.id_receta    = c.id_receta
        LEFT JOIN categorias cat ON cat.id_categoria = r.id_categoria
        WHERE c.id_usuario = ?
        ORDER BY c.created_at DESC
    ");
    $s->execute([$userId]);
    $comentarios = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $s = $pdo->prepare("
            SELECT c.id, c.contenido, c.created_at, c.id_receta,
                   r.titulo AS receta_titulo, r.imagen AS receta_imagen,
                   r.dificultad, NULL AS categoria
            FROM comentarios c
            LEFT JOIN recetas r ON r.id = c.id_receta
            WHERE c.id_usuario = ?
            ORDER BY c.created_at DESC
        ");
        $s->execute([$userId]);
        $comentarios = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {}
}

// ── Cargar valoraciones ───────────────────────────────────────────────────────
$valoraciones = [];
try {
    $s = $pdo->prepare("
        SELECT v.id_valoracion AS id,
               v.puntuacion, v.comentario, v.created_at, v.id_receta,
               r.titulo    AS receta_titulo,
               r.imagen    AS receta_imagen,
               r.dificultad,
               cat.nombre  AS categoria
        FROM valoraciones v
        LEFT JOIN recetas    r   ON r.id_receta    = v.id_receta
        LEFT JOIN categorias cat ON cat.id_categoria = r.id_categoria
        WHERE v.id_usuario = ?
        ORDER BY v.created_at DESC
    ");
    $s->execute([$userId]);
    $valoraciones = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $s = $pdo->prepare("
            SELECT v.id, v.puntuacion, v.comentario, v.created_at, v.id_receta,
                   r.titulo AS receta_titulo, r.imagen AS receta_imagen,
                   r.dificultad, NULL AS categoria
            FROM valoraciones v
            LEFT JOIN recetas r ON r.id = v.id_receta
            WHERE v.id_usuario = ?
            ORDER BY v.created_at DESC
        ");
        $s->execute([$userId]);
        $valoraciones = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {}
}

$tabInicial = (($_GET['tab'] ?? '') === 'valoraciones') ? 'valoraciones' : 'comentarios';
$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);
$csrfTok = csrfToken();
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="pb-5">
<div class="container">

    <?php if ($exito): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ══ HEADER HERO ═════════════════════════════════════════════════════ -->
    <div class="mc-header mt-3 mb-4">
        <div class="mc-header-icon">
            <i class="bi bi-chat-left-heart-fill"></i>
        </div>
        <div class="mc-header-text">
            <span class="section-tag" style="background:rgba(232,168,56,.12);color:rgba(255,220,120,.9)">
                <i class="bi bi-activity me-1"></i>Mi actividad
            </span>
            <h1 class="mc-title">Mis comentarios y valoraciones</h1>
            <p class="mc-subtitle">Todo lo que has escrito y puntuado en GastroDigital</p>
        </div>
        <div class="mc-stats">
            <div class="mc-stat">
                <span class="mc-stat-num"><?= count($comentarios) ?></span>
                <span class="mc-stat-label">Comentarios</span>
            </div>
            <div class="mc-stat-sep"></div>
            <div class="mc-stat">
                <span class="mc-stat-num" style="color:var(--gold)"><?= count($valoraciones) ?></span>
                <span class="mc-stat-label">Valoraciones</span>
            </div>
        </div>
    </div>

    <!-- ══ PESTAÑAS ════════════════════════════════════════════════════════ -->
    <div class="mc-tabs mb-4" role="tablist">
        <button class="mc-tab" id="mc-tab-com"
                role="tab" aria-selected="true"
                aria-controls="mc-panel-com"
                onclick="mcSwitch('comentarios')">
            <i class="bi bi-chat-dots"></i>
            Comentarios
            <?php if (count($comentarios)): ?>
            <span class="mc-tab-badge"><?= count($comentarios) ?></span>
            <?php endif; ?>
        </button>
        <button class="mc-tab" id="mc-tab-val"
                role="tab" aria-selected="false"
                aria-controls="mc-panel-val"
                onclick="mcSwitch('valoraciones')">
            <i class="bi bi-star-fill"></i>
            Valoraciones
            <?php if (count($valoraciones)): ?>
            <span class="mc-tab-badge mc-tab-badge--gold"><?= count($valoraciones) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ══ PANEL: COMENTARIOS ══════════════════════════════════════════════ -->
    <div id="mc-panel-com" role="tabpanel" aria-labelledby="mc-tab-com">

        <?php if (empty($comentarios)): ?>
        <div class="mc-empty">
            <div class="mc-empty-icon"><i class="bi bi-chat-dots"></i></div>
            <h4>Aún no has comentado ninguna receta</h4>
            <p>Cuando visites una receta y dejes tu opinión, aparecerá aquí.</p>
            <a href="<?= $baseUrl ?>/index.php?route=recetas" class="btn btn-primary mt-2">
                <i class="bi bi-search me-2"></i>Explorar recetas
            </a>
        </div>
        <?php else: ?>
        <p class="mc-panel-info">
            <i class="bi bi-info-circle me-1" style="color:var(--gold)"></i>
            Tienes <strong><?= count($comentarios) ?></strong>
            comentario<?= count($comentarios) !== 1 ? 's' : '' ?>.
            Puedes eliminar los que ya no quieras o reportar si ves algo incorrecto.
        </p>
        <div class="mc-list">
            <?php foreach ($comentarios as $c): ?>
            <div class="mc-item" id="com-<?= (int)$c['id'] ?>">
                <!-- Imagen -->
                <div class="mc-item-img">
                    <?php if (!empty($c['receta_imagen']) && file_exists(ROOT_PATH . '/uploads/' . $c['receta_imagen'])): ?>
                    <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($c['receta_imagen']) ?>"
                         alt="" loading="lazy">
                    <?php else: ?>
                    <div class="mc-item-img-placeholder"><i class="bi bi-egg-fried"></i></div>
                    <?php endif; ?>
                </div>
                <!-- Cuerpo -->
                <div class="mc-item-body">
                    <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$c['id_receta'] ?>"
                       class="mc-item-receta-link">
                        <i class="bi bi-journal-richtext me-1"></i>
                        <?= htmlspecialchars($c['receta_titulo'] ?? 'Receta eliminada') ?>
                    </a>
                    <?php if (!empty($c['categoria'])): ?>
                    <span class="mc-item-cat"><?= htmlspecialchars($c['categoria']) ?></span>
                    <?php endif; ?>
                    <p class="mc-item-texto">
                        <i class="bi bi-quote mc-quote-icon"></i>
                        <?= htmlspecialchars($c['contenido']) ?>
                    </p>
                    <div class="mc-item-meta">
                        <span class="mc-item-fecha">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('d \d\e F \d\e Y', strtotime($c['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <!-- Acciones -->
                <div class="mc-item-actions">
                    <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$c['id_receta'] ?>"
                       class="mc-btn-ver" title="Ver receta">
                        <i class="bi bi-eye"></i>
                    </a>
                    <button type="button"
                            class="mc-btn-rep"
                            title="Reportar comentario"
                            data-id="<?= (int)$c['id'] ?>"
                            data-receta-id="<?= (int)$c['id_receta'] ?>"
                            onclick="abrirReporteCom(this)">
                        <i class="bi bi-flag"></i>
                    </button>
                    <button type="button"
                            class="mc-btn-del"
                            title="Eliminar comentario"
                            data-id="<?= (int)$c['id'] ?>"
                            onclick="confirmarEliminar(this)">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ PANEL: VALORACIONES ═════════════════════════════════════════════ -->
    <div id="mc-panel-val" role="tabpanel" aria-labelledby="mc-tab-val" style="display:none">

        <?php if (empty($valoraciones)): ?>
        <div class="mc-empty">
            <div class="mc-empty-icon" style="background:linear-gradient(135deg,rgba(232,168,56,.15),rgba(211,84,0,.1))">
                <i class="bi bi-star-fill" style="color:var(--gold)"></i>
            </div>
            <h4>Aún no has valorado ninguna receta</h4>
            <p>Dale estrellas a las recetas que pruebes.</p>
            <a href="<?= $baseUrl ?>/index.php?route=recetas" class="btn btn-primary mt-2">
                <i class="bi bi-search me-2"></i>Explorar recetas
            </a>
        </div>
        <?php else: ?>
        <p class="mc-panel-info">
            <i class="bi bi-info-circle me-1" style="color:var(--gold)"></i>
            Has valorado <strong><?= count($valoraciones) ?></strong>
            receta<?= count($valoraciones) !== 1 ? 's' : '' ?>.
        </p>
        <div class="mc-list">
            <?php foreach ($valoraciones as $v):
                $pts = (int)$v['puntuacion'];
            ?>
            <div class="mc-item">
                <!-- Imagen -->
                <div class="mc-item-img">
                    <?php if (!empty($v['receta_imagen']) && file_exists(ROOT_PATH . '/uploads/' . $v['receta_imagen'])): ?>
                    <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($v['receta_imagen']) ?>"
                         alt="" loading="lazy">
                    <?php else: ?>
                    <div class="mc-item-img-placeholder"
                         style="background:linear-gradient(135deg,rgba(232,168,56,.12),rgba(232,168,56,.22))">
                        <i class="bi bi-star-fill" style="color:var(--gold)"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Cuerpo -->
                <div class="mc-item-body">
                    <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$v['id_receta'] ?>"
                       class="mc-item-receta-link">
                        <i class="bi bi-journal-richtext me-1"></i>
                        <?= htmlspecialchars($v['receta_titulo'] ?? 'Receta eliminada') ?>
                    </a>
                    <?php if (!empty($v['categoria'])): ?>
                    <span class="mc-item-cat"><?= htmlspecialchars($v['categoria']) ?></span>
                    <?php endif; ?>
                    <!-- Estrellas -->
                    <div class="mc-stars" title="<?= $pts ?>/5">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="bi bi-star<?= $s <= $pts ? '-fill' : '' ?> mc-star<?= $s <= $pts ? ' mc-star--on' : ' mc-star--off' ?>"></i>
                        <?php endfor; ?>
                        <span class="mc-stars-num"><?= $pts ?>/5</span>
                    </div>
                    <?php if (!empty(trim($v['comentario'] ?? ''))): ?>
                    <p class="mc-item-texto mc-item-texto--small">
                        <i class="bi bi-quote mc-quote-icon"></i>
                        <?= htmlspecialchars($v['comentario']) ?>
                    </p>
                    <?php endif; ?>
                    <div class="mc-item-meta">
                        <span class="mc-item-fecha">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('d \d\e F \d\e Y', strtotime($v['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <!-- Acciones -->
                <div class="mc-item-actions">
                    <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= (int)$v['id_receta'] ?>"
                       class="mc-btn-ver" title="Ver receta">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->
</main>

<!-- Form oculto para eliminar comentario -->
<form method="POST" id="formElimCom" style="display:none">
    <?php csrfField(); ?>
    <input type="hidden" name="id_comentario" id="elimComId">
    <button type="submit" name="eliminar_comentario"></button>
</form>

<!-- Modal confirmación eliminar -->
<div class="modal fade" id="modalElimCom" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-body text-center p-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3"
             style="width:52px;height:52px;background:rgba(192,57,43,.1)">
            <i class="bi bi-trash3 fs-4 text-danger"></i>
        </div>
        <h6 class="fw-800 mb-1">¿Eliminar comentario?</h6>
        <p class="small text-muted mb-4">Esta acción no se puede deshacer.</p>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger btn-sm flex-grow-1" id="btnConfirmElimCom">
                <i class="bi bi-trash3 me-1"></i>Eliminar
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal reportar comentario -->
<div class="modal fade" id="modalReporteCom" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title fw-800">
            <i class="bi bi-flag text-warning me-2"></i>Reportar comentario
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="text-muted small mb-3">Ayuda a mantener la comunidad sana. Tu reporte es anónimo.</p>
        <form id="formReporteCom">
          <input type="hidden" name="tipo" value="comentario">
          <input type="hidden" name="objetivo_id" id="reporteComId" value="0">
          <div class="mb-3">
            <label class="form-label fw-600 small">Motivo</label>
            <select class="form-select" name="motivo" required>
              <option value="">Selecciona un motivo</option>
              <option value="ofensivo">Ofensivo o agresivo</option>
              <option value="spam">Spam o publicidad</option>
              <option value="amenaza">Amenaza o acoso</option>
              <option value="inapropiado">Contenido inapropiado</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Descripción <span class="text-muted">(opcional)</span></label>
            <textarea class="form-control" name="descripcion" rows="3"
                      placeholder="Explica brevemente el problema"></textarea>
          </div>
          <div class="d-grid">
            <button class="btn btn-warning fw-700 text-dark" type="submit" id="btnEnviarReporte">
                <i class="bi bi-flag me-1"></i>Enviar reporte
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Toast notificación -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
  <div id="toastMC" class="toast align-items-center border-0 text-white" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-600" id="toastMCBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<style>
/* ── Header ── */
.mc-header {
    display: flex; align-items: center; gap: 1.1rem;
    flex-wrap: wrap;
    padding: 1.4rem 1.75rem;
    background: linear-gradient(130deg, #2e1a0a 0%, #5c2d0e 50%, #3b1a06 100%);
    border-radius: var(--r-lg);
    box-shadow: 0 6px 24px rgba(0,0,0,.18);
    position: relative; overflow: hidden;
}
.mc-header::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 85% 50%, rgba(232,168,56,.14) 0%, transparent 60%);
    pointer-events: none;
}
.mc-header-icon {
    width: 52px; height: 52px; border-radius: 13px;
    background: linear-gradient(135deg, var(--gold), var(--orange));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #fff;
    box-shadow: 0 4px 14px rgba(211,84,0,.35); flex-shrink: 0;
    position: relative; z-index: 1;
}
.mc-header-text { flex: 1; min-width: 0; position: relative; z-index: 1; }
.mc-title { font-size: clamp(1.05rem,2.5vw,1.35rem); font-weight: 800; color: #fff; margin: .25rem 0 .15rem; }
.mc-subtitle { color: rgba(255,255,255,.5); font-size: .82rem; margin: 0; }
.mc-stats { display: flex; align-items: center; gap: 1.2rem; position: relative; z-index: 1; }
.mc-stat { text-align: center; }
.mc-stat-num { display: block; font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1; }
.mc-stat-label { font-size: .67rem; color: rgba(255,255,255,.45); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
.mc-stat-sep { width: 1px; height: 36px; background: rgba(255,255,255,.15); }

/* ── Tabs ── */
.mc-tabs { display: flex; gap: .5rem; border-bottom: 2px solid var(--cream-3); padding-bottom: 0; }
.mc-tab {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .65rem 1.4rem;
    border: none; background: transparent;
    font-family: 'Nunito', sans-serif; font-size: .88rem; font-weight: 700;
    color: var(--text-m); cursor: pointer;
    border-radius: var(--r) var(--r) 0 0;
    transition: all .18s;
    position: relative; bottom: -2px;
    border-bottom: 3px solid transparent;
}
.mc-tab:hover { color: var(--orange); background: var(--cream-2); }
.mc-tab.is-active { color: var(--orange); border-bottom-color: var(--orange); background: rgba(211,84,0,.06); }
.mc-tab-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 5px;
    background: var(--cream-3); color: var(--text-m);
    border-radius: 10px; font-size: .65rem; font-weight: 800;
}
.mc-tab.is-active .mc-tab-badge { background: var(--orange); color: #fff; }
.mc-tab-badge--gold { background: rgba(232,168,56,.2); color: var(--gold-d); }
.mc-tab.is-active .mc-tab-badge--gold { background: var(--gold); color: var(--dark); }

/* ── Panel info ── */
.mc-panel-info {
    font-size: .83rem; color: var(--text-m);
    background: var(--cream-2); border-radius: 10px;
    padding: .6rem 1rem; margin-bottom: 1.2rem;
    border-left: 3px solid var(--gold);
}

/* ── Lista ── */
.mc-list { display: flex; flex-direction: column; gap: .75rem; }
.mc-item {
    display: flex; align-items: flex-start; gap: 1rem;
    background: #fff; border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 1rem;
    transition: box-shadow .2s, transform .2s, border-color .2s;
    animation: fadeInUp .3s ease backwards;
}
.mc-item:hover { border-color: var(--gold); box-shadow: 0 4px 18px rgba(232,168,56,.13); transform: translateY(-2px); }

/* Imagen */
.mc-item-img { width: 72px; height: 60px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: var(--cream-2); }
.mc-item-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.mc-item-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--text-m); opacity: .5; }

/* Cuerpo */
.mc-item-body { flex: 1; min-width: 0; }
.mc-item-receta-link {
    font-weight: 800; font-size: .88rem; color: var(--dark);
    text-decoration: none; display: inline-flex; align-items: center; gap: .25rem;
    transition: color .18s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 100%;
}
.mc-item-receta-link:hover { color: var(--orange); }
.mc-item-cat {
    display: inline-block; background: var(--cream-2); color: var(--text-m);
    font-size: .67rem; font-weight: 700; padding: .1rem .5rem;
    border-radius: 10px; margin-left: .4rem;
}
.mc-item-texto {
    position: relative; font-size: .88rem; color: var(--text);
    margin: .5rem 0 .4rem; padding-left: 1rem; line-height: 1.55;
    border-left: 3px solid var(--cream-3);
}
.mc-item-texto--small { font-size: .82rem; color: var(--text-m); }
.mc-quote-icon { color: var(--gold); font-size: .7rem; margin-right: .2rem; }
.mc-item-meta { display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
.mc-item-fecha { font-size: .75rem; color: var(--text-m); }

/* Acciones */
.mc-item-actions { display: flex; flex-direction: column; gap: .35rem; flex-shrink: 0; }
.mc-btn-ver, .mc-btn-del, .mc-btn-rep {
    width: 32px; height: 32px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: .82rem; border: 1.5px solid; transition: all .18s;
    cursor: pointer; text-decoration: none; background: transparent;
}
.mc-btn-ver  { border-color: rgba(52,152,219,.3); color: #2980b9; }
.mc-btn-ver:hover  { background: #3498db; color: #fff; border-color: #3498db; }
.mc-btn-del  { border-color: rgba(192,57,43,.25); color: var(--red); }
.mc-btn-del:hover  { background: var(--red); color: #fff; border-color: var(--red); }
.mc-btn-rep  { border-color: rgba(232,168,56,.35); color: var(--gold-d); }
.mc-btn-rep:hover  { background: var(--gold); color: #fff; border-color: var(--gold); }

/* Estrellas */
.mc-stars { display: flex; align-items: center; gap: 2px; margin: .45rem 0 .3rem; }
.mc-star { font-size: .95rem; }
.mc-star--on  { color: var(--gold); }
.mc-star--off { color: var(--cream-3); }
.mc-stars-num { font-size: .78rem; font-weight: 800; color: var(--gold-d); margin-left: .35rem; }

/* Estado vacío */
.mc-empty { text-align: center; padding: 4rem 2rem; color: var(--text-m); }
.mc-empty-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, var(--cream-2), var(--cream-3));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.9rem; color: var(--text-m); opacity: .5;
    margin: 0 auto 1.2rem;
}
.mc-empty h4 { font-weight: 800; font-size: 1.05rem; color: var(--dark); margin-bottom: .4rem; }
.mc-empty p  { font-size: .88rem; margin-bottom: 0; }

/* Responsive */
@media (max-width: 576px) {
    .mc-header { padding: 1.1rem; }
    .mc-stats  { width: 100%; justify-content: center; }
    .mc-item   { flex-wrap: wrap; }
    .mc-item-img { width: 52px; height: 44px; }
    .mc-item-actions { flex-direction: row; }
    .mc-tab    { padding: .5rem .9rem; font-size: .82rem; }
}
</style>

<script>
(function () {
    'use strict';
    const CSRF = <?= json_encode($csrfTok) ?>;

    /* ── Toast helper ───────────────────────────────────────────────── */
    function showToast(msg, ok) {
        const t = document.getElementById('toastMC');
        document.getElementById('toastMCBody').textContent = msg;
        t.className = `toast align-items-center border-0 text-white ${ok ? 'bg-success' : 'bg-danger'}`;
        bootstrap.Toast.getOrCreateInstance(t, {delay: 3200}).show();
    }

    /* ── Cambio de pestaña ──────────────────────────────────────────── */
    function mcSwitch(tab) {
        document.getElementById('mc-panel-com').style.display = tab === 'comentarios' ? '' : 'none';
        document.getElementById('mc-panel-val').style.display = tab === 'valoraciones' ? '' : 'none';
        document.getElementById('mc-tab-com').classList.toggle('is-active', tab === 'comentarios');
        document.getElementById('mc-tab-val').classList.toggle('is-active', tab === 'valoraciones');
        document.getElementById('mc-tab-com').setAttribute('aria-selected', tab === 'comentarios' ? 'true' : 'false');
        document.getElementById('mc-tab-val').setAttribute('aria-selected', tab === 'valoraciones' ? 'true' : 'false');
        history.replaceState({}, '', location.pathname + '?tab=' + tab);
    }
    window.mcSwitch = mcSwitch;
    mcSwitch(<?= json_encode($tabInicial) ?>);

    /* ── Animación stagger ──────────────────────────────────────────── */
    document.querySelectorAll('.mc-item').forEach((el, i) => {
        el.style.animationDelay = (i * 0.04) + 's';
    });

    /* ── Confirmar eliminación ──────────────────────────────────────── */
    let pendingDelId = null;
    const modalElim = new bootstrap.Modal(document.getElementById('modalElimCom'));

    window.confirmarEliminar = function (btn) {
        pendingDelId = btn.dataset.id;
        modalElim.show();
    };

    document.getElementById('btnConfirmElimCom').addEventListener('click', function () {
        if (!pendingDelId) return;
        document.getElementById('elimComId').value = pendingDelId;
        modalElim.hide();
        document.getElementById('formElimCom').submit();
    });

    /* ── Reportar comentario ────────────────────────────────────────── */
    const modalReporte = new bootstrap.Modal(document.getElementById('modalReporteCom'));

    window.abrirReporteCom = function (btn) {
        document.getElementById('reporteComId').value = btn.dataset.id;
        document.getElementById('formReporteCom').reset();
        document.getElementById('reporteComId').value = btn.dataset.id;
        modalReporte.show();
    };

    document.getElementById('formReporteCom').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnEnviarReporte');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando…';
        const data = new FormData(this);
        fetch('<?= $baseUrl ?>/controllers/RecetaController.php?accion=reportar', {
            method: 'POST',
            body: new URLSearchParams([...data.entries(), ['csrf_token', CSRF]])
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) throw new Error(d.error);
            modalReporte.hide();
            showToast('🚩 Reporte enviado. Lo revisaremos pronto.', true);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-flag me-1"></i>Enviar reporte';
        })
        .catch(err => {
            showToast(err.message || 'Error al enviar reporte', false);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-flag me-1"></i>Enviar reporte';
        });
    });

})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
