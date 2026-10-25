<?php
/**
 * topbar.php — Barra superior blanca con logo, badge de rol, links y usuario
 * Incluir DESPUÉS de navbar.php en cada vista que lo necesite.
 *
 * Variables opcionales:
 *   $topbarBadge   string  texto del badge (ej. "Admin", "Club Gourmet", "Chef Cocina")
 *   $topbarLinks   array   [['label'=>'...','href'=>'...','icon'=>'bi-xxx'], ...]
 *   $topbarAction  array   ['label'=>'...','href'=>'...','icon'=>'bi-xxx','class'=>'...']
 *   $topbarBreadcrumb array [['label'=>'Inicio','href'=>'/'], ['label'=>'Dashboard']]
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$_tbBase    = rtrim(BASE_URL, '/');
$_tbRol     = $_SESSION['rol'] ?? '';
$_tbNombre  = $_SESSION['usuario_nombre'] ?? '';
$_tbEmail   = $_SESSION['usuario_email']  ?? '';
$_tbInic    = strtoupper(substr($_tbNombre, 0, 1));
$_tbAvatarUrl = urlAvatarPerfil($_SESSION['usuario_avatar'] ?? '');

// Badge por defecto según rol
if (!isset($topbarBadge)) {
    $topbarBadge = match($_tbRol) {
        'admin' => 'Admin',
        'chef'  => 'Chef Cocina',
        default => 'Club Gourmet',
    };
}
?>
<header class="gd-topbar">
  <!-- Lado izquierdo: logo + badge + breadcrumb -->
  <div class="gd-topbar-left">
    <a href="<?= $_tbBase ?>/" class="gd-topbar-brand">
      <img src="<?= $_tbBase ?>/img/logo-oficial-gastrodigital-optimized.png" class="gd-topbar-logo" alt="GastroDigital">
    </a>
    <?php if ($topbarBadge): ?>
    <span class="gd-topbar-badge"><?= htmlspecialchars($topbarBadge) ?></span>
    <?php endif; ?>
    <?php if (!empty($topbarBreadcrumb)): ?>
    <nav class="gd-topbar-breadcrumb" aria-label="breadcrumb">
      <?php foreach ($topbarBreadcrumb as $i => $crumb): ?>
        <?php if ($i > 0): ?><i class="bi bi-chevron-right gd-bc-sep"></i><?php endif; ?>
        <?php if (!empty($crumb['href'])): ?>
          <a href="<?= htmlspecialchars($crumb['href']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
        <?php else: ?>
          <span><?= htmlspecialchars($crumb['label']) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>

  <!-- Lado derecho: links rápidos + campana + usuario + salir -->
  <div class="gd-topbar-right">
    <?php if (!empty($topbarLinks)): ?>
      <?php foreach ($topbarLinks as $lnk): ?>
        <a href="<?= htmlspecialchars($lnk['href']) ?>"
           class="gd-topbar-link <?= $lnk['class'] ?? '' ?>">
          <?php if (!empty($lnk['icon'])): ?><i class="bi <?= htmlspecialchars($lnk['icon']) ?>"></i><?php endif; ?>
          <?= htmlspecialchars($lnk['label']) ?>
          <?php if (!empty($lnk['count'])): ?><span class="gd-topbar-count"><?= (int)$lnk['count'] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_id'])): ?>
    <a class="gd-topbar-bell position-relative"
       href="<?= $_tbBase ?>/index.php?route=notificaciones"
       title="Notificaciones" aria-label="Notificaciones">
      <i class="bi bi-bell"></i>
      <?php if (!empty($notificacionesNoLeidas)): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
          <?= $notificacionesNoLeidas > 99 ? '99+' : (int)$notificacionesNoLeidas ?>
        </span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if (!empty($topbarAction)): ?>
    <a href="<?= htmlspecialchars($topbarAction['href']) ?>"
       class="gd-topbar-action-btn <?= $topbarAction['class'] ?? '' ?>">
      <?php if (!empty($topbarAction['icon'])): ?><i class="bi <?= htmlspecialchars($topbarAction['icon']) ?>"></i><?php endif; ?>
      <?= htmlspecialchars($topbarAction['label']) ?>
    </a>
    <?php endif; ?>

    <!-- Usuario -->
    <?php if (isset($_SESSION['usuario_id'])): ?>
    <div class="gd-topbar-user">
      <div class="gd-topbar-av">
        <?php if ($_tbAvatarUrl): ?>
          <img src="<?= htmlspecialchars($_tbAvatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($_tbNombre) ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <span style="display:none"><?= htmlspecialchars($_tbInic) ?></span>
        <?php else: ?>
          <?= htmlspecialchars($_tbInic) ?>
        <?php endif; ?>
      </div>
      <div class="gd-topbar-user-info">
        <div class="gd-topbar-user-name"><?= htmlspecialchars($_tbNombre) ?></div>
        <div class="gd-topbar-user-role"><?= ucfirst($_tbRol) ?></div>
      </div>
    </div>
    <a href="<?= $_tbBase ?>/logout" class="gd-topbar-logout" title="Salir">
      <i class="bi bi-box-arrow-right"></i> Salir
    </a>
    <?php endif; ?>
  </div>
</header>
