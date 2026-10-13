<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireRole(ROL_CHEF);

$pageTitle = 'Mis Recetas';
$model     = new Receta();
$userModel = new Usuario();
$nombre    = $_SESSION['usuario_nombre'] ?? 'Chef';
$userId    = $_SESSION['usuario_id'];
$perfil    = $userModel->buscarPorId($userId) ?: [];
$perfilNombre      = $perfil['nombre']         ?? $nombre;
$perfilAvatar      = $perfil['avatar']         ?? '';
$perfilAvatarUrl   = urlAvatarPerfil($perfilAvatar);
$perfilBio         = $perfil['bio']            ?? '';
$perfilRedes       = $perfil['redes_sociales'] ?? '';
$perfilEspecialidad= $perfil['especialidad']   ?? '';

// ── Filtro activo (todas / publicadas / borradores) ──────────────────────────
$filtro = $_GET['filtro'] ?? 'todas';

// Cargar todas las recetas del chef con métricas enriquecidas
$todas_recetas = $model->porChefConVisitas($userId);
$recetas = [];
switch ($filtro) {
    case 'borradores':
        foreach ($todas_recetas as $r) { if (!$r['publicada']) $recetas[] = $r; }
        break;
    case 'publicadas':
        foreach ($todas_recetas as $r) { if ($r['publicada'])  $recetas[] = $r; }
        break;
    default:
        $recetas = $todas_recetas;
        $filtro  = 'todas';
}

$comentarios = $model->comentariosPorChef($userId);

// ── Conteos para las tabs ───────────────────────────────────────────────────
$numTodas      = count($todas_recetas);
$numPublicadas = count(array_filter($todas_recetas, fn($r) => $r['publicada']));
$numBorradores = $numTodas - $numPublicadas;

// ── Stats GLOBALES del chef (siempre sobre todas las recetas) ───────────────
$totalFavoritos   = array_sum(array_column($todas_recetas, 'total_favoritos'));
$totalVisitas     = $model->visitasPorChef($userId);
$totalComentarios = $model->totalComentariosPorChef($userId);
$ratingVals       = array_filter(array_column($todas_recetas, 'rating'));
$promedioRating   = count($ratingVals)
    ? round(array_sum($ratingVals) / count($ratingVals), 1)
    : 0;

