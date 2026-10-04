<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../config/config.php';
requireLogin();

$pageTitle = 'Mis Interacciones';
$pdo       = Conexion::getInstancia()->getPDO();
$userId    = (int)$_SESSION['usuario_id'];
$baseUrl   = rtrim(BASE_URL, '/');
$icoAnim   = $baseUrl . '/img/Icon/Icon-Animados/';

// ── Eliminar comentario ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_comentario'])) {
    $idCom = (int)($_POST['id_comentario'] ?? 0);
    if ($idCom) {
        try {
            // Primero intentar con la columna más común en las BDs
            $stmtDel = $pdo->prepare("DELETE FROM comentarios WHERE id_comentario = ? AND id_usuario = ?");
            $stmtDel->execute([$idCom, $userId]);
            if ($stmtDel->rowCount() === 0) {
                // Si no borró nada, probar con 'id'
                $stmtDel2 = $pdo->prepare("DELETE FROM comentarios WHERE id = ? AND id_usuario = ?");
                $stmtDel2->execute([$idCom, $userId]);
            }
            $_SESSION['exito'] = 'Comentario eliminado.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo eliminar: ' . $e->getMessage();
        }
    }
    header('Location: ' . $baseUrl . '/mis-comentarios');
    exit;
}

// ── Comentarios ───────────────────────────────────────────────────────
$comentarios = [];
try {
    // Intento 1: Nombres estándar de PKs
    $stmtCom = $pdo->prepare("
        SELECT c.*, r.titulo AS receta_titulo, r.imagen
        FROM comentarios c
        LEFT JOIN recetas r ON r.id_receta = c.id_receta
        WHERE c.id_usuario = ?
        ORDER BY c.created_at DESC
    ");
    $stmtCom->execute([$userId]);
    $rows = $stmtCom->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $comentarios[] = [
            'id' => $row['id_comentario'] ?? $row['id'] ?? 0,
            'contenido' => $row['contenido'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'id_receta' => $row['id_receta'] ?? 0,
            'receta_titulo' => $row['receta_titulo'] ?? 'Sin título',
            'imagen' => $row['imagen'] ?? null
        ];
    }
} catch (Throwable $e) {
    // Intento 2: PK = 'id'
    try {
        $stmtCom = $pdo->prepare("
            SELECT c.*, r.titulo AS receta_titulo, r.imagen
            FROM comentarios c
            LEFT JOIN recetas r ON r.id = c.id_receta
            WHERE c.id_usuario = ?
            ORDER BY c.created_at DESC
        ");
        $stmtCom->execute([$userId]);
        $rows = $stmtCom->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $comentarios[] = [
                'id' => $row['id'] ?? 0,
                'contenido' => $row['contenido'] ?? '',
                'created_at' => $row['created_at'] ?? '',
                'id_receta' => $row['id_receta'] ?? 0,
                'receta_titulo' => $row['receta_titulo'] ?? 'Sin título',
                'imagen' => $row['imagen'] ?? null
            ];
        }
    } catch (Throwable $e2) {
        // Sin comentarios
    }
}

// ── Valoraciones ──────────────────────────────────────────────────────
$valoraciones = [];
try {
    $stmtVal = $pdo->prepare("
        SELECT v.*, r.titulo AS receta_titulo, r.imagen
        FROM valoraciones v
        LEFT JOIN recetas r ON r.id_receta = v.id_receta
        WHERE v.id_usuario = ?
        ORDER BY v.created_at DESC
    ");
    $stmtVal->execute([$userId]);
    $rows = $stmtVal->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $valoraciones[] = [
            'id' => $row['id_valoracion'] ?? $row['id'] ?? 0,
            'puntuacion' => $row['puntuacion'] ?? 0,
            'comentario' => $row['comentario'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'id_receta' => $row['id_receta'] ?? 0,
            'receta_titulo' => $row['receta_titulo'] ?? 'Sin título',
            'imagen' => $row['imagen'] ?? null
        ];
    }
} catch (Throwable $e) {
    try {
        $stmtVal = $pdo->prepare("
            SELECT v.*, r.titulo AS receta_titulo, r.imagen
            FROM valoraciones v
            LEFT JOIN recetas r ON r.id = v.id_receta
            WHERE v.id_usuario = ?
            ORDER BY v.created_at DESC
        ");
        $stmtVal->execute([$userId]);
        $rows = $stmtVal->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $valoraciones[] = [
                'id' => $row['id'] ?? 0,
                'puntuacion' => $row['puntuacion'] ?? 0,
                'comentario' => $row['comentario'] ?? '',
                'created_at' => $row['created_at'] ?? '',
                'id_receta' => $row['id_receta'] ?? 0,
                'receta_titulo' => $row['receta_titulo'] ?? 'Sin título',
                'imagen' => $row['imagen'] ?? null
            ];
        }
    } catch (Throwable $e2) {
        // Sin valoraciones
    }
}

// Tab inicial: desde ?tab= en la URL
$tabInicial = ($_GET['tab'] ?? 'comentarios') === 'valoraciones' ? 'valoraciones' : 'comentarios';

$exito = $_SESSION['exito'] ?? null;
unset($_SESSION['exito']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <?php if ($exito): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ENCABEZADO -->
    <div class="d-flex align-items-center gap-3 mb-5">
        <div class="interact-header-icon">
            <i class="bi bi-chat-left-text"></i>
        </div>
        <div>
            <h1 class="fw-800 mb-0" style="font-size:1.5rem">Mis Interacciones</h1>
            <p class="text-muted mb-0 small">
                <strong><?= count($comentarios) ?></strong> comentario<?= count($comentarios)!=1?'s':'' ?> ·
                <strong><?= count($valoraciones) ?></strong> valoraci<?= count($valoraciones)!=1?'ones':'ón' ?>
            </p>
        </div>
    </div>

    <!-- TABS MANUALES (sin data-bs-toggle para evitar conflictos) -->
    <div class="interact-tabs mb-4">
        <button class="interact-tab-btn" id="tabBtnComentarios"
                onclick="switchTab('comentarios')">
            <i class="bi bi-chat-dots"></i>
            Comentarios
            <?php if (count($comentarios)): ?>
            <span class="interact-tab-count"><?= count($comentarios) ?></span>
            <?php endif; ?>
        </button>
        <button class="interact-tab-btn" id="tabBtnValoraciones"
                onclick="switchTab('valoraciones')">
            <img src="<?= $icoAnim ?>stars.gif" alt="★" style="width:18px;height:18px">
            Valoraciones
            <?php if (count($valoraciones)): ?>
            <span class="interact-tab-count"><?= count($valoraciones) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- PANEL COMENTARIOS -->
    <div id="panelComentarios" class="interact-panel">
        <?php if (empty($comentarios)): ?>
        <div class="empty-state">
            <i class="bi bi-chat-dots"></i>
            <h5>Aún no has comentado ninguna receta</h5>
            <p>Explora el catálogo, prueba una receta y deja tu opinión.</p>
            <a href="<?= $baseUrl ?>/recetas" class="btn btn-primary mt-2">
                <i class="bi bi-search me-1"></i>Explorar recetas
            </a>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($comentarios as $c): ?>
            <div class="col-md-6">
                <div class="interact-card">
                    <div class="interact-card-head">
                        <a href="<?= $baseUrl ?>/recetas?id=<?= (int)$c['id_receta'] ?>"
                           class="interact-card-title">
                            <i class="bi bi-journal-richtext"></i>
                            <?= htmlspecialchars($c['receta_titulo']) ?>
                        </a>
                        <span class="interact-card-date">
                            <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                        </span>
                    </div>
                    <p class="interact-card-body">
                        "<?= htmlspecialchars($c['contenido']) ?>"
                    </p>
                    <form method="POST" onsubmit="return confirm('¿Eliminar este comentario?')" class="mt-2">
                        <?php csrfField(); ?>
                        <input type="hidden" name="id_comentario" value="<?= (int)$c['id'] ?>">
                        <button type="submit" name="eliminar_comentario"
                                class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- PANEL VALORACIONES -->
    <div id="panelValoraciones" class="interact-panel" style="display:none">
        <?php if (empty($valoraciones)): ?>
        <div class="empty-state">
            <img src="<?= $icoAnim ?>stars.gif" alt="★"
                 style="width:80px;height:80px;opacity:.5;display:block;margin:0 auto 1rem">
            <h5>Aún no has valorado ninguna receta</h5>
            <p>Prueba una receta y dale estrellas para ayudar a la comunidad.</p>
            <a href="<?= $baseUrl ?>/recetas" class="btn btn-primary mt-2">
                <i class="bi bi-search me-1"></i>Explorar recetas
            </a>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($valoraciones as $v): ?>
            <div class="col-md-6">
                <div class="interact-card">
                    <div class="interact-card-head">
                        <a href="<?= $baseUrl ?>/recetas?id=<?= (int)$v['id_receta'] ?>"
                           class="interact-card-title">
                            <i class="bi bi-journal-richtext"></i>
                            <?= htmlspecialchars($v['receta_titulo']) ?>
                        </a>
                        <!-- Estrellas con gif -->
                        <div class="interact-stars">
                            <img src="<?= $icoAnim ?>stars.gif" alt="★"
                                 style="width:18px;height:18px">
                            <span style="color:var(--gold);font-weight:700;font-size:.82rem">
                                <?= $v['puntuacion'] ?>/5
                            </span>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="bi bi-star<?= $s <= (int)$v['puntuacion'] ? '-fill' : '' ?>"
                               style="color:var(--gold);font-size:.8rem"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if (!empty($v['comentario'])): ?>
                    <p class="interact-card-body">
                        "<?= htmlspecialchars($v['comentario']) ?>"
                    </p>
                    <?php endif; ?>
                    <span class="interact-card-date d-block mt-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= date('d/m/Y', strtotime($v['created_at'])) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->
</main>

<style>
.interact-header-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--gold), var(--orange));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
    box-shadow: 0 4px 14px rgba(211,84,0,.3);
}

