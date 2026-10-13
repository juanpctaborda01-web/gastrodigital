<?php
/**
 * assets/page_header.php
 * ─────────────────────────────────────────────────────────────────────
 * Componente reutilizable: Hero de sección unificado para las 3 UIs.
 * Referencia visual: cambiar_contrasenas.php (panel oscuro + section-tag + título).
 *
 * Uso en cualquier vista:
 *   $phTag    = 'Seguridad';                // etiqueta pequeña (opcional)
 *   $phTagIcon= 'bi-shield-lock';           // icono Bootstrap Icons (opcional)
 *   $phTitle  = 'Cambiar contraseñas';      // título h1 principal
 *   $phSub    = 'Texto descriptivo…';       // subtítulo (opcional)
 *   $phArt    = 'key-fill';                 // ícono BS grande derecho (opcional)
 *   $phArtImg = '/img/Icon/…/icon.png';    // imagen PNG derecha (alternativa a $phArt)
 *   $phBack   = ['label'=>'Volver','href'=>BASE_URL.'/admin']; // botón volver (opcional)
 *   include __DIR__ . '/../../assets/page_header.php';
 *   // (limpiar variables después si se reutiliza)
 */
$_ph_tag     = $phTag     ?? '';
$_ph_tagIco  = $phTagIcon ?? '';
$_ph_title   = $phTitle   ?? 'Sin título';
$_ph_sub     = $phSub     ?? '';
$_ph_art     = $phArt     ?? '';
$_ph_artImg  = $phArtImg  ?? '';
$_ph_back    = $phBack    ?? null;

// Limpiar para no contaminar otras inclusiones
unset($phTag, $phTagIcon, $phTitle, $phSub, $phArt, $phArtImg, $phBack);
?>
<div class="gd-section-hero mb-4">

  <!-- Arte lateral derecha -->
  <?php if ($_ph_art || $_ph_artImg): ?>
  <div class="gd-section-hero-art d-none d-md-block">
    <?php if ($_ph_artImg): ?>
      <img src="<?= htmlspecialchars($_ph_artImg) ?>" alt="">
    <?php else: ?>
      <i class="bi bi-<?= htmlspecialchars($_ph_art) ?>"
         style="font-size:4rem;opacity:.28;color:#fff;display:block"></i>
    <?php endif; ?>
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
      <?php if ($_ph_tagIco): ?><i class="bi bi-<?= htmlspecialchars($_ph_tagIco) ?> me-1"></i><?php endif; ?>
      <?= htmlspecialchars($_ph_tag) ?>
    </span>
    <?php endif; ?>

    <h1 class="gd-section-hero-title"><?= htmlspecialchars($_ph_title) ?></h1>

    <?php if ($_ph_sub): ?>
    <p class="gd-section-hero-sub"><?= $_ph_sub /* puede contener HTML controlado */ ?></p>
    <?php endif; ?>
  </div>

</div>
