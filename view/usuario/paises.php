<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/categorias.php';   // getCategoriaIcono(), getCategoriaColor()

$pageTitle = 'Explorar Países';
$pdo       = Conexion::getInstancia()->getPDO();
$baseUrl   = rtrim(BASE_URL, '/');

// ── Todos los países (con o sin recetas) ─────────────────────────────────────
$paises = [];
try {
    $paises = $pdo->query("
        SELECT p.id_pais AS id,
               p.nombre,
               COALESCE(p.codigo_iso,'') AS codigo_iso,
               COUNT(DISTINCT r.id_receta) AS total_recetas
        FROM paises p
        LEFT JOIN recetas r ON r.id_pais = p.id_pais AND r.publicada = 1
        GROUP BY p.id_pais, p.nombre, p.codigo_iso
        ORDER BY total_recetas DESC, p.nombre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $paises = $pdo->query("
            SELECT id_pais AS id, nombre,
                   COALESCE(codigo_iso,'') AS codigo_iso,
                   0 AS total_recetas
            FROM paises ORDER BY nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) { $paises = []; }
}

// ── País seleccionado ─────────────────────────────────────────────────────────
$paisSelId   = (int)($_GET['pais_id'] ?? 0);
$paisSelNombre = '';
$recetasPais = [];

if ($paisSelId) {
    // Nombre del país seleccionado
    try {
        $s = $pdo->prepare("SELECT nombre FROM paises WHERE id_pais = ?");
        $s->execute([$paisSelId]);
        $paisSelNombre = $s->fetchColumn() ?: '';
    } catch (Throwable $e) {}

    // Recetas del país
    if ($paisSelNombre) {
        try {
            $s = $pdo->prepare("
                SELECT r.id_receta AS id, r.titulo, r.descripcion,
                       r.tiempo_preparacion, r.dificultad, r.imagen,
                       r.calorias, r.porciones, r.visitas,
                       cat.nombre AS categoria,
                       cat.icono  AS cat_icono,
                       cat.color  AS cat_color,
                       u.nombre   AS chef_nombre,
                       ROUND(AVG(v.puntuacion),1) AS rating
                FROM recetas r
                LEFT JOIN categorias cat ON cat.id_categoria = r.id_categoria
                LEFT JOIN usuarios   u   ON u.id_usuario     = r.id_chef
                LEFT JOIN valoraciones v ON v.id_receta      = r.id_receta
                WHERE r.id_pais = ? AND r.publicada = 1
                GROUP BY r.id_receta
                ORDER BY r.destacada DESC, r.visitas DESC
            ");
            $s->execute([$paisSelId]);
            $recetasPais = $s->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $recetasPais = []; }
    }
}

$totalConRecetas = count(array_filter($paises, fn($p) => (int)$p['total_recetas'] > 0));
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="pb-5">
<div class="container-fluid px-3">

<!-- ── Hero ── -->
<div class="paises-hero mb-4">
    <div class="paises-hero-inner">
        <span class="section-tag"><i class="bi bi-globe-americas me-1"></i>Gastronomía Mundial</span>
        <h1 class="paises-hero-title">Explora recetas por país</h1>
        <p class="paises-hero-sub">
            <?= count($paises) ?> países · <?= $totalConRecetas ?> con recetas publicadas
        </p>
    </div>
    <!-- Búsqueda rápida de país -->
    <div class="paises-search-wrap">
        <i class="bi bi-search paises-search-icon"></i>
        <input type="text"
               id="inputBuscarPais"
               class="paises-search-input"
               placeholder="Buscar país…"
               autocomplete="off"
               aria-label="Buscar país">
    </div>
</div>

<!-- ── Layout: grid países + panel detalle ── -->
<div class="paises-layout" id="paisesLayout">

    <!-- Grid de países -->
    <div class="paises-grid-wrap">

        <!-- Filtros rápidos -->
        <div class="d-flex gap-2 mb-3 flex-wrap" id="filtrosPaises">
            <button class="paises-filtro active" data-filtro="todos">
                🌍 Todos (<?= count($paises) ?>)
            </button>
            <button class="paises-filtro" data-filtro="con-recetas">
                🍽️ Con recetas (<?= $totalConRecetas ?>)
            </button>
        </div>

        <!-- Cards -->
        <div class="paises-grid" id="paisesList">
            <?php foreach ($paises as $p):
                $nombre   = htmlspecialchars($p['nombre']);
                $iso      = strtolower(trim($p['codigo_iso']));
                $total    = (int)$p['total_recetas'];
                $flagUrl  = $iso ? "$baseUrl/img/icon/flags/4x3/$iso.svg" : '';
                $isActive = ($p['id'] == $paisSelId);
            ?>
            <button class="pais-card-btn <?= $isActive ? 'active' : '' ?> <?= $total > 0 ? 'tiene-recetas' : '' ?>"
                    data-pais-id="<?= (int)$p['id'] ?>"
                    data-nombre="<?= strtolower($p['nombre']) ?>"
                    data-tiene-recetas="<?= $total > 0 ? '1' : '0' ?>"
                    title="<?= $nombre ?> — <?= $total ?> receta<?= $total != 1 ? 's' : '' ?>">

                <!-- Bandera -->
                <div class="pais-flag-wrap">
                    <?php if ($flagUrl): ?>
                    <img src="<?= $flagUrl ?>"
                         alt="<?= $nombre ?>"
                         class="pais-flag-img"
                         loading="lazy"
                         onerror="this.parentNode.innerHTML='<span class=\'pais-flag-fallback\'>🌐</span>'">
                    <?php else: ?>
                    <span class="pais-flag-fallback">🌐</span>
                    <?php endif; ?>
                </div>

                <!-- Nombre -->
                <span class="pais-nombre"><?= $nombre ?></span>

                <!-- Contador de recetas -->
                <?php if ($total > 0): ?>
                <span class="pais-recetas-badge"><?= $total ?></span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div><!-- /paises-grid -->

        <p class="paises-sin-resultado d-none text-muted text-center py-4 small">
            <i class="bi bi-search me-1"></i>No se encontró ningún país con ese nombre.
        </p>
    </div><!-- /paises-grid-wrap -->

    <!-- Panel de recetas del país seleccionado -->
    <div class="paises-detalle" id="paisesDetalle"
         style="<?= $paisSelId ? '' : 'display:none' ?>">

        <div id="detalleContent">
        <?php if ($paisSelId && $paisSelNombre): ?>
            <?php $isoSel = ''; ?>
            <?php foreach ($paises as $pp): if ((int)$pp['id'] === $paisSelId) { $isoSel = strtolower($pp['codigo_iso']); break; } endforeach; ?>

            <!-- Header del panel -->
            <div class="detalle-header">
                <?php if ($isoSel): ?>
                <img src="<?= $baseUrl ?>/img/icon/flags/4x3/<?= $isoSel ?>.svg"
                     alt="<?= htmlspecialchars($paisSelNombre) ?>"
                     class="detalle-flag"
                     onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="detalle-header-text">
                    <h2 class="detalle-titulo"><?= htmlspecialchars($paisSelNombre) ?></h2>
                    <p class="detalle-sub">
                        <?= count($recetasPais) ?> receta<?= count($recetasPais) != 1 ? 's' : '' ?> disponible<?= count($recetasPais) != 1 ? 's' : '' ?>
                    </p>
                </div>
                <button class="detalle-cerrar" id="btnCerrarDetalle" title="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Recetas -->
            <?php if (empty($recetasPais)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-egg-fried"></i>
                <h5>Aún no hay recetas de <?= htmlspecialchars($paisSelNombre) ?></h5>
                <p class="small">Pronto los chefs agregarán recetas de este país.</p>
            </div>
            <?php else: ?>
            <div class="detalle-recetas-grid">
                <?php foreach ($recetasPais as $r):
                    // ── Imagen de la receta ──────────────────────────────
                    $imgField  = trim($r['imagen'] ?? '');
                    // La BD puede guardar solo el nombre, con o sin extensión
                    // Intentamos encontrar el archivo en /uploads/
                    $imgSrc    = null;
                    $rootUploads = (defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../..') . '/uploads/';
                    if ($imgField) {
                        // Primero el nombre exacto
                        if (file_exists($rootUploads . $imgField)) {
                            $imgSrc = $baseUrl . '/uploads/' . rawurlencode($imgField);
                        } else {
                            // Probar añadiendo extensiones comunes
                            foreach (['.jpg', '.jpeg', '.png', '.webp', '.JPG'] as $ext) {
                                if (file_exists($rootUploads . $imgField . $ext)) {
                                    $imgSrc = $baseUrl . '/uploads/' . rawurlencode($imgField . $ext);
                                    break;
                                }
                            }
                        }
                    }

                    // ── Icono de categoría (fallback robusto) ────────────
                    $catNombre = $r['categoria'] ?? 'Otros';
                    // Usar getCategoriaIcono() si está disponible, sino construir manualmente
                    if (function_exists('getCategoriaIcono')) {
                        $catIcono = getCategoriaIcono($catNombre);
                    } else {
                        $catSlug  = strtolower(str_replace([' ', '/'], ['-', '-'], $catNombre));
                        $catSlug  = preg_replace('/[^a-z0-9\-]/', '', $catSlug);
                        $catIcono = $catSlug . '.png';
                        $rootIcon = (defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../..') . '/img/Icon/Categorias/';
                        if (!file_exists($rootIcon . $catIcono)) {
                            $catIcono = 'plato-principal.png';
                        }
                    }
                    $catIconUrl = $baseUrl . '/img/Icon/Categorias/' . $catIcono;
                    $color = $r['cat_color'] ?? '#c0392b';
                ?>
                <a href="<?= $baseUrl ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                   class="detalle-receta-card">

                    <!-- Imagen real o placeholder con icono de categoría -->
                    <?php if ($imgSrc): ?>
                    <div class="detalle-receta-img"
                         style="background-image:url('<?= $imgSrc ?>')">
                    </div>
                    <?php else: ?>
                    <div class="detalle-receta-img detalle-receta-img--placeholder"
                         style="background:linear-gradient(135deg,<?= htmlspecialchars($color) ?>22,<?= htmlspecialchars($color) ?>44)">
                        <img src="<?= $catIconUrl ?>"
                             alt="<?= htmlspecialchars($catNombre) ?>"
                             style="width:34px;height:34px;object-fit:contain;opacity:.5"
                             onerror="this.style.display='none';this.nextSibling.style.display='block'">
                        <i class="bi bi-egg-fried" style="color:<?= htmlspecialchars($color) ?>;display:none;font-size:1.6rem;opacity:.4"></i>
                    </div>
                    <?php endif; ?>

                    <!-- Info -->
                    <div class="detalle-receta-info">
                        <div class="detalle-receta-nombre"><?= htmlspecialchars($r['titulo']) ?></div>
                        <div class="detalle-receta-meta">
                            <?php if (!empty($r['categoria'])): ?>
                            <span class="detalle-receta-cat"><?= htmlspecialchars($r['categoria']) ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-clock me-1"></i><?= (int)$r['tiempo_preparacion'] ?> min</span>
                            <span class="detalle-receta-dif detalle-dif-<?= strtolower($r['dificultad'] ?? 'media') ?>">
                                <?= htmlspecialchars($r['dificultad'] ?? '') ?>
                            </span>
                            <?php if ($r['rating']): ?>
                            <span style="color:var(--gold)">
                                <i class="bi bi-star-fill"></i> <?= $r['rating'] ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right detalle-receta-arrow"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        </div><!-- /detalleContent -->

        <!-- Estado vacío (cuando aún no se seleccionó) -->
        <div id="detallePlaceholder" style="<?= $paisSelId ? 'display:none' : '' ?>"
             class="detalle-placeholder">
            <i class="bi bi-globe-americas"></i>
            <p>Selecciona un país para ver sus recetas</p>
        </div>

    </div><!-- /paises-detalle -->

</div><!-- /paises-layout -->

</div><!-- /container -->
</main>

<style>
/* ── Hero ── */
.paises-hero {
    background: linear-gradient(135deg, var(--dark) 0%, #4a2410 60%, var(--dark-2) 100%);
    border-radius: var(--r-lg);
    padding: 2.5rem 2rem 2rem;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.paises-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 80% 50%, rgba(232,168,56,.12) 0%, transparent 60%);
    pointer-events: none;
}
.paises-hero-inner { position: relative; z-index: 1; }
.paises-hero-title {
    font-size: clamp(1.5rem, 3vw, 2.2rem);
    font-weight: 800;
    color: #fff;
    margin-bottom: .35rem;
    letter-spacing: -.02em;
}
.paises-hero-sub { color: rgba(255,255,255,.55); font-size: .9rem; margin: 0; }

/* Búsqueda */
.paises-search-wrap {
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}
.paises-search-icon {
    position: absolute;
    left: .9rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,.45);
    font-size: .9rem;
    pointer-events: none;
}
.paises-search-input {
    background: rgba(255,255,255,.1);
    border: 1.5px solid rgba(255,255,255,.18);
    border-radius: 30px;
    padding: .6rem 1.1rem .6rem 2.4rem;
    color: #fff;
    font-size: .88rem;
    min-width: 220px;
    backdrop-filter: blur(8px);
    transition: all .2s;
    font-family: 'Nunito', sans-serif;
}
.paises-search-input::placeholder { color: rgba(255,255,255,.4); }
.paises-search-input:focus {
    outline: none;
    border-color: var(--gold);
    background: rgba(255,255,255,.16);
    box-shadow: 0 0 0 3px rgba(232,168,56,.2);
}

/* ── Layout ── */
.paises-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (min-width: 1024px) {
    .paises-layout.detalle-abierto {
        grid-template-columns: 1fr 420px;
    }
}

/* ── Filtros ── */
.paises-filtro {
    padding: .38rem 1.1rem;
    border-radius: 20px;
    border: 1.5px solid var(--border);
    background: #fff;
    color: var(--text);
    font-size: .8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .18s;
    font-family: 'Nunito', sans-serif;
}
.paises-filtro:hover { border-color: var(--gold); color: var(--orange); }
.paises-filtro.active {
    background: linear-gradient(135deg, var(--orange), var(--red));
    border-color: transparent;
    color: #fff;
}

/* ── Grid de países ── */
.paises-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: .65rem;
}
@media (max-width: 576px) {
    .paises-grid { grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); }
}

/* ── Card de país (botón) ── */
.pais-card-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    padding: .9rem .5rem .75rem;
    background: #fff;
    border: 2px solid var(--border);
    border-radius: 14px;
    cursor: pointer;
    transition: all .22s cubic-bezier(.34,1.56,.64,1);
    position: relative;
    font-family: 'Nunito', sans-serif;
    text-align: center;
}
.pais-card-btn:hover {
    transform: translateY(-5px);
    border-color: var(--gold);
    box-shadow: 0 8px 22px rgba(232,168,56,.22);
}
.pais-card-btn.active {
    border-color: var(--orange);
    background: linear-gradient(135deg, #fff8f2, #fff3e8);
    box-shadow: 0 6px 20px rgba(211,84,0,.22);
}
.pais-card-btn:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }

/* Bandera */
.pais-flag-wrap {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,.14);
    flex-shrink: 0;
    transition: transform .22s;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--cream-2);
}
.pais-card-btn:hover .pais-flag-wrap,
.pais-card-btn.active .pais-flag-wrap { transform: scale(1.1) rotate(3deg); }
.pais-flag-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pais-flag-fallback { font-size: 1.8rem; line-height: 1; }