/* Tabs manuales */
.interact-tabs {
    display: flex; gap: .6rem; flex-wrap: wrap;
}
.interact-tab-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .6rem 1.4rem; border-radius: 30px;
    border: 1.5px solid var(--border);
    background: #fff; color: var(--text);
    font-size: .84rem; font-weight: 700; cursor: pointer;
    transition: all .2s; font-family: 'Nunito', sans-serif;
}
.interact-tab-btn:hover {
    border-color: var(--gold); color: var(--orange);
}
.interact-tab-btn.is-active {
    background: linear-gradient(135deg, var(--orange), var(--red));
    border-color: transparent; color: #fff;
}
.interact-tab-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 5px;
    border-radius: 10px; font-size: .65rem; font-weight: 800;
    background: rgba(255,255,255,.25); color: inherit;
}
.interact-tab-btn.is-active .interact-tab-count {
    background: rgba(255,255,255,.3); color: #fff;
}

/* Cards de interacciones */
.interact-card {
    background: #fff; border-radius: 14px; padding: 1.2rem 1.25rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    border: 1px solid var(--border); height: 100%;
    transition: box-shadow .2s, transform .2s;
}
.interact-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }

.interact-card-head {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: .75rem; margin-bottom: .85rem;
}
.interact-card-title {
    font-weight: 700; font-size: .88rem; color: var(--orange);
    text-decoration: none; display: flex; align-items: center; gap: .4rem;
    flex: 1; min-width: 0;
}
.interact-card-title:hover { color: var(--red); }
.interact-card-title i { flex-shrink: 0; }

