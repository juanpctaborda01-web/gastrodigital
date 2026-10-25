<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireRole(ROL_ADMIN);

$pageTitle    = 'Dashboard Admin';
$extraCss     = 'admin-dashboard.css?v=' . filemtime(__DIR__ . '/../../styles/admin-dashboard.css');
$recetaModel  = new Receta();
$userModel    = new Usuario();
$nombre       = $_SESSION['usuario_nombre'] ?? 'Admin';
$userId       = (int)($_SESSION['usuario_id'] ?? 0);
$perfil       = $userModel->buscarPorId($userId) ?: [];
$perfilNombre = $perfil['nombre'] ?? $nombre;
$perfilAvatar = $perfil['avatar'] ?? '';
$perfilAvatarUrl = urlAvatarPerfil($perfilAvatar);
$perfilBio    = $perfil['bio'] ?? '';
$perfilRedes  = $perfil['redes_sociales'] ?? '';
$exito        = $_SESSION['exito'] ?? null;
$error        = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

// --- Datos del dashboard ---
$stats          = $recetaModel->statsAdmin();
$dashboardUsers = $userModel->dashboardStats();
$statsPorRol    = [
    'admin' => (int)($dashboardUsers['admin'] ?? 0),
    'chef'  => (int)($dashboardUsers['chef'] ?? 0),
    'usuario' => (int)($dashboardUsers['usuario'] ?? 0),
];
$totalUsuarios  = (int)($dashboardUsers['total_usuarios'] ?? array_sum($statsPorRol));
$totalCategorias = (int)($stats['categorias'] ?? 0);
$nuevos30       = (int)($dashboardUsers['nuevos_30'] ?? 0);
$masVistas      = $recetaModel->masVistas(8);
$topCategorias  = $recetaModel->topCategorias(8);
$usuariosRecientes = $userModel->usuariosRecientes(30, 6);
$recientes      = $recetaModel->listarFiltradas([], 1, 5)['recetas'];
$chartRoles = [
    'labels' => ['Administradores', 'Chefs', 'Usuarios'],
    'values' => [
        (int)($statsPorRol['admin'] ?? 0),
        (int)($statsPorRol['chef'] ?? 0),
        (int)($statsPorRol['usuario'] ?? 0),
    ],
];
$chartRecetas = [
    'labels' => ['Publicadas', 'Borradores'],
    'values' => [
        (int)$stats['publicadas'],
        max(0, (int)$stats['total'] - (int)$stats['publicadas']),
    ],
];
$chartCategorias = [
    'labels' => array_map(static fn(array $categoria): string => $categoria['nombre'], $topCategorias),
    'values' => array_map(static fn(array $categoria): int => (int)$categoria['total_recetas'], $topCategorias),
];

$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<?php
// Topbar admin
$topbarBadge = 'Admin';
$topbarBreadcrumb = [['label'=>'Inicio','href'=> BASE_URL . '/admin'],['label'=>'Administración'],['label'=>'Dashboard']];
$topbarLinks = [];
include __DIR__ . '/../../assets/topbar.php';
?>

