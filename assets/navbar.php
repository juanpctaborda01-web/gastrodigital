<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$baseUrl   = rtrim(BASE_URL, '/');
$rol       = $_SESSION['rol'] ?? '';
$nombre    = $_SESSION['usuario_nombre'] ?? 'Usuario';
$email     = $_SESSION['usuario_email']  ?? '';
$iniciales = strtoupper(substr($nombre, 0, 1));
$avatar    = $_SESSION['usuario_avatar'] ?? null;
if (isset($_SESSION['usuario_id']) && !array_key_exists('usuario_avatar', $_SESSION)) {
    require_once __DIR__ . '/../models/usuarios.php';
    $perfilNavbar = (new Usuario())->buscarPorId((int)$_SESSION['usuario_id']);
    $avatar = $perfilNavbar['avatar'] ?? '';
    $_SESSION['usuario_avatar'] = $avatar;
}
$avatarUrl = urlAvatarPerfil($avatar);
$notificacionesNoLeidas = 0;
if (isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/../models/Notificacion.php';
    $notificacionesNoLeidas = (new Notificacion())->contarNoLeidas((int)$_SESSION['usuario_id']);
}

$uri = trim(strtok(str_replace($baseUrl, '', $_SERVER['REQUEST_URI'] ?? '/'), '?'), '/');

function sbActive(string $route, string $uri): string {
    if ($route === '') return $uri === '' ? 'is-active' : '';
    return ($uri === $route || str_starts_with($uri, $route)) ? 'is-active' : '';
}

$icoBase = $baseUrl . '/img/Icon/Categorias/';
$icoAnim = $baseUrl . '/img/Icon/Icon-Animados/';
$icoSob  = $baseUrl . '/img/Icon/ICON-Sobrantes/';
?>

<div class="sb-backdrop" id="sbBackdrop"></div>