/* Nombre */
.pais-nombre {
    font-size: .72rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pais-card-btn.active .pais-nombre { color: var(--orange); }

/* Badge de recetas */
.pais-recetas-badge {
    position: absolute;
    top: 5px;
    right: 7px;
    background: linear-gradient(135deg, var(--gold), var(--orange));
    color: #fff;
    font-size: .6rem;
    font-weight: 800;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,.18);
}

/* Sin recetas: más tenue */
.pais-card-btn:not(.tiene-recetas) {
    opacity: .55;
}
.pais-card-btn:not(.tiene-recetas):hover { opacity: .8; }

/* ── Panel detalle ── */
.paises-detalle {
    background: #fff;
    border-radius: var(--r-lg);
    box-shadow: 0 4px 24px rgba(60,30,10,.1);
    overflow: hidden;
    position: sticky;
    top: 1.5rem;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--cream-3) transparent;
}
@media (max-width: 1023px) {
    .paises-detalle {
        position: static;
        max-height: none;
    }
}

/* Header del panel */
.detalle-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.2rem 1.4rem;
    background: linear-gradient(135deg, var(--dark), #4a2410);
    position: sticky;
    top: 0;
    z-index: 2;
}
.detalle-flag {
    width: 56px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
    flex-shrink: 0;
}
.detalle-header-text { flex: 1; min-width: 0; }
.detalle-titulo {
    font-size: 1.15rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.detalle-sub { color: rgba(255,255,255,.55); font-size: .78rem; margin: 0; }
.detalle-cerrar {
    background: rgba(255,255,255,.12);
    border: none;
    color: rgba(255,255,255,.7);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .2s;
    font-size: .85rem;
}
.detalle-cerrar:hover { background: rgba(255,255,255,.22); color: #fff; }

/* Placeholder */
.detalle-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .75rem;
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-m);
}
.detalle-placeholder i { font-size: 3rem; opacity: .22; color: var(--gold); }
.detalle-placeholder p { font-size: .9rem; font-weight: 600; margin: 0; }