<main class="pb-4">
<div class="container-fluid px-3 dashboard-shell">

    <!-- ══ HERO ADMIN ══ -->
    <div class="gd-dash-hero">
      <div class="gd-dash-hero-inner">
        <div class="gd-dash-hero-body">
          <div class="gd-dash-chip">
            <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png" alt="" style="width:18px;height:18px">
            Centro de Control Gastronómico
          </div>
          <h1 class="gd-dash-hero-title">
            <?= $saludo ?>, <?= htmlspecialchars($nombre) ?>
            <span aria-hidden="true">👋</span>
          </h1>
          <p class="gd-dash-hero-desc">Gestiona recetas, categorías, chefs y la actividad de la comunidad gastronómica en tiempo real.</p>
          <div class="gd-dash-hero-stats">
            <span class="gd-dash-hero-stat">
              <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png" alt="" style="width:13px;height:13px">
              <?= $stats['total'] ?> recetas
            </span>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat"><i class="bi bi-people"></i><?= $totalUsuarios ?> usuarios</span>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat"><i class="bi bi-tags"></i><?= $totalCategorias ?> categorías</span>
            <span class="gd-dash-hero-sep">•</span>
            <span class="gd-dash-hero-stat"><i class="bi bi-eye"></i><?= number_format($stats['total_visitas']) ?> visitas</span>
          </div>
        </div>
        <div class="gd-dash-hero-btns">
          <a href="<?= BASE_URL ?>/admin/usuarios"   class="gd-hero-pill"><i class="bi bi-people me-1"></i>Usuarios</a>
          <a href="<?= BASE_URL ?>/admin/categorias" class="gd-hero-pill"><i class="bi bi-grid me-1"></i>Categorías</a>
          <a href="<?= BASE_URL ?>/admin/reportes?tipo=recetas" class="gd-hero-pill gd-hero-pill--gold"><i class="bi bi-flag me-1"></i>Reportes</a>
          <a href="<?= BASE_URL ?>/recetas" class="gd-hero-pill"><i class="bi bi-search me-1"></i>Ver Catálogo</a>
        </div>
        <div class="gd-dash-hero-art">
          <img src="<?= BASE_URL ?>/img/Icon/Categorias/parrillada.png" alt=""
               style="width:110px;height:110px;opacity:.2;filter:brightness(5) drop-shadow(0 4px 14px rgba(232,168,56,.3))">
        </div>
      </div>
    </div>

    <!-- Estadísticas principales — métricas generales -->
    <div class="gd-stat-wrap">
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="stat-card-colored" style="background:linear-gradient(135deg,#8f2b0c,#a83d1e)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total recetas</div>
                    </div>
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png" alt="" class="stat-icon" style="width:42px;height:42px;opacity:.8">
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card-colored" style="background:linear-gradient(135deg,#7a4700,#9a5a00)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $totalUsuarios ?></div>
                        <div class="stat-label">Usuarios registrados</div>
                    </div>
                    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/registro.png"
                         alt="" class="stat-icon-image" width="42" height="42">
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card-colored" style="background:linear-gradient(135deg,#205840,#286b53)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $totalCategorias ?></div>
                        <div class="stat-label">Categorías</div>
                    </div>
                    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/gourmet.png"
                         alt="" class="stat-icon-image" width="42" height="42">
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card-colored"
                 style="background:linear-gradient(135deg,#642b19,#8e3d22)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= number_format($stats['total_visitas']) ?></div>
                        <div class="stat-label">Visitas totales</div>
                    </div>
                    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/visitas.png"
                         alt="" class="stat-icon-image" width="42" height="42">
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="stat-card-colored" style="background:linear-gradient(135deg,#523d32,#674c3e)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $stats['total_comentarios'] ?></div>
                        <div class="stat-label">Comentarios</div>
                    </div>
                    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/comentario.png"
                         alt="" class="stat-icon-image" width="42" height="42">
                </div>
            </div>
        </div>
    </div>
    </div><!-- /gd-stat-wrap -->

    <section class="mb-4" aria-labelledby="admin-chart-title">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3 px-1">
            <div>
                <span class="text-uppercase small fw-bold text-muted">Análisis de la comunidad</span>
                <h2 id="admin-chart-title" class="h4 fw-bold mb-0">Estadísticas visuales</h2>
            </div>
            <span class="text-muted small">Datos actuales de la plataforma</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-1">Usuarios por rol</h3>
                        <p class="small text-muted mb-3">Distribución de las <?= number_format($totalUsuarios) ?> cuentas registradas</p>
                        <div class="admin-chart-rows" role="img" aria-label="Usuarios por rol">
                            <?php
                            $roleColors = ['#a94024', '#e6a23c', '#568a6b'];
                            $roleTotal = max(1, array_sum($chartRoles['values']));
                            foreach ($chartRoles['labels'] as $index => $label):
                                $value = $chartRoles['values'][$index];
                                $percentage = round(($value / $roleTotal) * 100, 2);
                            ?>
                                <div class="admin-chart-row">
                                    <div class="admin-chart-row-heading">
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <strong><?= number_format($value) ?></strong>
                                    </div>
                                    <div class="admin-chart-track">
                                        <span style="width:<?= $percentage ?>%;background-color:<?= $roleColors[$index] ?>"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-1">Estado de las recetas</h3>
                        <p class="small text-muted mb-3">Publicadas frente a borradores</p>
                        <div class="admin-chart-rows" role="img" aria-label="Estado de las recetas">
                            <?php
                            $recipeColors = ['#568a6b', '#d9dce2'];
                            $recipeTotal = max(1, array_sum($chartRecetas['values']));
                            foreach ($chartRecetas['labels'] as $index => $label):
                                $value = $chartRecetas['values'][$index];
                                $percentage = round(($value / $recipeTotal) * 100, 2);
                            ?>
                                <div class="admin-chart-row">
                                    <div class="admin-chart-row-heading">
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <strong><?= number_format($value) ?></strong>
                                    </div>
                                    <div class="admin-chart-track">
                                        <span style="width:<?= $percentage ?>%;background-color:<?= $recipeColors[$index] ?>"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-xl-4">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-1">Recetas por categoría</h3>
                        <p class="small text-muted mb-3">Categorías con más recetas</p>
                        <?php if ($chartCategorias['labels']): ?>
                            <div class="admin-category-chart" role="img" aria-label="Recetas por categoría">
                                <?php
                                $maxCategoria = max(1, max($chartCategorias['values']));
                                foreach ($chartCategorias['labels'] as $index => $label):
                                    $value = $chartCategorias['values'][$index];
                                    $percentage = round(($value / $maxCategoria) * 100, 2);
                                ?>
                                    <div class="admin-chart-row">
                                        <div class="admin-chart-row-heading">
                                            <span class="text-truncate"><?= htmlspecialchars($label) ?></span>
                                            <strong><?= number_format($value) ?></strong>
                                        </div>
                                        <div class="admin-chart-track">
                                            <span style="width:<?= $percentage ?>%;background-color:var(--primary)"></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="small text-muted mb-0">Todavía no hay categorías para graficar.</p>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Estadísticas secundarias -->
    <div class="gd-stat-wrap">
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:linear-gradient(135deg,var(--primary),var(--primary-dark))">
                        <i class="bi bi-star-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4" style="color:var(--secondary)">
                            <?= $stats['rating_global'] ?: '—' ?>
                        </div>
                        <div class="text-muted small">Rating global</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:linear-gradient(135deg,var(--accent),var(--secondary))">
                        <i class="bi bi-person-plus-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4" style="color:var(--accent)"><?= $nuevos30 ?></div>
                        <div class="text-muted small">Nuevos (30 días)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:linear-gradient(135deg,var(--danger),#b53d26)">
                        <i class="bi bi-check-circle-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4" style="color:var(--primary)"><?= $stats['publicadas'] ?></div>
                        <div class="text-muted small">Recetas publicadas</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:linear-gradient(135deg,#c8891a,#da9217)">
                        <i class="bi bi-bar-chart-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-800 fs-4" style="color:var(--secondary-dark)"><?= $stats['total_valoraciones'] ?></div>
                        <div class="text-muted small">Valoraciones</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($exito): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card shadow-sm border-0 mb-4 panel-card" id="perfil">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-md-3 text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-800 mb-3 overflow-hidden"
                         style="width:76px;height:76px;background:linear-gradient(135deg,var(--primary),var(--secondary));font-size:1.8rem">
                        <?php if ($perfilAvatarUrl): ?>
                            <img src="<?= htmlspecialchars($perfilAvatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($perfilNombre) ?>"
                                 style="width:100%;height:100%;object-fit:cover"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span style="display:none"><?= htmlspecialchars(strtoupper(substr($perfilNombre, 0, 1))) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars(strtoupper(substr($perfilNombre, 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-800 mb-1"><?= htmlspecialchars($perfilNombre) ?></h5>
                    <p class="text-muted small mb-0">Administrador · Gestión del contenido</p>
                </div>
                <div class="col-md-9">
                    <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardarPerfil" enctype="multipart/form-data">
                        <?php csrfField(); ?>
                        <input type="hidden" name="usuario_id" value="<?= $userId ?>">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-600">Nombre de usuario</label>
                                <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilNombre) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-600">Foto de perfil</label>
                                <input type="text" name="avatar" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilAvatar) ?>" placeholder="URL de imagen o déjalo vacío para usar la subida">
                                <input type="file" name="avatar_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-600">Biografía</label>
                                <textarea name="bio" class="form-control form-control-sm" rows="3" placeholder="Tu perfil de administrador..."><?= htmlspecialchars($perfilBio) ?></textarea>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-600">Redes sociales</label>
                                <input type="text" name="redes_sociales" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilRedes) ?>" placeholder="LinkedIn / Instagram">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-save me-1"></i>Guardar perfil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribución de roles y top categorías -->
    <div class="row g-3 mb-4">

        <!-- Distribución de roles -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-700 mb-3">
                        <i class="bi bi-people me-2" style="color:var(--primary)"></i>Distribución de usuarios
                    </h6>
                    <?php
                    $rolData = [
                        ['rol' => 'Admin',   'key' => 'admin',   'color' => 'var(--primary-dark)', 'icon' => 'bi-shield-fill'],
                        ['rol' => 'Chefs',   'key' => 'chef',    'color' => 'var(--secondary-dark)', 'icon' => 'bi-egg-fried'],
                        ['rol' => 'Usuarios','key' => 'usuario', 'color' => 'var(--text-muted)', 'icon' => 'bi-person-fill'],
                    ];
                    foreach ($rolData as $rd):
                        $total = $totalUsuarios ?: 1;
                        $count = $statsPorRol[$rd['key']] ?? 0;
                        $pct   = round($count / $total * 100);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-600">
                                <i class="bi <?= $rd['icon'] ?> me-1" style="color:<?= $rd['color'] ?>"></i>
                                <?= $rd['rol'] ?>
                            </span>
                            <span class="small fw-700"><?= $count ?> <span class="text-muted fw-400">(<?= $pct ?>%)</span></span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px;background:var(--surface-3)">
                            <div class="progress-bar" role="progressbar"
                                 style="width:<?= $pct ?>%;background:<?= $rd['color'] ?>;border-radius:4px"
                                 aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Total registrados</span>
                        <span class="fw-700"><?= $totalUsuarios ?></span>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted small">Nuevos (30 días)</span>
                        <span class="fw-700" style="color:var(--accent)">+<?= $nuevos30 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top categorías -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-700 mb-0">
                            <i class="bi bi-tags me-2" style="color:var(--secondary)"></i>Top categorías
                        </h6>
                        <a href="<?= BASE_URL ?>/admin/categorias" class="btn btn-outline-primary btn-sm">
                            Gestionar
                        </a>
                    </div>
                    <div class="row g-2">
                        <?php foreach ($topCategorias as $cat):
                            $iconoCategoria = basename((string)$cat['icono']);
                            $iconoExtension = strtolower(pathinfo($iconoCategoria, PATHINFO_EXTENSION));
                            $iconoEsArchivo = in_array($iconoExtension, ['png', 'jpg', 'jpeg', 'svg'], true);
                            $iconoBootstrap = preg_match('/(?:^|\s)bi-([a-z0-9-]+)$/i', (string)$cat['icono'], $coincidenciaIcono)
                                ? 'bi-' . $coincidenciaIcono[1]
                                : 'bi-tag-fill';
                        ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3"
                                 style="background:<?= htmlspecialchars($cat['bg']) ?>20;border:1px solid <?= htmlspecialchars($cat['color']) ?>30">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:38px;height:38px;background:<?= htmlspecialchars($cat['bg']) ?>">
                                    <?php if ($iconoEsArchivo): ?>
                                        <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= rawurlencode($iconoCategoria) ?>"
                                             alt="" width="26" height="26" loading="lazy"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block'">
                                        <i class="bi <?= htmlspecialchars($iconoBootstrap) ?>"
                                           style="display:none;color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.1rem"></i>
                                    <?php else: ?>
                                        <i class="bi <?= htmlspecialchars($iconoBootstrap) ?>"
                                           style="color:<?= htmlspecialchars($cat['color']) ?>;font-size:1.1rem"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="fw-700 small text-truncate"><?= htmlspecialchars($cat['nombre']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem">
                                        <?= $cat['total_recetas'] ?> receta<?= $cat['total_recetas'] != 1 ? 's' : '' ?>
                                        · <?= number_format($cat['total_visitas']) ?> visitas
                                    </div>
                                </div>
                                <span class="fw-800 small flex-shrink-0" style="color:<?= htmlspecialchars($cat['color']) ?>">
                                    <?= $cat['total_recetas'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recetas más vistas y usuarios nuevos -->
    <div class="row g-3 mb-4">

        <!-- Recetas más vistas -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-700 mb-0">
                            <i class="bi bi-eye me-2" style="color:var(--primary)"></i>Recetas más vistas
                        </h6>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-primary btn-sm">Ver catálogo</a>
                    </div>
                    <?php if (empty($masVistas)): ?>
                        <p class="text-muted small">Aún no hay datos de visitas.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th><i class="bi bi-eye"></i></th>
                                    <th><i class="bi bi-star-fill text-warning"></i></th>
                                    <th><i class="bi bi-heart-fill text-danger"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($masVistas as $i => $r): ?>
                                <tr>
                                    <td>
                                        <?php if ($i < 3): ?>
                                        <span class="badge rounded-pill"
                                              style="background:<?= ['var(--primary)','var(--secondary)','var(--primary-light)'][$i] ?>;color:<?= $i===1?'#3b2a1a':'#fff' ?>">
                                            <?= $i + 1 ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted small"><?= $i + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-600 small"><?= htmlspecialchars($r['titulo']) ?></div>
                                        <small class="text-muted">por <?= htmlspecialchars($r['chef_nombre'] ?? '—') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark" style="font-size:.7rem"><?= htmlspecialchars($r['categoria'] ?? '—') ?></span></td>
                                    <td class="fw-700 small"><?= number_format($r['visitas']) ?></td>
                                    <td class="small"><?= $r['rating'] ?: '—' ?></td>
                                    <td class="small text-muted"><?= $r['total_favoritos'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Usuarios nuevos -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-700 mb-0">
                            <i class="bi bi-person-plus me-2" style="color:var(--accent)"></i>Usuarios recientes
                        </h6>
                        <a href="<?= BASE_URL ?>/admin/usuarios" class="btn btn-outline-primary btn-sm">Ver todos</a>
                    </div>
                    <?php if (empty($usuariosRecientes)): ?>
                        <p class="text-muted small">No hay usuarios nuevos en los últimos 30 días.</p>
                    <?php else: ?>
                        <?php foreach ($usuariosRecientes as $u): ?>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <div class="rounded-circle d-flex align-items-center justify-content-center
                                        text-white fw-700 flex-shrink-0"
                                 style="width:36px;height:36px;background:var(--primary);font-size:.85rem">
                                <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-600 small text-truncate"><?= htmlspecialchars($u['nombre']) ?></div>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></small>
                            </div>
                            <span class="badge-rol badge-<?= $u['rol'] ?>"><?= ucfirst($u['rol']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recetas recientes y accesos rápidos -->
    <div class="row g-3 mb-4">

        <!-- Recetas recientes -->
        <div class="col-lg-8">
            <div class="card panel-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-700 mb-0">
                            <i class="bi bi-clock-history me-2" style="color:var(--primary)"></i>Recetas recientes
                        </h6>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-outline-primary btn-sm">Ver todas</a>
                    </div>
                    <?php if (empty($recientes)): ?>
                        <p class="text-muted small">Aún no hay recetas.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Chef</th>
                                    <th><i class="bi bi-eye"></i></th>
                                    <th><i class="bi bi-star-fill text-warning"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recientes as $r): ?>
                                <tr>
                                    <td class="fw-600 small"><?= htmlspecialchars($r['titulo']) ?></td>
                                    <td><span class="badge bg-light text-dark" style="font-size:.7rem"><?= htmlspecialchars($r['categoria'] ?? '—') ?></span></td>
                                    <td class="text-muted small"><?= htmlspecialchars($r['chef_nombre'] ?? '—') ?></td>
                                    <td class="small"><?= number_format($r['visitas']) ?></td>
                                    <td class="small">
                                        <?= $r['rating']
                                            ? '<i class="bi bi-star-fill text-warning"></i> ' . $r['rating']
                                            : '<span class="text-muted">—</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-700 mb-3">
                        <i class="bi bi-lightning-fill me-2" style="color:var(--secondary)"></i>Accesos rápidos
                    </h6>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/usuarios"
                           class="btn btn-outline-primary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-people fs-5"></i>
                            <div>
                                <div class="fw-700">Gestionar usuarios</div>
                                <small class="text-muted fw-400">Roles · eliminar cuentas</small>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/categorias"
                           class="btn btn-outline-secondary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-tags fs-5" style="color:var(--secondary)"></i>
                            <div>
                                <div class="fw-700">Categorías</div>
                                <small class="text-muted fw-400">Crear · eliminar</small>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>/index.php?route=admin/recetas"
                           class="btn btn-outline-primary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-egg-fried fs-5" style="color:var(--primary)"></i>
                            <div>
                                <div class="fw-700">Gestionar recetas</div>
                                <small class="text-muted fw-400">Crear · editar · eliminar</small>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>/index.php?route=admin/reportes&tipo=recetas"
                           class="btn btn-sm text-start d-flex align-items-center gap-2"
                           style="background:var(--surface-3);border:1px solid var(--border)">
                            <i class="bi bi-file-earmark-bar-graph fs-5" style="color:var(--primary-dark)"></i>
                            <div>
                                <div class="fw-700">Generar reportes</div>
                                <small class="text-muted fw-400">Recetas · comentarios · CSV</small>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas"
                           class="btn btn-sm text-start d-flex align-items-center gap-2"
                           style="background:var(--surface-3);border:1px solid var(--border)">
                            <i class="bi bi-book fs-5" style="color:var(--accent)"></i>
                            <div>
                                <div class="fw-700">Ver catálogo</div>
                                <small class="text-muted fw-400">Todas las recetas públicas</small>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>/descargar-imagenes-recetas.php"
                           class="btn btn-sm text-start d-flex align-items-center gap-2"
                           style="background:linear-gradient(135deg,#fff8f0,#fdf0e0);border:1px solid #f5c8a0">
                            <i class="bi bi-images fs-5" style="color:var(--primary)"></i>
                            <div>
                                <div class="fw-700">Herramienta de imágenes</div>
                                <small class="text-muted fw-400">Descargar desde Wikimedia Commons</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
