<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/categoria.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireLogin();

$pageTitle = 'Mi Inicio';
$model     = new Receta();
$catModel  = new Categoria();
$userModel = new Usuario();
$nombre    = $_SESSION['usuario_nombre'] ?? 'Usuario';
$email     = $_SESSION['usuario_email']  ?? '';
$userId    = $_SESSION['usuario_id'];
$rol       = $_SESSION['rol'] ?? 'usuario';
$perfil    = $userModel->buscarPorId($userId) ?: [];
$perfilNombre = $perfil['nombre'] ?? $nombre;
$perfilAvatar = $perfil['avatar'] ?? '';
$perfilAvatarUrl = urlAvatarPerfil($perfilAvatar);

// Estadísticas dinámicas
$totalFavoritos     = $model->contarFavoritosUsuario($userId);
$totalRecetas       = $model->contarPublicadas();
$totalPaises        = $model->contarPaisesCulinarios();

// Categorías para tags populares
$categorias = $catModel->listarSimple();

// Mensajes flash
$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$baseUrl = rtrim(BASE_URL, '/');
$bodyClass = 'app-layout';
$extraCss  = 'dashboard-usuario.css?v=' . filemtime(__DIR__ . '/../../styles/dashboard-usuario.css');
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<?php
$topbarBadge = 'Club Gourmet';
$topbarLinks = [
    ['label'=>'Explorar Recetas','href'=> $baseUrl . '/index.php?route=recetas','icon'=>'bi-search'],
    ['label'=>'Favoritos','href'=> $baseUrl . '/index.php?route=favoritos','icon'=>'bi-heart-fill','class'=>'gd-topbar-link--fav','count'=> $totalFavoritos],
];
include __DIR__ . '/../../assets/topbar.php';
?>

<!-- ══ CONTENIDO PRINCIPAL ══ -->
<main class="pb-4">
<div class="container-fluid px-3">

    <?php if ($exito): ?>
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ══ HERO USUARIO ══ -->
    <div class="gd-dash-hero">
      <div class="gd-dash-hero-inner">
        <div class="gd-dash-hero-body">
          <div class="gd-dash-chip">
            <i class="bi bi-emoji-smile-fill" aria-hidden="true"></i>
            GastroDigital Community
          </div>
          <h1 class="gd-dash-hero-title">
            <?php if ($perfilAvatarUrl): ?>
              <img src="<?= htmlspecialchars($perfilAvatarUrl) ?>"
                   alt="Avatar de <?= htmlspecialchars($perfilNombre) ?>"
                   class="rounded-circle me-2"
                   style="width:48px;height:48px;object-fit:cover;vertical-align:middle">
            <?php endif; ?>
            Hola, <?= htmlspecialchars($perfilNombre) ?>
            <span aria-hidden="true">👋</span>
          </h1>
          <p class="gd-dash-hero-desc">¿Qué delicia vas a preparar hoy? Explora nuestras recetas gourmet, guarda tus favoritas y viaja por el mundo a través de su gastronomía.</p>
          <!-- Buscador -->
          <form action="<?= $baseUrl ?>/recetas" method="GET" class="mb-3">
            <div class="hero-search-row">
              <div class="hero-search-input-wrap">
                <i class="bi bi-search hero-search-ico"></i>
                <input type="text" name="q" class="hero-search-input"
                       placeholder="Buscar receta, ingrediente o plato...">
              </div>
              <button type="submit" class="hero-search-btn">
                Buscar Receta <i class="bi bi-arrow-right ms-1"></i>
              </button>
            </div>
          </form>
          <!-- Tags populares -->
          <div class="hero-tags-row">
            <span class="hero-tags-label"><i class="bi bi-fire"></i> Populares:</span>
            <?php
            $tags = [
              ['🌮','Tacos','q=Tacos'],['🍝','Pastas','q=Pastas'],['🥗','Ensaladas','q=Ensaladas'],
              ['⚡','Menos de 30 min','tiempo=30'],['🍰','Postres','q=Postres'],
              ['🌎','México','pais=México'],['🇮🇹','Italia','pais=Italia'],
            ];
            foreach ($tags as $t): ?>
            <a href="<?= $baseUrl ?>/recetas?<?= $t[2] ?>" class="hero-tag-pill">
              <?= $t[0] ?> <?= $t[1] ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Arte lateral: icono animado -->
        <div class="gd-dash-hero-art">
          <img src="<?= $baseUrl ?>/img/Icon/Categorias/plato-principal.png" alt=""
               style="width:110px;height:110px;opacity:.18;filter:brightness(5) drop-shadow(0 4px 16px rgba(232,168,56,.3))">
        </div>
      </div>
    </div>

    <!-- ══ ESTADÍSTICAS ══ -->
    <div class="gd-stat-wrap">
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <a href="<?= $baseUrl ?>/favoritos" class="gd-metric-card d-block text-decoration-none" style="border-top:3px solid var(--red)">
          <div class="d-flex align-items-center gap-3">
            <div class="gd-metric-icon-wrap" style="background:rgba(192,57,43,.1)">
              <i class="bi bi-heart-fill" aria-hidden="true" style="font-size:1.4rem;color:var(--red)"></i>
            </div>
            <div>
              <div class="gd-metric-label">Recetas Guardadas</div>
              <div class="gd-metric-number"><?= $totalFavoritos ?></div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-sm-4">
        <a href="<?= $baseUrl ?>/recetas" class="gd-metric-card d-block text-decoration-none" style="border-top:3px solid var(--orange)">
          <div class="d-flex align-items-center gap-3">
            <div class="gd-metric-icon-wrap" style="background:rgba(211,84,0,.1)">
              <img src="<?= $baseUrl ?>/img/Icon/Categorias/plato-principal.png" alt="" style="width:28px;height:28px">
            </div>
            <div>
              <div class="gd-metric-label">Recetas Disponibles</div>
              <div class="gd-metric-number"><?= $totalRecetas ?></div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-sm-4">
        <a href="<?= $baseUrl ?>/paises" class="gd-metric-card d-block text-decoration-none" style="border-top:3px solid #3498db">
          <div class="d-flex align-items-center gap-3">
            <div class="gd-metric-icon-wrap" style="background:rgba(52,152,219,.1)">
              <i class="bi bi-globe-americas" aria-hidden="true" style="font-size:1.4rem;color:#3498db"></i>
            </div>
            <div>
              <div class="gd-metric-label">Países Culinarios</div>
              <div class="gd-metric-number"><?= $totalPaises ?></div>
            </div>
          </div>
        </a>
      </div>
    </div>
    </div>

</div>
</main>

<!-- ══ BOTONES FLOTANTES ══ -->
<div class="gd-fab-container">
    <button class="gd-fab gd-fab-chat" type="button" title="Chefbot" id="gdChatFab">
        <i class="bi bi-chat-dots-fill"></i>
    </button>
</div>

<!-- Scripts del sidebar + interactividad del dashboard -->
<script>
    // Animate stats on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('gd-stat-visible');
            }
        });
    }, { threshold: 0.3 });

    document.querySelectorAll('.gd-stat-card').forEach(card => {
        observer.observe(card);
    });

})();
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
