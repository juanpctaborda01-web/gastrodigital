<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$baseUrl   = rtrim(BASE_URL, '/');
$rol       = $_SESSION['rol'] ?? '';
$nombre    = $_SESSION['usuario_nombre'] ?? 'Usuario';
$email     = $_SESSION['usuario_email']  ?? '';
$iniciales = strtoupper(substr($nombre, 0, 1));
$avatar    = $_SESSION['usuario_avatar'] ?? '';

$uri = trim(strtok(str_replace($baseUrl, '', $_SERVER['REQUEST_URI'] ?? '/'), '?'), '/');

function sbActive(string $route, string $uri): string {
    if ($route === '') return $uri === '' ? 'is-active' : '';
    return ($uri === $route || str_starts_with($uri, $route)) ? 'is-active' : '';
}

// Iconos PNG por item (base path)
$icoBase = $baseUrl . '/img/Icon/Categorias/';
$icoAnim = $baseUrl . '/img/Icon/Icon-Animados/';
?>

<div class="sb-backdrop" id="sbBackdrop"></div>

<aside class="sb" id="sbSidebar">

  <!-- ══ HEADER ═══════════════════════════════════════════════════════ -->
  <div class="sb-head">
    <!-- Logo (visible siempre) -->
    <a class="sb-brand" href="<?= $baseUrl ?>/">
      <div class="sb-logo-wrap">
        <img src="<?= $baseUrl ?>/img/GastroDigitalLogo2.png" alt="GD" class="sb-logo-img"
             onerror="this.style.display='none';this.nextSibling.style.display='flex'">
        <span class="sb-logo-fallback" style="display:none"><i class="bi bi-egg-fried"></i></span>
      </div>
      <div class="sb-brand-texts sb-hide-collapsed">
        <span class="sb-brand-name">Gastro<strong>Digital</strong></span>
        <span class="sb-brand-sub">✦ TU COCINA DIGITAL</span>
      </div>
    </a>
    <!-- Hamburguesa: en desktop colapsa/expande; en móvil cierra -->
    <button class="sb-hamburger" id="sbToggle" aria-label="Toggle menú">
      <span class="sb-ham-line"></span>
      <span class="sb-ham-line"></span>
      <span class="sb-ham-line"></span>
    </button>
  </div>

  <!-- ══ NAVEGACIÓN ════════════════════════════════════════════════════ -->
  <nav class="sb-nav" aria-label="Menú principal">

    <?php if (!isset($_SESSION['usuario_id'])): ?>
      <!-- ── SIN SESIÓN ── -->
      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Descubrir</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('', $uri) ?>" href="<?= $baseUrl ?>/" data-tooltip="Ver recetas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Ver recetas</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Herramientas</span>
        <div class="sb-sep-line"></div>
      </div>
      <button class="sb-item sb-item--btn" id="darkModeToggle" data-tooltip="Modo oscuro">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-moon-stars"></i></span>
        <span class="sb-label sb-hide-collapsed">Modo oscuro</span>
        <label class="sb-switch sb-hide-collapsed" onclick="event.stopPropagation()">
          <input type="checkbox" id="darkCheckbox"><span class="sb-slider"></span>
        </label>
      </button>
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chef Bot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chef Bot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php elseif ($rol === 'admin'): ?>
      <!-- ── ADMIN ── -->
      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Gestión del Sistema</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('admin', $uri) && !sbActive('admin/', $uri) ? 'is-active' : (sbActive('admin', $uri) ? 'is-active' : '') ?>"
         href="<?= $baseUrl ?>/admin" data-tooltip="Dashboard">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Dashboard</span>
        <?php if (sbActive('admin', $uri) && !str_contains($uri, '/')): ?>
          <span class="sb-dot sb-hide-collapsed"></span>
        <?php endif; ?>
      </a>
      <a class="sb-item <?= sbActive('admin/usuarios', $uri) ?>" href="<?= $baseUrl ?>/admin/usuarios" data-tooltip="Usuarios">
        <span class="sb-icon sb-icon--teal"><i class="bi bi-people"></i></span>
        <span class="sb-label sb-hide-collapsed">Usuarios</span>
      </a>
      <a class="sb-item <?= sbActive('recetas', $uri) && !sbActive('mis-recetas', $uri) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/recetas" data-tooltip="Recetas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>desayuno.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Recetas</span>
      </a>
      <a class="sb-item <?= sbActive('admin/categorias', $uri) ?>" href="<?= $baseUrl ?>/admin/categorias" data-tooltip="Categorías">
        <span class="sb-icon sb-icon--green">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Categorías</span>
      </a>
      <a class="sb-item <?= sbActive('paises', $uri) ?>" href="<?= $baseUrl ?>/paises" data-tooltip="Explorar países">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoAnim ?>paises.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Explorar países</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Moderación y Control</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item" href="<?= $baseUrl ?>/admin/moderacion#comentarios" data-tooltip="Comentarios">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-chat-left-dots"></i></span>
        <span class="sb-label sb-hide-collapsed">Comentarios</span>
      </a>
      <a class="sb-item <?= sbActive('admin/reportes', $uri) ?>" href="<?= $baseUrl ?>/admin/reportes" data-tooltip="Reportes">
        <span class="sb-icon sb-icon--red"><i class="bi bi-flag"></i></span>
        <span class="sb-label sb-hide-collapsed">Reportes</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Herramientas</span>
        <div class="sb-sep-line"></div>
      </div>
      <button class="sb-item sb-item--btn" id="darkModeToggle" data-tooltip="Modo oscuro">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-moon-stars"></i></span>
        <span class="sb-label sb-hide-collapsed">Modo oscuro</span>
        <label class="sb-switch sb-hide-collapsed" onclick="event.stopPropagation()">
          <input type="checkbox" id="darkCheckbox"><span class="sb-slider"></span>
        </label>
      </button>
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chef Bot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chef Bot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php elseif ($rol === 'chef'): ?>
      <!-- ── CHEF ── -->
      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mi Cocina Chef</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('mis-recetas', $uri) && empty($_GET['filtro']) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas" data-tooltip="Inicio">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Inicio</span>
        <?php if (sbActive('mis-recetas', $uri) && empty($_GET['filtro'])): ?>
          <span class="sb-dot sb-hide-collapsed"></span>
        <?php endif; ?>
      </a>
      <a class="sb-item <?= sbActive('mis-recetas', $uri) && empty($_GET['filtro']) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas" data-tooltip="Mis recetas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mis recetas</span>
      </a>
      <a class="sb-item <?= sbActive('crear-receta', $uri) ?>" href="<?= $baseUrl ?>/crear-receta" data-tooltip="Nueva receta">
        <span class="sb-icon sb-icon--green"><i class="bi bi-plus-circle"></i></span>
        <span class="sb-label sb-hide-collapsed">Nueva receta</span>
        <span class="sb-pill sb-pill--crear sb-hide-collapsed">CREAR</span>
      </a>
      <a class="sb-item <?= (($_GET['filtro'] ?? '') === 'borradores') ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas?filtro=borradores" data-tooltip="Borradores">
        <span class="sb-icon sb-icon--yellow"><i class="bi bi-hourglass-split"></i></span>
        <span class="sb-label sb-hide-collapsed">Borradores</span>
      </a>
      <a class="sb-item <?= (($_GET['filtro'] ?? '') === 'publicadas') ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas?filtro=publicadas" data-tooltip="Publicadas">
        <span class="sb-icon sb-icon--teal"><i class="bi bi-check2-circle"></i></span>
        <span class="sb-label sb-hide-collapsed">Publicadas</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Comunidad y Métricas</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item" href="<?= $baseUrl ?>/mis-recetas#comentarios" data-tooltip="Comentarios">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-chat-left-dots"></i></span>
        <span class="sb-label sb-hide-collapsed">Comentarios</span>
      </a>
      <a class="sb-item" href="<?= $baseUrl ?>/mis-recetas#valoraciones" data-tooltip="Valoraciones">
        <span class="sb-icon sb-icon--yellow">
          <img src="<?= $icoAnim ?>stars.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Valoraciones</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Herramientas</span>
        <div class="sb-sep-line"></div>
      </div>
      <button class="sb-item sb-item--btn" id="darkModeToggle" data-tooltip="Modo oscuro">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-moon-stars"></i></span>
        <span class="sb-label sb-hide-collapsed">Modo oscuro</span>
        <label class="sb-switch sb-hide-collapsed" onclick="event.stopPropagation()">
          <input type="checkbox" id="darkCheckbox"><span class="sb-slider"></span>
        </label>
      </button>
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chef Bot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chef Bot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php else: /* usuario comensal */ ?>
      <!-- ── USUARIO ── -->
      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mi Experiencia</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('dashboard', $uri) ?>" href="<?= $baseUrl ?>/dashboard" data-tooltip="Inicio">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Inicio</span>
        <?php if (sbActive('dashboard', $uri)): ?><span class="sb-dot sb-hide-collapsed"></span><?php endif; ?>
      </a>
      <a class="sb-item <?= sbActive('recetas', $uri) ?>" href="<?= $baseUrl ?>/recetas" data-tooltip="Explorar recetas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Explorar recetas</span>
      </a>
      <a class="sb-item <?= sbActive('categorias', $uri) ?>" href="<?= $baseUrl ?>/categorias" data-tooltip="Categorías">
        <span class="sb-icon sb-icon--green">
          <img src="<?= $icoBase ?>desayuno.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Categorías</span>
      </a>
      <a class="sb-item <?= sbActive('favoritos', $uri) ?>" href="<?= $baseUrl ?>/favoritos" data-tooltip="Mis favoritos">
        <span class="sb-icon sb-icon--red">
          <img src="<?= $icoAnim ?>heart.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mis favoritos</span>
      </a>
      <a class="sb-item <?= sbActive('paises', $uri) ?>" href="<?= $baseUrl ?>/paises" data-tooltip="Países">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoAnim ?>paises.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Explorar países</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mi Actividad</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('mis-comentarios', $uri) ?>" href="<?= $baseUrl ?>/mis-comentarios" data-tooltip="Mis comentarios">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-chat-left-dots"></i></span>
        <span class="sb-label sb-hide-collapsed">Mis comentarios</span>
      </a>
      <a class="sb-item" href="<?= $baseUrl ?>/mis-comentarios?tab=valoraciones" data-tooltip="Mis valoraciones">
        <span class="sb-icon sb-icon--yellow">
          <img src="<?= $icoAnim ?>stars.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mis valoraciones</span>
      </a>
      <a class="sb-item <?= sbActive('perfil', $uri) ?>" href="<?= $baseUrl ?>/perfil" data-tooltip="Perfil">
        <span class="sb-icon sb-icon--teal"><i class="bi bi-person-circle"></i></span>
        <span class="sb-label sb-hide-collapsed">Perfil</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Herramientas</span>
        <div class="sb-sep-line"></div>
      </div>
      <button class="sb-item sb-item--btn" id="darkModeToggle" data-tooltip="Modo oscuro">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-moon-stars"></i></span>
        <span class="sb-label sb-hide-collapsed">Modo oscuro</span>
        <label class="sb-switch sb-hide-collapsed" onclick="event.stopPropagation()">
          <input type="checkbox" id="darkCheckbox"><span class="sb-slider"></span>
        </label>
      </button>
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chef Bot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chef Bot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>
    <?php endif; ?>

  </nav>

  <!-- ══ FOOTER CON USUARIO ════════════════════════════════════════════ -->
  <div class="sb-foot">
    <?php if (isset($_SESSION['usuario_id'])): ?>
      <!-- Clicable → /perfil -->
      <a href="<?= $baseUrl ?>/perfil" class="sb-user-row" data-tooltip="Ver perfil">
        <div class="sb-user-av">
          <?php if ($avatar && filter_var($avatar, FILTER_VALIDATE_URL)): ?>
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="sb-user-av-img"
                 onerror="this.style.display='none';this.nextSibling.style.display='flex'">
            <span class="sb-user-av-initials" style="display:none"><?= $iniciales ?></span>
          <?php else: ?>
            <span class="sb-user-av-initials"><?= $iniciales ?></span>
          <?php endif; ?>
        </div>
        <div class="sb-user-info sb-hide-collapsed">
          <div class="sb-user-name"><?= htmlspecialchars($nombre) ?></div>
          <div class="sb-user-email"><?= htmlspecialchars($email) ?></div>
          <span class="sb-user-role sb-role--<?= $rol ?>">• <?= strtoupper($rol) ?></span>
        </div>
        <i class="bi bi-chevron-right sb-user-chevron sb-hide-collapsed"></i>
      </a>
      <a href="<?= $baseUrl ?>/logout" class="sb-logout" data-tooltip="Cerrar sesión">
        <img src="<?= $icoAnim ?>trash.gif" alt="" class="sb-ico-img" style="width:18px;height:18px">
        <span class="sb-hide-collapsed">Cerrar sesión</span>
      </a>
    <?php else: ?>
      <a href="<?= $baseUrl ?>/login"    class="sb-auth-btn sb-auth-btn--login">
        <i class="bi bi-box-arrow-in-right"></i>
        <span class="sb-hide-collapsed">Iniciar sesión</span>
      </a>
      <a href="<?= $baseUrl ?>/register" class="sb-auth-btn sb-auth-btn--reg">
        <i class="bi bi-person-plus"></i>
        <span class="sb-hide-collapsed">Registrarse</span>
      </a>
    <?php endif; ?>
  </div>

</aside>
