<?php
/**
 * assets/page_header.php
 * ─────────────────────────────────────────────────────────────────────
 * Componente reutilizable: Hero de sección unificado para las 3 UIs.
 * Referencia visual: cambiar_contrasenas.php (panel oscuro + section-tag + título).
 *
 * Uso en cualquier vista:
 *   $phTag    = 'Seguridad';                // etiqueta pequeña (opcional)
 *   $phTagImg = BASE_URL . '/img/Icon/...'; // icono local para la etiqueta (opcional)
 *   $phTitle  = 'Cambiar contraseñas';      // título h1 principal
 *   $phSub    = 'Texto descriptivo…';       // subtítulo (opcional)
 *   $phArtImg = BASE_URL . '/img/Icon/...'; // imagen local lateral (opcional)
 *   $phBack   = ['label'=>'Volver','href'=>BASE_URL.'/admin']; // botón volver (opcional)
 *   include __DIR__ . '/../../assets/page_header.php';
 *   // (limpiar variables después si se reutiliza)
 */
$_ph_tag     = $phTag     ?? '';
$_ph_tagImg  = $phTagImg  ?? '';
$_ph_title   = $phTitle   ?? 'Sin título';
$_ph_sub     = $phSub     ?? '';
$_ph_artImg  = $phArtImg  ?? '';
$_ph_back    = $phBack    ?? null;

// Limpiar para no contaminar otras inclusiones
unset($phTag, $phTagIcon, $phTagImg, $phTitle, $phSub, $phArt, $phArtImg, $phBack);
?>
<div class="gd-section-hero mb-4">

  <!-- Arte lateral derecha -->
  <?php if ($_ph_artImg): ?>
  <div class="gd-section-hero-art d-none d-md-block">
    <img src="<?= htmlspecialchars($_ph_artImg) ?>" alt="">
  </div>
  <?php endif; ?>

  <!-- Cuerpo de texto -->
  <div class="gd-section-hero-body">
    <?php if ($_ph_back): ?>
    <a href="<?= htmlspecialchars($_ph_back['href']) ?>"
       class="gd-ph-back sb-hide-collapsed">
      <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($_ph_back['label']) ?>
    </a>
    <?php endif; ?>

    <?php if ($_ph_tag): ?>
    <span class="section-tag">
      <?php if ($_ph_tagImg): ?>
        <img class="gd-section-hero-tag-icon" src="<?= htmlspecialchars($_ph_tagImg) ?>" alt="">
      <?php endif; ?>
      <?= htmlspecialchars($_ph_tag) ?>
    </span>
    <?php endif; ?>

    <h1 class="gd-section-hero-title"><?= htmlspecialchars($_ph_title) ?></h1>

    <?php if ($_ph_sub): ?>
    <p class="gd-section-hero-sub"><?= $_ph_sub /* puede contener HTML controlado */ ?></p>
    <?php endif; ?>
  </div>

</div>