$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$difColor = ['Fácil' => 'success', 'Media' => 'warning', 'Difícil' => 'danger'];
$difIcon  = ['Fácil' => '🟢', 'Media' => '🟡', 'Difícil' => '🔴'];
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<?php
$topbarBadge      = 'Chef Cocina';
$topbarBreadcrumb = [['label'=>'Mi Cocina','href'=> BASE_URL . '/mis-recetas'],['label'=>'Dashboard Chef']];
$topbarAction     = ['label'=>'Nueva Receta','href'=> BASE_URL . '/crear-receta','icon'=>'bi-plus-circle'];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-5">
<div class="container-fluid px-3" style="max-width:1400px">

    <!-- ══ HERO CHEF ══════════════════════════════════════════════════════════ -->
    <div class="gd-dash-hero mb-4">
      <div class="gd-dash-hero-inner">
        <div class="gd-dash-hero-body">
          <div class="gd-dash-chip">
            <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png" alt="" style="width:18px;height:18px">
            Mi Espacio Creativo Culinario
          </div>
          <h1 class="gd-dash-hero-title">
            <?= $saludo ?>, Chef <?= htmlspecialchars($nombre) ?>
            <img src="<?= BASE_URL ?>/img/icon/Icon-Animados/saludo.gif" alt="👋" style="width:36px;height:36px;vertical-align:middle">
          </h1>
          <p class="gd-dash-hero-desc">Administra tus recetas, supervisa las valoraciones de tus comensales y crea nuevas experiencias gastronómicas.</p>
          <div class="gd-dash-hero-stats">
            <span class="gd-dash-hero-stat">
              <i class="bi bi-journal-richtext"></i><?= $numTodas ?> recetas
            </span>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat"><i class="bi bi-eye"></i><?= number_format($totalVisitas) ?> visitas</span>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat"><i class="bi bi-chat-dots"></i><?= $totalComentarios ?> comentarios</span>
            <?php if ($numBorradores > 0): ?>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat" style="color:rgba(255,200,80,.8)">
                <i class="bi bi-pencil-square"></i><?= $numBorradores ?> borrador<?= $numBorradores !== 1 ? 'es' : '' ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="gd-dash-hero-btns">
          <a href="<?= BASE_URL ?>/crear-receta" class="gd-hero-pill gd-hero-pill--gold">
            <i class="bi bi-plus-circle me-1"></i>Crear Nueva Receta
          </a>
        </div>
        <div class="gd-dash-hero-art">
          <img src="<?= BASE_URL ?>/img/Icon/Categorias/parrillada.png" alt=""
               style="width:110px;height:110px;opacity:.2;filter:brightness(5) drop-shadow(0 4px 16px rgba(232,168,56,.3))">
        </div>
      </div>
    </div>

    <!-- ══ STAT-CARDS (5 métricas) ════════════════════════════════════════════ -->
    <div class="row g-3 mb-4" id="estadisticas">
        <!-- Total recetas -->
        <div class="col-6 col-sm-4 col-lg">
            <div class="chef-stat-card chef-stat-orange">
                <div class="chef-stat-icon"><i class="bi bi-journal-richtext"></i></div>
                <div class="chef-stat-body">
                    <div class="chef-stat-num"><?= $numTodas ?></div>
                    <div class="chef-stat-label">Mis recetas</div>
                    <div class="chef-stat-sub"><?= $numPublicadas ?> publicadas · <?= $numBorradores ?> borradores</div>
                </div>
            </div>
        </div>
        <!-- Visitas totales -->
        <div class="col-6 col-sm-4 col-lg">
            <div class="chef-stat-card chef-stat-red">
                <div class="chef-stat-icon"><i class="bi bi-eye"></i></div>
                <div class="chef-stat-body">
                    <div class="chef-stat-num"><?= number_format($totalVisitas) ?></div>
                    <div class="chef-stat-label">Visitas totales</div>
                    <div class="chef-stat-sub">en todas tus recetas</div>
                </div>
            </div>
        </div>
        <!-- Favoritos -->
        <div class="col-6 col-sm-4 col-lg">
            <div class="chef-stat-card chef-stat-crimson">
                <div class="chef-stat-icon"><i class="bi bi-heart-fill"></i></div>
                <div class="chef-stat-body">
                    <div class="chef-stat-num"><?= number_format($totalFavoritos) ?></div>
                    <div class="chef-stat-label">Total favoritos</div>
                    <div class="chef-stat-sub">guardados por usuarios</div>
                </div>
            </div>
        </div>
        <!-- Comentarios -->
        <div class="col-6 col-sm-4 col-lg">
            <div class="chef-stat-card chef-stat-brown">
                <div class="chef-stat-icon"><i class="bi bi-chat-dots-fill"></i></div>
                <div class="chef-stat-body">
                    <div class="chef-stat-num"><?= number_format($totalComentarios) ?></div>
                    <div class="chef-stat-label">Comentarios</div>
                    <div class="chef-stat-sub">recibidos en total</div>
                </div>
            </div>
        </div>
        <!-- Rating promedio -->
        <div class="col-6 col-sm-4 col-lg">
            <div class="chef-stat-card chef-stat-gold">
                <div class="chef-stat-icon"><i class="bi bi-star-fill"></i></div>
                <div class="chef-stat-body">
                    <div class="chef-stat-num"><?= $promedioRating ?: '—' ?></div>
                    <div class="chef-stat-label">Rating promedio</div>
                    <div class="chef-stat-sub">
                        <?php if ($promedioRating): ?>
                        <?php for($s=1;$s<=5;$s++) echo $s<=$promedioRating ? '★' : '☆'; ?>
                        <?php else: ?>sin valoraciones aún<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($exito): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ══ LAYOUT: Tabla recetas + Sidebar ════════════════════════════════════ -->
    <div class="row g-4">

        <!-- ─── Tabla de recetas ──────────────────────────────────────────────── -->
        <div class="col-lg-8">

            <!-- Encabezado + tabs filtro -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h5 class="fw-800 mb-0">
                    <i class="bi bi-journal-richtext me-2" style="color:var(--primary)"></i>Mis recetas
                </h5>
                <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-globe me-1"></i>Catálogo público
                </a>
            </div>

            <!-- Tabs Todas / Publicadas / Borradores -->
            <div class="chef-filter-tabs mb-3">
                <a href="?filtro=todas"
                   class="chef-filter-tab <?= $filtro === 'todas' ? 'active' : '' ?>">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Todas
                    <span class="chef-filter-badge"><?= $numTodas ?></span>
                </a>
                <a href="?filtro=publicadas"
                   class="chef-filter-tab <?= $filtro === 'publicadas' ? 'active' : '' ?>">
                    <i class="bi bi-check-circle me-1"></i>Publicadas
                    <span class="chef-filter-badge chef-filter-badge--green"><?= $numPublicadas ?></span>
                </a>
                <a href="?filtro=borradores"
                   class="chef-filter-tab <?= $filtro === 'borradores' ? 'active' : '' ?>">
                    <i class="bi bi-pencil-square me-1"></i>Borradores
                    <?php if ($numBorradores > 0): ?>
                    <span class="chef-filter-badge chef-filter-badge--amber"><?= $numBorradores ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (empty($recetas)): ?>
            <!-- Estado vacío -->
            <div class="card border-0 shadow-sm" style="border-radius:16px">
                <div class="card-body text-center py-5">
                    <?php if ($filtro === 'borradores'): ?>
                        <i class="bi bi-pencil-square fs-1 text-muted opacity-25 d-block mb-3"></i>
                        <h5 class="fw-700">Sin borradores</h5>
                        <p class="text-muted small">Todas tus recetas están publicadas.</p>
                    <?php elseif ($filtro === 'publicadas'): ?>
                        <i class="bi bi-check-circle fs-1 text-muted opacity-25 d-block mb-3"></i>
                        <h5 class="fw-700">Sin recetas publicadas</h5>
                        <p class="text-muted small">Aún no tienes recetas publicadas.</p>
                    <?php else: ?>
                        <i class="bi bi-journal-plus fs-1 text-muted opacity-25 d-block mb-3"></i>
                        <h5 class="fw-700">Todavía no tienes recetas</h5>
                        <p class="text-muted small">Comparte tu primera creación con la comunidad.</p>
                        <a href="<?= BASE_URL ?>/crear-receta" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle me-2"></i>Crear primera receta
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ─── Tabla de recetas mejorada ───────────────────────────────── -->
            <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle chef-recetas-tabla">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width:40%">Receta</th>
                                <th>Estado</th>
                                <th><i class="bi bi-eye text-primary" title="Visitas"></i></th>
                                <th><i class="bi bi-star-fill text-warning" title="Rating"></i></th>
                                <th><i class="bi bi-heart-fill text-danger" title="Favoritos"></i></th>
                                <th><i class="bi bi-chat-dots" title="Comentarios" style="color:#7e665a"></i></th>
                                <th class="text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recetas as $r):
                                $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
                                $imgField = trim($r['imagen'] ?? '');
                                $tieneImg = $imgField && file_exists($rootPath . '/uploads/' . $imgField);
                            ?>
                            <tr class="chef-receta-row">
                                <!-- Receta + thumbnail -->
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Thumbnail -->
                                        <div class="chef-receta-thumb flex-shrink-0">
                                            <?php if ($tieneImg): ?>
                                                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($imgField) ?>"
                                                     alt="<?= htmlspecialchars($r['titulo']) ?>">
                                            <?php else: ?>
                                                <div class="chef-receta-thumb-ph">
                                                    <i class="bi bi-egg-fried"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Info texto -->
                                        <div class="min-w-0">
                                            <div class="fw-700 chef-receta-titulo"><?= htmlspecialchars($r['titulo']) ?></div>
                                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                <span class="chef-receta-cat">
                                                    <?= htmlspecialchars($r['categoria'] ?? 'Sin categoría') ?>
                                                </span>
                                                <span class="chef-dif-badge chef-dif-<?= strtolower($r['dificultad'] ?? 'facil') ?>">
                                                    <?= $difIcon[$r['dificultad']] ?? '' ?> <?= htmlspecialchars($r['dificultad'] ?? '') ?>
                                                </span>
                                                <span class="text-muted" style="font-size:.72rem">
                                                    <i class="bi bi-clock me-1"></i><?= (int)$r['tiempo_preparacion'] ?> min
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Estado publicada / borrador + toggle rápido -->
                                <td>
                                    <button type="button"
                                            class="chef-toggle-pub <?= $r['publicada'] ? 'pub' : 'bor' ?>"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-pub="<?= $r['publicada'] ? '1' : '0' ?>"
                                            title="Clic para <?= $r['publicada'] ? 'convertir en borrador' : 'publicar' ?>">
                                        <?php if ($r['publicada']): ?>
                                        <i class="bi bi-check-circle-fill me-1"></i>Publicada
                                        <?php else: ?>
                                        <i class="bi bi-pencil-square me-1"></i>Borrador
                                        <?php endif; ?>
                                    </button>
                                    <?php if ($r['destacada']): ?>
                                    <div class="mt-1">
                                        <span class="chef-destacada-badge">
                                            <i class="bi bi-star-fill me-1"></i>Destacada
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <!-- Visitas -->
                                <td>
                                    <span class="fw-700 small"><?= number_format((int)$r['visitas']) ?></span>
                                </td>
                                <!-- Rating -->
                                <td>
                                    <?php if ($r['rating']): ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-700 small" style="color:var(--gold-d)"><?= $r['rating'] ?></span>
                                        <i class="bi bi-star-fill" style="font-size:.7rem;color:var(--gold)"></i>
                                        <span class="text-muted" style="font-size:.68rem">(<?= $r['total_valoraciones'] ?>)</span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size:.8rem">—</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Favoritos -->
                                <td>
                                    <span class="small fw-600"><?= number_format((int)$r['total_favoritos']) ?></span>
                                </td>
                                <!-- Comentarios -->
                                <td>
                                    <span class="small fw-600"><?= number_format((int)$r['total_comentarios']) ?></span>
                                </td>
                                <!-- Acciones -->
                                <td class="text-center pe-3">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int)$r['id'] ?>"
                                           class="chef-accion-btn chef-accion-view" title="Ver receta">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/index.php?route=editar-receta&id=<?= (int)$r['id'] ?>"
                                           class="chef-accion-btn chef-accion-edit" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                                class="chef-accion-btn chef-accion-del"
                                                title="Eliminar receta"
                                                data-id="<?= (int)$r['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($r['titulo'], ENT_QUOTES) ?>"
                                                onclick="confirmarEliminarReceta(this)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /col-lg-8 -->

        <!-- ─── Sidebar: Perfil + Comentarios ────────────────────────────────── -->
        <div class="col-lg-4">

            <!-- Card perfil -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden" id="perfil">
                <div class="card-header border-0 py-3 px-4"
                     style="background:linear-gradient(135deg,#2e1a0a,#5c2d0e)">
                    <h6 class="fw-800 mb-0 text-white">
                        <i class="bi bi-person-badge me-2" style="color:var(--gold)"></i>Mi perfil
                    </h6>
                </div>
                <div class="card-body p-4 text-center">
                    <!-- Avatar -->
                    <div class="chef-avatar mb-3">
                        <?php if ($perfilAvatarUrl): ?>
                            <img src="<?= htmlspecialchars($perfilAvatarUrl) ?>" alt="<?= htmlspecialchars($perfilNombre) ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span style="display:none"><?= htmlspecialchars(mb_strtoupper(mb_substr($perfilNombre, 0, 1))) ?></span>
                        <?php else: ?>
                            <span><?= htmlspecialchars(mb_strtoupper(mb_substr($perfilNombre, 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-800 mb-1"><?= htmlspecialchars($nombre) ?></h5>
                    <p class="text-muted small mb-1"><?= htmlspecialchars($perfilEspecialidad ?: 'Chef · GastroDigital') ?></p>
                    <?php if ($perfilBio): ?>
                    <p class="small text-muted mb-3" style="font-size:.8rem;line-height:1.5">
                        <?= htmlspecialchars(mb_substr($perfilBio, 0, 100)) ?><?= mb_strlen($perfilBio) > 100 ? '…' : '' ?>
                    </p>
                    <?php endif; ?>
                    <span class="badge rounded-pill px-3 py-1 mb-3"
                          style="background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#1c0a00;font-size:.72rem">
                        <i class="bi bi-star-fill me-1"></i>Chef destacado
                    </span>

                    <!-- Mini estadísticas -->
                    <div class="row g-0 text-center mb-3 border rounded-3 overflow-hidden">
                        <div class="col-4 py-2 border-end">
                            <div class="fw-800 fs-5" style="color:var(--primary)"><?= $numTodas ?></div>
                            <div class="text-muted" style="font-size:.7rem">Recetas</div>
                        </div>
                        <div class="col-4 py-2 border-end">
                            <div class="fw-800 fs-5" style="color:#c0392b"><?= number_format($totalFavoritos) ?></div>
                            <div class="text-muted" style="font-size:.7rem">Favoritos</div>
                        </div>
                        <div class="col-4 py-2">
                            <div class="fw-800 fs-5" style="color:var(--gold-d)"><?= $promedioRating ?: '—' ?></div>
                            <div class="text-muted" style="font-size:.7rem">Rating</div>
                        </div>
                    </div>

                    <!-- Formulario editar perfil colapsable -->
                    <button class="btn btn-outline-primary btn-sm w-100 mb-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#formPerfilCollapse" aria-expanded="false">
                        <i class="bi bi-pencil me-1"></i>Editar perfil
                    </button>

                    <div class="collapse" id="formPerfilCollapse">
                        <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardarPerfil"
                              class="text-start mt-2" enctype="multipart/form-data">
                            <?php csrfField(); ?>
                            <input type="hidden" name="usuario_id" value="<?= (int)$userId ?>">
                            <div class="mb-2">
                                <label class="form-label small fw-600">Nombre</label>
                                <input type="text" name="nombre" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($perfilNombre) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-600">URL de foto</label>
                                <input type="text" name="avatar" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($perfilAvatar) ?>"
                                       placeholder="https://... o dejar vacío">
                                <input type="file" name="avatar_file" class="form-control form-control-sm mt-1"
                                       accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-600">Biografía</label>
                                <textarea name="bio" class="form-control form-control-sm" rows="2"
                                          placeholder="Describe tu estilo culinario..."><?= htmlspecialchars($perfilBio) ?></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-600">Especialidad</label>
                                <input type="text" name="especialidad" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($perfilEspecialidad) ?>"
                                       placeholder="Ej. Cocina italiana">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-600">Redes sociales</label>
                                <input type="text" name="redes_sociales" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($perfilRedes) ?>"
                                       placeholder="Instagram / TikTok">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-save me-1"></i>Guardar perfil
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Comentarios recientes -->
            <h6 class="fw-800 mb-3 d-flex align-items-center gap-2" id="comentarios">
                <span class="d-inline-flex align-items-center justify-content-center rounded-2"
                      style="width:28px;height:28px;background:rgba(211,84,0,.12)">
                    <i class="bi bi-chat-dots-fill" style="color:var(--primary);font-size:.9rem"></i>
                </span>
                Comentarios recientes
            </h6>

            <?php if (empty($comentarios)): ?>
            <div class="card border-0 shadow-sm" style="border-radius:14px">
                <div class="card-body text-center py-4">
                    <i class="bi bi-chat-dots fs-1 text-muted opacity-25 d-block mb-2"></i>
                    <p class="text-muted small mb-0">Aún no hay comentarios en tus recetas.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-2 mb-4">
                <?php foreach (array_slice($comentarios, 0, 8) as $c): ?>
                <div class="card border-0 shadow-sm chef-comentario-card">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="chef-com-avatar">
                                <?php $autorAvatarUrl = urlAvatarPerfil($c['autor_avatar'] ?? null); ?>
                                <?php if ($autorAvatarUrl): ?>
                                    <img src="<?= htmlspecialchars($autorAvatarUrl) ?>"
                                         alt="Avatar de <?= htmlspecialchars($c['autor']) ?>"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <span style="display:none"><?= htmlspecialchars(mb_strtoupper(mb_substr($c['autor'], 0, 1))) ?></span>
                                <?php else: ?>
                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($c['autor'], 0, 1))) ?>
                                <?php endif; ?>
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="fw-700 small d-block"><?= htmlspecialchars($c['autor']) ?></span>
                                <small class="text-muted" style="font-size:.68rem">
                                    <?= date('d/m/Y · H:i', strtotime($c['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                        <p class="mb-1 small" style="line-height:1.5;color:var(--text)">
                            "<?= htmlspecialchars(mb_substr($c['contenido'], 0, 110)) ?><?= mb_strlen($c['contenido']) > 110 ? '…' : '' ?>"
                        </p>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int)$c['id_receta'] ?>"
                           class="d-flex align-items-center gap-1 mt-1"
                           style="font-size:.72rem;color:var(--primary);text-decoration:none">
                            <i class="bi bi-journal-richtext"></i>
                            <?= htmlspecialchars(mb_substr($c['receta_titulo'], 0, 40)) ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($totalComentarios > 8): ?>
                <p class="text-muted small text-center mt-1 mb-0">
                    Y <?= $totalComentarios - 8 ?> comentario<?= $totalComentarios - 8 !== 1 ? 's' : '' ?> más…
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ── Valoraciones recibidas ──────────────────────────────────── -->
            <?php
            // Obtener las últimas valoraciones sobre recetas de este chef
            $valoracionesRecibidas = [];
            try {
                $pdo = Conexion::getInstancia()->getPDO();
                $pkR = 'id_receta'; $pkU = 'id_usuario'; $pkV = 'id_valoracion';
                $stmt = $pdo->prepare("
                    SELECT v.$pkV AS id, v.puntuacion, v.comentario, v.created_at,
                           u.nombre AS autor, u.avatar AS autor_avatar,
                           r.titulo AS receta_titulo, r.$pkR AS receta_id
                    FROM valoraciones v
                    JOIN recetas  r ON r.$pkR = v.$pkR
                    JOIN usuarios u ON u.$pkU = v.$pkU
                    WHERE r.id_chef = ?
                    ORDER BY v.created_at DESC LIMIT 5
                ");
                $stmt->execute([$userId]);
                $valoracionesRecibidas = $stmt->fetchAll();
            } catch (Throwable $e) {}
            ?>
            <h6 class="fw-800 mb-3 d-flex align-items-center gap-2 mt-4" id="valoraciones">
                <span class="d-inline-flex align-items-center justify-content-center rounded-2"
                      style="width:28px;height:28px;background:rgba(232,168,56,.15)">
                    <i class="bi bi-star-fill" style="color:var(--gold);font-size:.9rem"></i>
                </span>
                Últimas valoraciones
            </h6>
            <?php if (empty($valoracionesRecibidas)): ?>
            <div class="card border-0 shadow-sm" style="border-radius:14px">
                <div class="card-body text-center py-4">
                    <i class="bi bi-star fs-1 text-muted opacity-25 d-block mb-2"></i>
                    <p class="text-muted small mb-0">Aún no hay valoraciones en tus recetas.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($valoracionesRecibidas as $v):
                    $pts = (int)$v['puntuacion'];
                ?>
                <div class="card border-0 shadow-sm chef-comentario-card">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="chef-com-avatar" style="background:linear-gradient(135deg,#c98d20,#e8a838)">
                                    <?php $autorAvatarUrl = urlAvatarPerfil($v['autor_avatar'] ?? null); ?>
                                    <?php if ($autorAvatarUrl): ?>
                                        <img src="<?= htmlspecialchars($autorAvatarUrl) ?>"
                                             alt="Avatar de <?= htmlspecialchars($v['autor']) ?>"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <span style="display:none"><?= htmlspecialchars(mb_strtoupper(mb_substr($v['autor'], 0, 1))) ?></span>
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($v['autor'], 0, 1))) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="fw-700 small"><?= htmlspecialchars($v['autor']) ?></span>
                            </div>
                            <!-- Estrellas -->
                            <div class="d-flex gap-1">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi bi-star<?= $s <= $pts ? '-fill' : '' ?>"
                                   style="font-size:.75rem;color:<?= $s <= $pts ? 'var(--gold)' : '#ddd' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty(trim($v['comentario'] ?? ''))): ?>
                        <p class="mb-1 small" style="line-height:1.4;color:var(--text);font-style:italic">
                            "<?= htmlspecialchars(mb_substr($v['comentario'], 0, 100)) ?><?= mb_strlen($v['comentario']) > 100 ? '…' : '' ?>"
                        </p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int)$v['receta_id'] ?>"
                           class="d-flex align-items-center gap-1 mt-1"
                           style="font-size:.72rem;color:var(--primary);text-decoration:none">
                            <i class="bi bi-journal-richtext"></i>
                            <?= htmlspecialchars(mb_substr($v['receta_titulo'], 0, 40)) ?>
                        </a>
                        <small class="text-muted d-block mt-1" style="font-size:.67rem">
                            <?= date('d/m/Y · H:i', strtotime($v['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /col-lg-4 -->
    </div><!-- /row -->

</div>
</main>

<!-- Form oculto para eliminar receta (pasa por el router → misma sesión) -->
<form method="POST" id="formElimReceta" style="display:none"
      action="<?= BASE_URL ?>/index.php?route=eliminar-receta">
    <?php csrfField(); ?>
    <input type="hidden" name="receta_id" id="elimRecetaId">
</form>

<!-- Modal confirmación eliminar receta -->
<div class="modal fade" id="modalElimReceta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-body text-center p-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3"
             style="width:52px;height:52px;background:rgba(192,57,43,.1)">
            <i class="bi bi-trash3 fs-4 text-danger"></i>
        </div>
        <h6 class="fw-800 mb-1">¿Eliminar receta?</h6>
        <p class="small text-muted mb-1" id="modalElimTitulo"></p>
        <p class="small text-muted mb-4">Esta acción no se puede deshacer.</p>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger btn-sm flex-grow-1" id="btnConfirmElimReceta">
                <i class="bi bi-trash3 me-1"></i>Eliminar
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Chef stat cards ───────────────────────────────────────────────────────── */
.chef-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-radius: 14px;
    padding: 1.2rem 1.3rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,.18);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
}
.chef-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,0,0,.22);
}
.chef-stat-card::after {
    content: '';
    position: absolute;
    right: -15px; top: -15px;
    width: 90px; height: 90px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    pointer-events: none;
}
.chef-stat-orange  { background: linear-gradient(135deg, #d35400, #e8782a); }
.chef-stat-red     { background: linear-gradient(135deg, #c0392b, #e74c3c); }
.chef-stat-crimson { background: linear-gradient(135deg, #96281b, #c0392b); }
.chef-stat-brown   { background: linear-gradient(135deg, #7e665a, #a38776); }
.chef-stat-gold    { background: linear-gradient(135deg, #c98d20, #e8a838); }

.chef-stat-icon {
    font-size: 2rem;
    opacity: .85;
    flex-shrink: 0;
}
.chef-stat-body { min-width: 0; }
.chef-stat-num {
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
}
.chef-stat-label {
    font-size: .78rem;
    font-weight: 700;
    opacity: .88;
    margin-top: .15rem;
}
.chef-stat-sub {
    font-size: .68rem;
    opacity: .65;
    margin-top: .1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
@media (max-width: 576px) {
    .chef-stat-num { font-size: 1.45rem; }
    .chef-stat-card { padding: .9rem 1rem; gap: .7rem; }
    .chef-stat-icon { font-size: 1.5rem; }
}

/* ── Tabs filtro ──────────────────────────────────────────────────────────── */
.chef-filter-tabs {
    display: flex;
    gap: .5rem;
    padding: .35rem;
    background: #f5ede0;
    border-radius: 12px;
    width: fit-content;
}
.chef-filter-tab {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .42rem 1rem;
    border-radius: 9px;
    font-size: .82rem;
    font-weight: 700;
    color: #8b7355;
    text-decoration: none;
    transition: all .18s;
    white-space: nowrap;
}
.chef-filter-tab:hover { background: rgba(232,168,56,.18); color: #5a3010; }
.chef-filter-tab.active {
    background: linear-gradient(135deg, #d35400, #c0392b);
    color: #fff;
    box-shadow: 0 3px 10px rgba(211,84,0,.3);
}
.chef-filter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px; height: 20px;
    border-radius: 10px;
    padding: 0 6px;
    font-size: .68rem;
    font-weight: 800;
    background: rgba(0,0,0,.12);
    color: inherit;
}
.chef-filter-tab.active .chef-filter-badge { background: rgba(255,255,255,.25); color: #fff; }
.chef-filter-badge--green { background: rgba(46,204,113,.18) !important; color: #1e8449 !important; }
.chef-filter-badge--amber { background: rgba(232,168,56,.22) !important; color: #8a6200 !important; }
.chef-filter-tab.active .chef-filter-badge--green,
.chef-filter-tab.active .chef-filter-badge--amber { background: rgba(255,255,255,.25) !important; color: #fff !important; }

/* ── Tabla de recetas del chef ───────────────────────────────────────────── */
.chef-recetas-tabla { border-collapse: separate; border-spacing: 0; }
.chef-recetas-tabla thead tr th {
    background: linear-gradient(135deg, #2e1a0a, #4a2410);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .85rem 1rem;
    border: none;
}
.chef-recetas-tabla thead tr th:first-child { border-radius: 0; }
.chef-recetas-tabla tbody tr { transition: background .15s; }
.chef-recetas-tabla tbody tr:hover { background: rgba(232,168,56,.06); }
.chef-recetas-tabla tbody td {
    padding: .75rem 1rem;
    border-bottom: 1px solid #f0e8de;
    vertical-align: middle;
}
.chef-recetas-tabla tbody tr:last-child td { border-bottom: none; }

/* Thumbnail */
.chef-receta-thumb {
    width: 52px; height: 52px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}
.chef-receta-thumb img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .3s;
}
.chef-receta-row:hover .chef-receta-thumb img { transform: scale(1.08); }
.chef-receta-thumb-ph {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #f5ede0, #ecdcc8);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #c8a87a;
}

/* Título receta */
.chef-receta-titulo {
    font-size: .88rem;
    color: #2c1810;
    line-height: 1.3;
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Categoría badge */
.chef-receta-cat {
    display: inline-block;
    padding: .15rem .55rem;
    border-radius: 6px;
    font-size: .68rem;
    font-weight: 700;
    background: #f5ede0;
    color: #8b6520;
    white-space: nowrap;
}

/* Dificultad badge */
.chef-dif-badge {
    display: inline-block;
    padding: .15rem .55rem;
    border-radius: 6px;
    font-size: .68rem;
    font-weight: 700;
    white-space: nowrap;
}
.chef-dif-fácil  { background: rgba(46,204,113,.12); color: #1a8a44; }
.chef-dif-media  { background: rgba(232,168,56,.15); color: #8a6200; }
.chef-dif-difícil{ background: rgba(192,57,43,.1);   color: #c0392b; }

/* Estado badges */
.chef-estado-badge {
    display: inline-flex;
    align-items: center;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    white-space: nowrap;
}
.chef-estado-pub {
    background: rgba(46,204,113,.12);
    color: #1a8a44;
    border: 1px solid rgba(46,204,113,.25);
}
.chef-estado-bor {
    background: rgba(232,168,56,.12);
    color: #8a5e00;
    border: 1px solid rgba(232,168,56,.3);
}

/* Botones de acción en tabla */
.chef-accion-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px; height: 32px;
    border-radius: 8px;
    font-size: .88rem;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all .18s;
    text-decoration: none;
    background: transparent;
    padding: 0;
}
.chef-accion-view {
    color: #2471a3;
    border-color: rgba(52,152,219,.35);
}
.chef-accion-view:hover {
    background: rgba(52,152,219,.1);
    border-color: #2471a3;
    color: #2471a3;
}
.chef-accion-edit {
    color: #8b7355;
    border-color: rgba(139,115,85,.3);
}
.chef-accion-edit:hover {
    background: rgba(139,115,85,.1);
    border-color: #8b7355;
    color: #5a3e20;
}
.chef-accion-del {
    color: #c0392b;
    border-color: rgba(192,57,43,.3);
}
.chef-accion-del:hover {
    background: rgba(192,57,43,.1);
    border-color: #c0392b;
}

/* Avatar chef sidebar */
.chef-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    margin: 0 auto;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--gold-d));
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 16px rgba(211,84,0,.25);
}
.chef-avatar img { width: 100%; height: 100%; object-fit: cover; }
.chef-avatar span { color: #fff; font-size: 1.8rem; font-weight: 800; }

/* Comentario card */
.chef-comentario-card {
    border-radius: 12px !important;
    transition: transform .18s, box-shadow .18s;
}
.chef-comentario-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,.1) !important;
}
.chef-com-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--gold-d));
    color: #fff;
    font-weight: 800;
    font-size: .72rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.chef-com-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
/* ── Badges de estado en tabla (publicada / borrador / destacada) ──── */
/* Base compartida — mismo estilo que el badge "Destacada" original */
.chef-toggle-pub,
.chef-destacada-badge {
    display: inline-flex;
    align-items: center;
    padding: .12rem .55rem;
    border-radius: 20px;
    font-size: .68rem;
    font-weight: 800;
    white-space: nowrap;
    line-height: 1.5;
}
/* Publicada — verde suave */
.chef-toggle-pub {
    cursor: pointer;
    border: none;
    transition: filter .18s, transform .18s;
}
.chef-toggle-pub:hover { filter: brightness(.92); transform: scale(.97); }
.chef-toggle-pub.pub {
    background: rgba(46,204,113,.15);
    color: #1a7a3c;
}
/* Borrador — amarillo dorado (mismo tono que Destacada) */
.chef-toggle-pub.bor {
    background: rgba(232,168,56,.15);
    color: #7a5500;
}
/* Destacada — amarillo dorado */
.chef-destacada-badge {
    background: rgba(232,168,56,.15);
    color: #7a5500;
}
    justify-content: center;
    flex-shrink: 0;
}
</style>

<!-- Toast notificación -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
  <div id="toastMisRecetas" class="toast align-items-center border-0 text-white" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-600" id="toastMisRecetasBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
const CSRF_CHEF = <?= json_encode(csrfToken()) ?>;

/* ── Toast ─────────────────────────────────────────────────────────── */
function showToastChef(msg, ok) {
    const t = document.getElementById('toastMisRecetas');
    document.getElementById('toastMisRecetasBody').textContent = msg;
    t.className = `toast align-items-center border-0 text-white ${ok ? 'bg-success' : 'bg-danger'}`;
    bootstrap.Toast.getOrCreateInstance(t, {delay: 3000}).show();
}

/* ── Eliminar receta (POST estándar con modal de confirmación) ─────── */
let pendingElimId = null;
const modalElim = new bootstrap.Modal(document.getElementById('modalElimReceta'));

function confirmarEliminarReceta(btn) {
    pendingElimId = btn.dataset.id;
    document.getElementById('modalElimTitulo').textContent = '«' + btn.dataset.titulo + '»';
    modalElim.show();
}

document.getElementById('btnConfirmElimReceta').addEventListener('click', function () {
    if (!pendingElimId) return;
    document.getElementById('elimRecetaId').value = pendingElimId;
    modalElim.hide();
    document.getElementById('formElimReceta').submit();
});

/* ── Toggle publicada / borrador (AJAX) ───────────────────────────── */
document.querySelectorAll('.chef-toggle-pub').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id   = this.dataset.id;
        const self = this;
        self.disabled = true;
        self.style.opacity = '.5';
        try {
            const res  = await fetch('<?= BASE_URL ?>/controllers/RecetaController.php?accion=togglePublicada', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receta_id=${id}&csrf_token=${encodeURIComponent(CSRF_CHEF)}`
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            const pub = data.publicada;
            self.dataset.pub = pub ? '1' : '0';
            self.className = `chef-toggle-pub ${pub ? 'pub' : 'bor'}`;
            self.innerHTML = pub
                ? '<i class="bi bi-check-circle-fill me-1"></i>Publicada'
                : '<i class="bi bi-pencil-square me-1"></i>Borrador';
            self.title = pub ? 'Clic para convertir en borrador' : 'Clic para publicar';
            showToastChef(pub ? '✅ Receta publicada' : '📝 Guardada como borrador', true);
        } catch (e) {
            showToastChef(e.message || 'Error al cambiar estado', false);
        }
        self.disabled = false;
        self.style.opacity = '1';
    });
});
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