/* Grid de recetas en el panel */
.detalle-recetas-grid {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.detalle-receta-card {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .9rem 1.2rem;
    border-bottom: 1px solid var(--cream-2);
    text-decoration: none;
    color: var(--text);
    transition: background .18s;
}
.detalle-receta-card:hover {
    background: linear-gradient(90deg, #fff8f2, #fff);
    color: var(--text);
}
.detalle-receta-card:last-child { border-bottom: none; }

.detalle-receta-img {
    width: 64px;
    height: 52px;
    border-radius: 10px;
    background-size: cover;
    background-position: center;
    flex-shrink: 0;
    overflow: hidden;
}
.detalle-receta-img--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}

.detalle-receta-info { flex: 1; min-width: 0; }
.detalle-receta-nombre {
    font-weight: 700;
    font-size: .88rem;
    color: var(--dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: .2rem;
}
.detalle-receta-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .4rem;
    font-size: .72rem;
    color: var(--text-m);
}
.detalle-receta-cat {
    background: var(--cream-2);
    padding: .1rem .5rem;
    border-radius: 10px;
    font-weight: 700;
    color: var(--text);
    font-size: .68rem;
}
.detalle-dif-fácil,
.detalle-dif-facil  { color: #1a8a44; font-weight: 700; }
.detalle-dif-media  { color: var(--gold-d); font-weight: 700; }
.detalle-dif-difícil,
.detalle-dif-dificil { color: var(--red); font-weight: 700; }
.detalle-receta-arrow {
    color: var(--border);
    font-size: .9rem;
    flex-shrink: 0;
    transition: color .2s, transform .2s;
}
.detalle-receta-card:hover .detalle-receta-arrow {
    color: var(--orange);
    transform: translateX(3px);
}
</style>

<script>
(function () {
    'use strict';
    const BASE_URL   = <?= json_encode($baseUrl) ?>;
    const CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;

    const grid       = document.getElementById('paisesList');
    const layout     = document.getElementById('paisesLayout');
    const detalle    = document.getElementById('paisesDetalle');
    const detalleContent = document.getElementById('detalleContent');
    const placeholder    = document.getElementById('detallePlaceholder');
    const cerrarBtn  = document.getElementById('btnCerrarDetalle');
    const buscarInput = document.getElementById('inputBuscarPais');
    const sinResultado = document.querySelector('.paises-sin-resultado');

    // ── Búsqueda de países ────────────────────────────────────────────
    buscarInput.addEventListener('input', function () {
        const q    = this.value.trim().toLowerCase();
        const btns = grid.querySelectorAll('.pais-card-btn');
        let visible = 0;
        btns.forEach(btn => {
            const nombre = btn.dataset.nombre || '';
            const match  = !q || nombre.includes(q);
            btn.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        sinResultado.classList.toggle('d-none', visible > 0);
    });

    // ── Filtros ───────────────────────────────────────────────────────
    document.querySelectorAll('.paises-filtro').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.paises-filtro').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filtro = this.dataset.filtro;
            grid.querySelectorAll('.pais-card-btn').forEach(card => {
                if (filtro === 'con-recetas') {
                    card.style.display = card.dataset.tieneRecetas === '1' ? '' : 'none';
                } else {
                    card.style.display = '';
                }
            });
            // Limpiar búsqueda al cambiar filtro
            buscarInput.value = '';
            sinResultado.classList.add('d-none');
        });
    });

    // ── Seleccionar país (fetch AJAX) ─────────────────────────────────
    grid.addEventListener('click', function (e) {
        const btn = e.target.closest('.pais-card-btn');
        if (!btn) return;

        const id = btn.dataset.paisId;

        // Marcar activo visualmente
        grid.querySelectorAll('.pais-card-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Actualizar URL sin recargar
        const url = new URL(window.location.href);
        url.searchParams.set('pais_id', id);
        history.pushState({}, '', url);

        // Mostrar panel con spinner
        detalle.style.display = '';
        placeholder.style.display = 'none';
        detalleContent.innerHTML = `
            <div class="d-flex align-items-center justify-content-center py-5">
                <div class="spinner-border" style="color:var(--gold);width:2rem;height:2rem" role="status">
                    <span class="visually-hidden">Cargando…</span>
                </div>
            </div>`;
        layout.classList.add('detalle-abierto');

        // Fetch parcial reutilizando la misma página
        fetch(BASE_URL + '/index.php?route=paises&pais_id=' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            // Extraer solo el bloque #detalleContent del HTML devuelto
            const parser = new DOMParser();
            const doc    = parser.parseFromString(html, 'text/html');
            const fresh  = doc.getElementById('detalleContent');
            if (fresh) {
                detalleContent.innerHTML = fresh.innerHTML;
                // Re-registrar botón cerrar
                const cerrar = detalleContent.querySelector('#btnCerrarDetalle');
                if (cerrar) cerrar.addEventListener('click', cerrarDetalle);
            } else {
                detalleContent.innerHTML = '<div class="p-4 text-muted small">No se pudo cargar el contenido.</div>';
            }
        })
        .catch(() => {
            detalleContent.innerHTML = '<div class="p-4 text-muted small">Error al cargar recetas.</div>';
        });
    });

    // ── Cerrar panel ──────────────────────────────────────────────────
    if (cerrarBtn) cerrarBtn.addEventListener('click', cerrarDetalle);

    function cerrarDetalle() {
        detalle.style.display = 'none';
        layout.classList.remove('detalle-abierto');
        grid.querySelectorAll('.pais-card-btn').forEach(b => b.classList.remove('active'));
        const url = new URL(window.location.href);
        url.searchParams.delete('pais_id');
        history.pushState({}, '', url);
    }

    // Abrir panel si venimos con pais_id en la URL (carga inicial)
    <?php if ($paisSelId): ?>
    layout.classList.add('detalle-abierto');
    detalle.style.display = '';
    <?php endif; ?>
})();
</script>

<?php include __DIR__ . '/../../assets/chatbot.php'; ?>
<?php include __DIR__ . '/../../assets/fooder.php'; ?>