<aside class="sb" id="sbSidebar">

  <!-- ══ HEADER ═══════════════════════════════════════════════════════════ -->
  <div class="sb-head">
    <a class="sb-brand" href="<?= $baseUrl ?>/">
      <div class="sb-logo-wrap">
        <img src="<?= $baseUrl ?>/img/GastroDigitalLogo2.png" alt="GD" class="sb-logo-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span class="sb-logo-fallback" style="display:none"><i class="bi bi-egg-fried"></i></span>
      </div>
      <div class="sb-brand-texts sb-hide-collapsed">
        <span class="sb-brand-name">Gastro<strong>Digital</strong></span>
        <span class="sb-brand-sub">✦ TU COCINA DIGITAL</span>
      </div>
    </a>
    <button class="sb-hamburger" id="sbToggle" aria-label="Toggle menú">
      <span class="sb-ham-line"></span>
      <span class="sb-ham-line"></span>
      <span class="sb-ham-line"></span>
    </button>
  </div>

  <!-- ══ SEARCH — solo cuando está expandido ════════════════════════════ -->
  <div class="sb-search-wrap sb-hide-collapsed">
    <div class="sb-search">
      <i class="bi bi-search sb-search-ico"></i>
      <input type="text" class="sb-search-input" id="sbSearch"
             placeholder="Buscar en el menú…" autocomplete="off">
    </div>
  </div>

  <!-- ══ NAVEGACIÓN ═════════════════════════════════════════════════════ -->
  <nav class="sb-nav" aria-label="Menú principal">

    <?php /* ─────────────── SIN SESIÓN ─────────────── */ ?>
    <?php if (!isset($_SESSION['usuario_id'])): ?>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Descubrir</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('', $uri) ?>" href="<?= $baseUrl ?>/" data-tooltip="Inicio">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Inicio</span>
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
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chefbot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chefbot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php /* ─────────────── ADMIN ─────────────── */ ?>
    <?php elseif ($rol === 'admin'): ?>

      <!-- Saludo rápido -->
      <div class="sb-greeting sb-hide-collapsed">
        <span class="sb-greeting-text">Hola, <strong><?= htmlspecialchars(explode(' ', $nombre)[0]) ?></strong> 👋</span>
      </div>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">General</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('admin', $uri) && !str_contains($uri, '/') ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/admin" data-tooltip="Dashboard">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Dashboard</span>
        <?php if (sbActive('admin', $uri) && !str_contains($uri, '/')): ?>
          <span class="sb-dot sb-hide-collapsed"></span>
        <?php endif; ?>
      </a>
      <a class="sb-item <?= sbActive('recetas', $uri) && !sbActive('mis-recetas', $uri) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/recetas" data-tooltip="Recetas públicas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Recetas</span>
      </a>
      <a class="sb-item <?= sbActive('paises', $uri) ?>" href="<?= $baseUrl ?>/paises" data-tooltip="Países">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoAnim ?>paises.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Países</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Gestión</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('admin/usuarios', $uri) ?>"
         href="<?= $baseUrl ?>/admin/usuarios" data-tooltip="Usuarios">
        <span class="sb-icon sb-icon--teal"><i class="bi bi-people-fill"></i></span>
        <span class="sb-label sb-hide-collapsed">Usuarios</span>
      </a>
      <a class="sb-item <?= sbActive('admin/recetas', $uri) ?>"
         href="<?= $baseUrl ?>/index.php?route=admin/recetas" data-tooltip="Gestionar recetas">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Recetas admin</span>
      </a>
      <a class="sb-item <?= sbActive('admin/categorias', $uri) ?>"
         href="<?= $baseUrl ?>/admin/categorias" data-tooltip="Categorías">
        <span class="sb-icon sb-icon--green">
          <img src="<?= $icoBase ?>desayuno.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Categorías</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Moderación</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('admin/moderacion', $uri) ?>"
         href="<?= $baseUrl ?>/admin/moderacion" data-tooltip="Moderación">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-shield-check"></i></span>
        <span class="sb-label sb-hide-collapsed">Moderación</span>
      </a>
      <a class="sb-item <?= sbActive('admin/cambiar-contrasenas', $uri) ?>"
         href="<?= $baseUrl ?>/index.php?route=admin/cambiar-contrasenas" data-tooltip="Contraseñas">
        <span class="sb-icon sb-icon--red"><i class="bi bi-key-fill"></i></span>
        <span class="sb-label sb-hide-collapsed">Contraseñas</span>
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
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chefbot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chefbot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php /* ─────────────── CHEF ─────────────── */ ?>
    <?php elseif ($rol === 'chef'): ?>

      <!-- Saludo rápido -->
      <div class="sb-greeting sb-hide-collapsed">
        <span class="sb-greeting-text">Hola Chef, <strong><?= htmlspecialchars(explode(' ', $nombre)[0]) ?></strong> 👨‍🍳</span>
      </div>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mi Cocina</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('mis-recetas', $uri) && empty($_GET['filtro']) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas" data-tooltip="Dashboard Chef">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Dashboard</span>
        <?php if (sbActive('mis-recetas', $uri) && empty($_GET['filtro'])): ?>
          <span class="sb-dot sb-hide-collapsed"></span>
        <?php endif; ?>
      </a>
      <a class="sb-item <?= sbActive('crear-receta', $uri) ?>"
         href="<?= $baseUrl ?>/crear-receta" data-tooltip="Nueva receta">
        <span class="sb-icon sb-icon--green">
          <img src="<?= $icoSob ?>registro.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Nueva receta</span>
        <span class="sb-pill sb-pill--crear sb-hide-collapsed">CREAR</span>
      </a>
      <a class="sb-item <?= sbActive('recetas', $uri) && !sbActive('mis-recetas', $uri) && !sbActive('crear-receta', $uri) && !sbActive('editar-receta', $uri) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/recetas" data-tooltip="Recetas de otros chefs">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Recetas de otros chefs</span>
      </a>
      <a class="sb-item <?= sbActive('categorias', $uri) ?>" href="<?= $baseUrl ?>/categorias" data-tooltip="Categorías">
        <span class="sb-icon sb-icon--green">
          <img src="<?= $icoBase ?>desayuno.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Categorías</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mis Recetas</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('mis-recetas', $uri) && empty($_GET['filtro']) ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas" data-tooltip="Todas mis recetas">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoBase ?>plato-principal.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Todas</span>
      </a>
      <a class="sb-item <?= (($_GET['filtro'] ?? '') === 'publicadas') ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas?filtro=publicadas" data-tooltip="Publicadas">
        <span class="sb-icon sb-icon--teal">
          <img src="<?= $icoSob ?>cheque.png" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Publicadas</span>
      </a>
      <a class="sb-item <?= (($_GET['filtro'] ?? '') === 'borradores') ? 'is-active' : '' ?>"
         href="<?= $baseUrl ?>/mis-recetas?filtro=borradores" data-tooltip="Borradores">
        <span class="sb-icon sb-icon--yellow"><i class="bi bi-pencil-square"></i></span>
        <span class="sb-label sb-hide-collapsed">Borradores</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Comunidad</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item" href="<?= $baseUrl ?>/mis-recetas#comentarios" data-tooltip="Comentarios recibidos">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-chat-left-dots-fill"></i></span>
        <span class="sb-label sb-hide-collapsed">Comentarios</span>
      </a>
      <a class="sb-item" href="<?= $baseUrl ?>/mis-recetas#valoraciones" data-tooltip="Valoraciones recibidas">
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
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chefbot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chefbot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php /* ─────────────── USUARIO ─────────────── */ ?>
    <?php else: ?>

      <!-- Saludo rápido -->
      <div class="sb-greeting sb-hide-collapsed">
        <span class="sb-greeting-text">Hola, <strong><?= htmlspecialchars(explode(' ', $nombre)[0]) ?></strong> 🍽️</span>
      </div>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Descubrir</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('dashboard', $uri) ?>" href="<?= $baseUrl ?>/dashboard" data-tooltip="Mi Inicio">
        <span class="sb-icon sb-icon--orange">
          <img src="<?= $icoAnim ?>home.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mi Inicio</span>
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
      <a class="sb-item <?= sbActive('paises', $uri) ?>" href="<?= $baseUrl ?>/paises" data-tooltip="Países">
        <span class="sb-icon sb-icon--blue">
          <img src="<?= $icoAnim ?>paises.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Países</span>
      </a>

      <div class="sb-sep">
        <span class="sb-sep-label sb-hide-collapsed">Mi Actividad</span>
        <div class="sb-sep-line"></div>
      </div>
      <a class="sb-item <?= sbActive('favoritos', $uri) ?>" href="<?= $baseUrl ?>/favoritos" data-tooltip="Mis favoritos">
        <span class="sb-icon sb-icon--red">
          <img src="<?= $icoAnim ?>heart.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mis favoritos</span>
      </a>
      <a class="sb-item <?= sbActive('mis-comentarios', $uri) ?>"
         href="<?= $baseUrl ?>/mis-comentarios" data-tooltip="Mis comentarios">
        <span class="sb-icon sb-icon--gray"><i class="bi bi-chat-left-dots-fill"></i></span>
        <span class="sb-label sb-hide-collapsed">Mis comentarios</span>
      </a>
      <a class="sb-item <?= sbActive('mis-valoraciones', $uri) ?>"
         href="<?= $baseUrl ?>/mis-comentarios?tab=valoraciones" data-tooltip="Mis valoraciones">
        <span class="sb-icon sb-icon--yellow">
          <img src="<?= $icoAnim ?>stars.gif" alt="" class="sb-ico-img">
        </span>
        <span class="sb-label sb-hide-collapsed">Mis valoraciones</span>
      </a>
      <a class="sb-item <?= sbActive('perfil', $uri) ?>" href="<?= $baseUrl ?>/perfil" data-tooltip="Mi perfil">
        <span class="sb-icon sb-icon--teal"><i class="bi bi-person-circle"></i></span>
        <span class="sb-label sb-hide-collapsed">Mi perfil</span>
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
      <button class="sb-item sb-item--btn" onclick="document.getElementById('chatbotToggle')?.click()" data-tooltip="Chefbot IA">
        <span class="sb-icon sb-icon--purple"><i class="bi bi-robot"></i></span>
        <span class="sb-label sb-hide-collapsed">Chefbot</span>
        <span class="sb-pill sb-pill--ia sb-hide-collapsed">IA</span>
      </button>

    <?php endif; ?>

  </nav>

  <!-- ══ FOOTER CON USUARIO ════════════════════════════════════════════ -->
  <div class="sb-foot">
    <?php if (isset($_SESSION['usuario_id'])): ?>
      <a href="<?= $baseUrl ?>/perfil" class="sb-user-row" data-tooltip="Ver perfil">
        <div class="sb-user-av">
          <?php if ($avatarUrl): ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="sb-user-av-img"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span class="sb-user-av-initials" style="display:none"><?= $iniciales ?></span>
          <?php else: ?>
            <span class="sb-user-av-initials"><?= $iniciales ?></span>
          <?php endif; ?>
        </div>
        <div class="sb-user-info sb-hide-collapsed">
          <div class="sb-user-name"><?= htmlspecialchars($nombre) ?></div>
          <div class="sb-user-email"><?= htmlspecialchars($email) ?></div>
          <span class="sb-user-role sb-role--<?= $rol ?>">
            <?php if ($rol === 'admin'): ?>
              <i class="bi bi-shield-fill me-1" style="font-size:.6rem"></i>
            <?php elseif ($rol === 'chef'): ?>
              <i class="bi bi-star-fill me-1" style="font-size:.6rem"></i>
            <?php else: ?>
              <i class="bi bi-person-fill me-1" style="font-size:.6rem"></i>
            <?php endif; ?>
            <?= strtoupper($rol) ?>
          </span>
        </div>
        <i class="bi bi-chevron-right sb-user-chevron sb-hide-collapsed"></i>
      </a>
      <a class="sb-item <?= sbActive('notificaciones', $uri) ?>"
         href="<?= $baseUrl ?>/index.php?route=notificaciones" data-tooltip="Notificaciones">
        <span class="sb-icon sb-icon--yellow"><i class="bi bi-bell-fill"></i></span>
        <span class="sb-label sb-hide-collapsed">Notificaciones</span>
        <?php if ($notificacionesNoLeidas > 0): ?>
          <span class="sb-pill sb-pill--notification"><?= $notificacionesNoLeidas > 99 ? '99+' : $notificacionesNoLeidas ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= $baseUrl ?>/logout" class="sb-logout" data-tooltip="Cerrar sesión">
        <i class="bi bi-box-arrow-left"></i>
        <span class="sb-hide-collapsed">Cerrar sesión</span>
      </a>
    <?php else: ?>
      <a href="<?= $baseUrl ?>/login" class="sb-auth-btn sb-auth-btn--login">
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

<?php include __DIR__ . '/context_help.php'; ?>

<script>
/* ── Buscador de items del sidebar ─────────────────────────────── */
(function () {
  var inp = document.getElementById('sbSearch');
  if (!inp) return;
  inp.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('.sb-item').forEach(function (item) {
      var label = (item.querySelector('.sb-label') || item).textContent.toLowerCase();
      item.style.display = (!q || label.includes(q)) ? '' : 'none';
    });
    // Ocultar separadores vacíos
    document.querySelectorAll('.sb-sep').forEach(function (sep) {
      var next = sep.nextElementSibling;
      var anyVisible = false;
      while (next && !next.classList.contains('sb-sep')) {
        if (next.style.display !== 'none') { anyVisible = true; break; }
        next = next.nextElementSibling;
      }
      sep.style.display = anyVisible ? '' : 'none';
    });
  });
})();
</script>
