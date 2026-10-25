<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/receta.php';
require_once __DIR__ . '/models/categoria.php';

// La landing pública debe poder mostrarse aunque haya una sesión activa.
// Si el usuario entra a rutas de dashboard, se redirige desde las vistas específicas.

// ── Datos desde la BD ────────────────────────────────────────────────────────
$recetaModel    = new Receta();
$categoriaModel = new Categoria();
require_once __DIR__ . '/config/categorias.php';

$destacadas    = $recetaModel->masPopulares(3);
$categorias    = $categoriaModel->listarSimple();
$stats         = $recetaModel->stats();
$favoritosIds  = isLoggedIn()
    ? $recetaModel->favoritosIds((int)$_SESSION['usuario_id'], array_column($destacadas, 'id'))
    : [];

$pageTitle = 'Inicio';
?>
<?php include __DIR__ . '/assets/header.php'; ?>
<?php include __DIR__ . '/assets/navbar.php'; ?>

<main>

    <!-- ══ HERO ══════════════════════════════════════════════════════════ -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="row align-items-center min-vh-hero">
                <div class="col-lg-6">
                    <span class="hero-tag">
                        <i class="bi bi-stars me-1"></i> La comunidad gastronómica
                    </span>
                    <h1 class="hero-title">
                        Descubre recetas que <span class="hero-highlight">inspiran</span>
                    </h1>
                    <p class="hero-subtitle">
                        Recetas creadas por chefs apasionados. Filtra por ingredientes,
                        tiempo o dificultad y cocina algo increíble hoy.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="<?= BASE_URL ?>/index.php?route=recetas" class="btn btn-hero-primary">
                            <i class="bi bi-search me-2"></i>Explorar recetas
                        </a>
                        <a href="<?= BASE_URL ?>/index.php?route=register" class="btn btn-hero-outline">
                            <i class="bi bi-person-plus me-2"></i>Unirme gratis
                        </a>
                    </div>
                    <!-- Stats reales desde la BD -->
                    <div class="hero-stats mt-5">
                        <div class="hero-stat">
                            <span class="hero-stat-num"><?= $stats['total'] ?>+</span>
                            <span class="hero-stat-label">Recetas</span>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat">
                            <span class="hero-stat-num"><?= count($categorias) ?></span>
                            <span class="hero-stat-label">Categorías</span>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat">
                            <span class="hero-stat-num"><?= $stats['publicadas'] ?></span>
                            <span class="hero-stat-label">Publicadas</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 d-none d-lg-flex justify-content-center">
                    <div class="hero-cards-stack">
                        <?php
                        $clases = ['card-1', 'card-2', 'card-3'];
                        $muestra = array_slice($destacadas, 0, 3);
                        foreach ($muestra as $idx => $r):
                            // Usar getCategoriaIcono para obtener el PNG correcto
                            $catHero    = $r['categoria'] ?? 'Otros';
                            $icoHero    = getCategoriaIcono($catHero);
                            $icoUrlHero = BASE_URL . '/img/Icon/Categorias/' . $icoHero;
                        ?>
                        <a class="hero-float-card <?= $clases[$idx] ?>"
                           href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int) $r['id'] ?>"
                           aria-label="Ver receta <?= htmlspecialchars($r['titulo']) ?>">
                            <div class="hero-card-top">
                                <span class="hero-card-icon-wrap">
                                    <img src="<?= $icoUrlHero ?>"
                                         alt=""
                                         class="hero-card-icon-img"
                                         onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                                    <i class="bi bi-egg-fried hero-card-fallback" aria-hidden="true" hidden></i>
                                </span>
                                <i class="bi bi-arrow-up-right hero-card-link-icon" aria-hidden="true"></i>
                            </div>
                            <span class="hero-card-title"><?= htmlspecialchars($r['titulo']) ?></span>
                            <small>
                                <i class="bi bi-clock" style="font-size:.65rem;margin-right:3px"></i>
                                <?= $r['tiempo_preparacion'] ?> min
                                <?php if (!empty($catHero)): ?>
                                · <?= htmlspecialchars($catHero) ?>
                                <?php endif; ?>
                            </small>
                        </a>
                        <?php endforeach; ?>
                        <div class="hero-circle-bg"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
                <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="#fdf8f2"/>
            </svg>
        </div>
    </section>

    <!-- ══ CATEGORÍAS ════════════════════════════════════════════════════ -->
    <section class="py-5" style="background:#fdf8f2">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-tag">Explora</span>
                <h2 class="section-title">Cocinas del mundo</h2>
                <p class="section-subtitle">Encuentra tu próxima receta favorita por categoría</p>
            </div>
            <div class="categorias-scroll">
                <?php foreach ($categorias as $cat):
                    // Obtener icono PNG de la BD directamente (no clase Bootstrap)
                    $catIcoFile = !empty($cat['icono']) ? $cat['icono'] : getCategoriaIcono($cat['nombre'] ?? '');
                    // Si el icono es una clase Bootstrap (empieza por 'bi-'), usar PNG de fallback
                    if (str_starts_with($catIcoFile, 'bi-')) {
                        $catIcoFile = getCategoriaIcono($cat['nombre'] ?? '');
                    }
                ?>
                <a href="<?= BASE_URL ?>/index.php?route=recetas&categoria=<?= urlencode($cat['nombre']) ?>"
                   class="categoria-chip"
                   style="--chip-color:<?= htmlspecialchars($cat['color']) ?>;--chip-bg:<?= htmlspecialchars($cat['bg']) ?>">
                    <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= htmlspecialchars($catIcoFile) ?>"
                         alt="<?= htmlspecialchars($cat['nombre']) ?>"
                         style="width:20px;height:20px;object-fit:contain"
                         loading="lazy"
                         decoding="async"
                         onerror="this.style.display='none'">
                    <span><?= htmlspecialchars($cat['nombre']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══ TOP 3 RECETAS MÁS POPULARES ═════════════════════════════════ -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                <div>
                    <span class="section-tag">
                        <i class="bi bi-fire me-1"></i>En tendencia
                    </span>
                    <h2 class="section-title mb-0">Recetas más populares</h2>
                    <p class="text-muted small mt-1">Las favoritas de la comunidad en este momento</p>
                </div>
                <a href="<?= BASE_URL ?>/index.php?route=recetas"
                   class="btn btn-outline-primary btn-sm d-none d-md-flex align-items-center gap-1">
                    Ver todas <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if (empty($destacadas)): ?>
                <p class="text-muted">Aún no hay recetas publicadas.</p>
            <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($destacadas as $idx => $r):
                    $catNombre = $r['categoria'] ?? $r['cat_nombre'] ?? 'Otros';
                    $color     = getCategoriaColor($catNombre);
                    $catIcono  = getCategoriaIcono($catNombre);
                    $isoFlag = strtolower((string) ($r['pais_iso'] ?? ''));
                    $esFav = in_array((int)$r['id'], $favoritosIds, true);
                    if (!preg_match('/^[a-z]{2,3}$/', $isoFlag)) {
                        $isoFlag = '';
                    }
                    $medals = ['🥇','🥈','🥉'];
                ?>
                <div class="col-sm-10 col-md-6 col-lg-4">
                    <div class="card receta-card h-100 position-relative overflow-hidden">

                        <!-- Imagen o placeholder con icono de categoría -->
                        <?php if (!empty($r['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $r['imagen'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                                 class="card-img-top"
                                 alt="<?= htmlspecialchars($r['titulo']) ?>"
                                 loading="lazy"
                                 decoding="async">
                        <?php else: ?>
                            <div class="receta-img-placeholder"
                                 style="background:linear-gradient(135deg,<?= $color ?>18,<?= $color ?>38)">
                                <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= $catIcono ?>"
                                     alt="<?= htmlspecialchars($catNombre) ?>"
                                     style="width:56px;height:56px;object-fit:contain;opacity:.45"
                                     onerror="this.style.display='none';this.nextSibling.style.display='block'">
                                <i class="bi bi-egg-fried" style="color:<?= $color ?>;display:none"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Medalla de posición -->
                        <span class="position-absolute top-0 end-0 m-2"
                              style="font-size:1.6rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))">
                            <?= $medals[$idx] ?? '' ?>
                        </span>

                        <!-- Badge categoría arriba-izquierda -->
                        <div class="position-absolute top-0 start-0 m-2">
                            <?php if (!empty($catNombre)): ?>
                            <span class="receta-badge-icon" style="font-size:.72rem">
                                <img src="<?= BASE_URL ?>/img/Icon/Categorias/<?= $catIcono ?>"
                                     alt="<?= htmlspecialchars($catNombre) ?>"
                                     style="width:16px;height:16px;object-fit:contain"
                                     onerror="this.style.display='none'">
                                <span class="receta-categoria-nombre" style="font-size:.68rem">
                                    <?= htmlspecialchars($catNombre) ?>
                                </span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Medalla posición — arriba-derecha -->
                        <!-- (ya está renderizada antes, no se duplica) -->

                        <!-- Bandera del país — abajo-derecha de la imagen -->
                        <?php if ($isoFlag): ?>
                        <img src="<?= BASE_URL ?>/img/icon/flags/4x3/<?= $isoFlag ?>.svg"
                             alt="País"
                             class="receta-flag-badge position-absolute"
                             style="bottom:10px;right:10px;z-index:2;width:28px;height:20px;
                                    border-radius:4px;box-shadow:0 1px 6px rgba(0,0,0,.28)"
                             onerror="this.style.display='none'">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title fw-700 mb-1">
                                <?= htmlspecialchars($r['titulo']) ?>
                            </h5>
                            <small class="text-muted">
                                por <?= htmlspecialchars($r['chef_nombre'] ?? 'Chef') ?>
                            </small>
                            <div class="d-flex align-items-center gap-3 text-muted small mt-2 flex-wrap">
                                <span><i class="bi bi-clock me-1"></i><?= (int)$r['tiempo_preparacion'] ?> min</span>
                                <span><i class="bi bi-bar-chart me-1"></i><?= htmlspecialchars($r['dificultad'] ?? '') ?></span>
                                <span><i class="bi bi-eye me-1"></i><?= number_format((int)$r['visitas']) ?></span>
                                <span><i class="bi bi-heart me-1"></i><?= (int)$r['total_favoritos'] ?></span>
                            </div>
                            <!-- Rating -->
                            <?php if (!empty($r['rating'])): ?>
                            <div class="mt-2 d-flex align-items-center gap-1">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi bi-star<?= $s <= round((float)$r['rating']) ? '-fill' : '' ?>"
                                   style="color:var(--gold);font-size:.75rem"></i>
                                <?php endfor; ?>
                                <span class="small fw-700 ms-1" style="color:var(--gold-d)">
                                    <?= $r['rating'] ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent border-0 pb-3 d-flex gap-2">
                            <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= (int)$r['id'] ?>"
                               class="btn btn-primary btn-sm flex-grow-1">
                                Ver receta <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <?php if (isLoggedIn()): ?>
                            <button type="button"
                                    class="btn btn-sm <?= $esFav ? 'btn-danger' : 'btn-outline-danger' ?> btn-fav"
                                    data-id="<?= (int)$r['id'] ?>"
                                    title="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                                    aria-label="<?= $esFav ? 'Quitar de favoritos' : 'Guardar en favoritos' ?>"
                                    aria-pressed="<?= $esFav ? 'true' : 'false' ?>">
                                <i class="bi bi-heart<?= $esFav ? '-fill' : '' ?>" aria-hidden="true"></i>
                            </button>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/index.php?route=login"
                               class="btn btn-sm btn-outline-danger"
                               title="Inicia sesión para guardar en favoritos"
                               aria-label="Inicia sesión para guardar en favoritos">
                                <i class="bi bi-heart" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ══ CÓMO FUNCIONA ════════════════════════════════════════════════ -->
    <section class="py-5" style="background:#fdf8f2">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-tag">Simple</span>
                <h2 class="section-title">¿Cómo funciona?</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <?php
                $pasos = [
                    ['num'=>'01','ico'=>'registro.png',       'dir'=>'ICON-Sobrantes', 'titulo'=>'Crea tu cuenta',    'desc'=>'Regístrate gratis en segundos y únete a la comunidad.'],
                    ['num'=>'02','ico'=>'plato-principal.png', 'dir'=>'Categorias',     'titulo'=>'Explora recetas',   'desc'=>'Busca por nombre, ingrediente, tiempo o categoría.'],
                    ['num'=>'03','ico'=>'parrillada.png',      'dir'=>'Categorias',     'titulo'=>'Publica las tuyas', 'desc'=>'Si eres chef, comparte tus creaciones con el mundo.'],
                    ['num'=>'04','ico'=>'cheque.png',          'dir'=>'ICON-Sobrantes', 'titulo'=>'Guarda favoritos',  'desc'=>'Marca las recetas que más te gusten para cocinarlas después.'],
                ];
                foreach ($pasos as $p):
                    $icoDir = BASE_URL . '/img/Icon/' . $p['dir'] . '/';
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="como-card text-center">
                        <div class="como-num"><?= $p['num'] ?></div>
                        <div class="como-icon">
                            <img src="<?= $icoDir . htmlspecialchars($p['ico']) ?>"
                                 alt="<?= htmlspecialchars($p['titulo']) ?>"
                                 style="width:40px;height:40px;object-fit:contain"
                                 onerror="this.style.display='none'">
                        </div>
                        <h5 class="fw-700 mt-3 mb-2"><?= $p['titulo'] ?></h5>
                        <p class="text-muted small mb-0"><?= $p['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══ CTA FINAL ═════════════════════════════════════════════════════ -->
    <section class="cta-section">
        <div class="container text-center">
            <img src="<?= BASE_URL ?>/img/Icon/Categorias/plato-principal.png"
                 alt="" class="cta-icon"
                 style="width:64px;height:64px;object-fit:contain;opacity:.85"
                 onerror="this.style.display='none'">
            <h2 class="cta-title">¿Listo para cocinar algo increíble?</h2>
            <p class="cta-subtitle">Únete a cocineros apasionados en GastroDigital</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
                <a href="<?= BASE_URL ?>/index.php?route=register" class="btn btn-hero-primary btn-lg">
                    <i class="bi bi-person-plus me-2"></i>Crear cuenta gratis
                </a>
                <a href="<?= BASE_URL ?>/index.php?route=login" class="btn btn-hero-outline btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                </a>
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/assets/fooder.php'; ?>