.interact-card-date {
    font-size: .71rem; color: var(--text-m);
    white-space: nowrap; flex-shrink: 0;
}
.interact-card-body {
    font-size: .85rem; color: var(--text-m);
    line-height: 1.65; margin: 0;
    font-style: italic;
}

.interact-stars {
    display: flex; align-items: center; gap: .3rem; flex-shrink: 0;
}

/* Dark mode */
[data-theme="dark"] .interact-card,
.dark .interact-card { background: #1e1408; border-color: rgba(232,168,56,.1); }
[data-theme="dark"] .interact-tab-btn:not(.is-active),
.dark .interact-tab-btn:not(.is-active) {
    background: #1e1408; border-color: rgba(232,168,56,.15); color: #e0d0bc;
}
</style>

<script>
// Tab inicial desde PHP
var tabInicial = <?= json_encode($tabInicial) ?>;

function switchTab(tab) {
    // Ocultar todos los paneles
    document.getElementById('panelComentarios').style.display  = 'none';
    document.getElementById('panelValoraciones').style.display = 'none';

    // Quitar is-active de todos los botones
    document.querySelectorAll('.interact-tab-btn').forEach(b => b.classList.remove('is-active'));

    // Mostrar panel seleccionado
    document.getElementById(tab === 'valoraciones' ? 'panelValoraciones' : 'panelComentarios').style.display = '';

    // Activar botón correcto
    document.getElementById(tab === 'valoraciones' ? 'tabBtnValoraciones' : 'tabBtnComentarios')
            .classList.add('is-active');

    // Actualizar URL sin recargar
    const url = new URL(window.location);
    if (tab === 'comentarios') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }
    history.replaceState(null, '', url);
}

// Activar tab inicial al cargar
document.addEventListener('DOMContentLoaded', () => switchTab(tabInicial));
</script>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
