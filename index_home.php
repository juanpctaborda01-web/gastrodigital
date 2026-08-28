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

$destacadas = $recetaModel->listarFiltradas(['destacada' => true], 1, 6)['recetas'];
$categorias = $categoriaModel->listarSimple();
$stats      = $recetaModel->stats();

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
                        $iconos = ['bi-egg-fried', 'bi-fire', 'bi-cup-hot'];
                        $clases = ['card-1', 'card-2', 'card-3'];
                        $muestra = array_slice($destacadas, 0, 3);
                        foreach ($muestra as $idx => $r):
                        ?>
                        <div class="hero-float-card <?= $clases[$idx] ?>">
                            <i class="bi <?= $iconos[$idx] ?>"></i>
                            <span><?= htmlspecialchars($r['titulo']) ?></span>
                            <small>⏱ <?= $r['tiempo_preparacion'] ?> min · <?= htmlspecialchars($r['categoria'] ?? '') ?></small>
                        </div>
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
                <?php foreach ($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>/index.php?route=recetas&categoria=<?= urlencode($cat['nombre']) ?>"
                   class="categoria-chip"
                   style="--chip-color:<?= htmlspecialchars($cat['color']) ?>;--chip-bg:<?= htmlspecialchars($cat['bg']) ?>">
                    <i class="bi <?= htmlspecialchars($cat['icono']) ?>"></i>
                    <span><?= htmlspecialchars($cat['nombre']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══ RECETAS DESTACADAS ════════════════════════════════════════════ -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <span class="section-tag">Populares</span>
                    <h2 class="section-title mb-0">Recetas destacadas</h2>
                </div>
                <a href="<?= BASE_URL ?>/index.php?route=recetas"
                   class="btn btn-outline-primary btn-sm d-none d-md-flex align-items-center gap-1">
                    Ver todas <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($destacadas)): ?>
                <p class="text-muted">Aún no hay recetas destacadas.</p>
            <?php else: ?>
            <div class="row g-4">
                <?php
                $colores = [];
                foreach (CATEGORIAS as $c) {
                    $colores[strtolower($c['nombre'])] = $c['color'];
                }
                foreach ($destacadas as $r):
                    $color = $colores[strtolower($r['categoria'] ?? '')] ?? '#c0392b';
                ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card receta-card h-100 position-relative overflow-hidden">
                        <?php if (!empty($r['imagen']) && file_exists(ROOT_PATH . '/uploads/' . $r['imagen'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($r['imagen']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($r['titulo']) ?>">
                        <?php else: ?>
                            <div class="receta-img-placeholder"
                                 style="background:linear-gradient(135deg,<?= $color ?>22,<?= $color ?>55)">
                                <i class="bi bi-egg-fried" style="color:<?= $color ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="receta-badge"><?= htmlspecialchars($r['categoria'] ?? '') ?></span>
                        <div class="card-body">
                            <h5 class="card-title fw-700 mb-1"><?= htmlspecialchars($r['titulo']) ?></h5>
                            <small class="text-muted">por <?= htmlspecialchars($r['chef_nombre'] ?? 'Chef') ?></small>
                            <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                                <span><i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min</span>
                                <span><i class="bi bi-bar-chart me-1"></i><?= $r['dificultad'] ?></span>
                                <?php if ($r['rating']): ?>
                                <span class="ms-auto fw-700" style="color:var(--secondary)">
                                    <i class="bi bi-star-fill me-1"></i><?= $r['rating'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                               class="btn btn-outline-primary btn-sm w-100">
                                Ver receta <i class="bi bi-arrow-right ms-1"></i>
                            </a>
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
                    ['num'=>'01','icon'=>'bi-person-plus',    'titulo'=>'Crea tu cuenta',    'desc'=>'Regístrate gratis en segundos y únete a la comunidad.'],
                    ['num'=>'02','icon'=>'bi-search',          'titulo'=>'Explora recetas',   'desc'=>'Busca por nombre, ingrediente, tiempo o categoría.'],
                    ['num'=>'03','icon'=>'bi-journal-richtext','titulo'=>'Publica las tuyas', 'desc'=>'Si eres chef, comparte tus creaciones con el mundo.'],
                    ['num'=>'04','icon'=>'bi-heart',           'titulo'=>'Guarda favoritos',  'desc'=>'Marca las recetas que más te gusten para cocinarlas después.'],
                ];
                foreach ($pasos as $p):
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="como-card text-center">
                        <div class="como-num"><?= $p['num'] ?></div>
                        <div class="como-icon"><i class="bi <?= $p['icon'] ?>"></i></div>
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
            <i class="bi bi-egg-fried cta-icon"></i>
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
