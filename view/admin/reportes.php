<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/categoria.php';
requireRole(ROL_ADMIN);

$pageTitle  = 'Reportes';
$model      = new Receta();
$catModel   = new Categoria();
$categorias = $catModel->listarSimple();

// ── Tipo de reporte ────────────────────────────────────────────────────────────
$tipo = $_GET['tipo'] ?? 'recetas';   // 'recetas' | 'comentarios'

// ── Filtros comunes ────────────────────────────────────────────────────────────
$filtros = [
    'desde'      => trim($_GET['desde']      ?? ''),
    'hasta'      => trim($_GET['hasta']      ?? ''),
    'categoria'  => trim($_GET['categoria']  ?? ''),
    'dificultad' => trim($_GET['dificultad'] ?? ''),
    'destacada'  => $_GET['destacada'] ?? '',
];

// ── Exportar CSV ───────────────────────────────────────────────────────────────
if (isset($_GET['exportar'])) {
    if ($tipo === 'recetas') {
        $filas     = $model->reporte($filtros);
        $filename  = 'reporte_recetas_' . date('Ymd_His') . '.csv';
        $cabeceras = ['ID','Título','Categoría','Dificultad','Tiempo (min)','Visitas',
                      'Destacada','Rating','Valoraciones','Comentarios','Favoritos','Chef','Fecha'];
        $mapper = fn($r) => [
            $r['id'], $r['titulo'], $r['categoria'] ?? '', $r['dificultad'],
            $r['tiempo_preparacion'], $r['visitas'], $r['destacada'] ? 'Sí' : 'No',
            $r['rating'] ?? '', $r['total_valoraciones'], $r['total_comentarios'],
            $r['total_favoritos'], $r['chef_nombre'] ?? '',
            date('d/m/Y', strtotime($r['created_at'])),
        ];
    } else {
        $filas     = $model->reporteComentarios($filtros);
        $filename  = 'reporte_comentarios_' . date('Ymd_His') . '.csv';
        $cabeceras = ['ID','Comentario','Autor','Receta','Categoría','Chef','Fecha'];
        $mapper = fn($r) => [
            $r['id'], $r['contenido'], $r['autor'] ?? '',
            $r['receta_titulo'] ?? '', $r['categoria'] ?? '',
            $r['chef_nombre'] ?? '',
            date('d/m/Y H:i', strtotime($r['created_at'])),
        ];
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    // BOM para que Excel muestre tildes correctamente
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, $cabeceras);
    foreach ($filas as $fila) {
        fputcsv($out, $mapper($fila));
    }
    fclose($out);
    exit();
}

// ── Ejecutar reporte sólo si hay filtros o se pidió explícitamente ─────────────
$ejecutar  = isset($_GET['buscar']) || isset($_GET['tipo']);
$resultados = [];
$resumen    = [];
$datosGraficas = [
    'categorias' => [],
    'dificultades' => [],
    'rendimiento' => [],
];

if ($ejecutar) {
    if ($tipo === 'recetas') {
        $resultados = $model->reporte($filtros);
        if ($resultados) {
            $resumen = [
                'total'            => count($resultados),
                'visitas'          => array_sum(array_column($resultados, 'visitas')),
                'comentarios'      => array_sum(array_column($resultados, 'total_comentarios')),
                'favoritos'        => array_sum(array_column($resultados, 'total_favoritos')),
                'rating_prom'      => round(array_sum(array_filter(array_column($resultados, 'rating')))
                                      / max(1, count(array_filter(array_column($resultados, 'rating')))), 1),
            ];
            foreach ($resultados as $fila) {
                $categoria = $fila['categoria'] ?: 'Sin categoría';
                $dificultad = $fila['dificultad'] ?: 'Sin dificultad';
                $datosGraficas['categorias'][$categoria] = ($datosGraficas['categorias'][$categoria] ?? 0) + 1;
                $datosGraficas['dificultades'][$dificultad] = ($datosGraficas['dificultades'][$dificultad] ?? 0) + 1;
                $datosGraficas['rendimiento'][] = [
                    'titulo' => $fila['titulo'],
                    'categoria' => $categoria,
                    'visitas' => (int)$fila['visitas'],
                    'favoritos' => (int)$fila['total_favoritos'],
                    'comentarios' => (int)$fila['total_comentarios'],
                ];
            }
            arsort($datosGraficas['categorias']);
            arsort($datosGraficas['dificultades']);
            usort($datosGraficas['rendimiento'], fn($a, $b) => $b['visitas'] <=> $a['visitas']);
            $datosGraficas['rendimiento'] = array_slice($datosGraficas['rendimiento'], 0, 8);
        }
    } else {
        $resultados = $model->reporteComentarios($filtros);
        $resumen    = ['total' => count($resultados)];
    }
}
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-4">
<div class="container">

    <!-- ══ CABECERA ══════════════════════════════════════════════════════════ -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <a href="<?= BASE_URL ?>/admin" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Volver al panel
            </a>
            <h2 class="fw-800 mt-1 mb-0">
                <i class="bi bi-file-earmark-bar-graph me-2" style="color:#8e44ad"></i>Reportes
            </h2>
            <p class="text-muted small mb-0">Filtra, analiza y exporta datos de la plataforma</p>
        </div>

        <!-- Botón exportar CSV (sólo si hay resultados) -->
        <?php if ($ejecutar && !empty($resultados)): ?>
        <?php
        $csvParams = http_build_query(array_merge($_GET, ['exportar' => '1']));
        ?>
        <a href="<?= BASE_URL ?>/view/admin/reportes.php?<?= $csvParams ?>"
           class="btn btn-success fw-700 d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-arrow-down"></i>
            Exportar CSV (<?= count($resultados) ?> filas)
        </a>
        <?php endif; ?>
    </div>

    <!-- ══ PESTAÑAS DE TIPO ═══════════════════════════════════════════════════ -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link fw-700 <?= $tipo === 'recetas' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/view/admin/reportes.php?tipo=recetas">
                <i class="bi bi-book me-1"></i>Recetas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-700 <?= $tipo === 'comentarios' ? 'active' : '' ?>"
               href="<?= BASE_URL ?>/view/admin/reportes.php?tipo=comentarios">
                <i class="bi bi-chat-dots me-1"></i>Comentarios
            </a>
        </li>
    </ul>

    <!-- ══ FORMULARIO DE FILTROS ═════════════════════════════════════════════ -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tipo"   value="<?= htmlspecialchars($tipo) ?>">
                <input type="hidden" name="buscar" value="1">

                <!-- Rango de fechas -->
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small fw-600">Desde</label>
                    <input type="date" class="form-control form-control-sm"
                           name="desde" value="<?= htmlspecialchars($filtros['desde']) ?>">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small fw-600">Hasta</label>
                    <input type="date" class="form-control form-control-sm"
                           name="hasta" value="<?= htmlspecialchars($filtros['hasta']) ?>">
                </div>

                <!-- Categoría -->
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-600">Categoría</label>
                    <select class="form-select form-select-sm" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['nombre']) ?>"
                            <?= $filtros['categoria'] === $cat['nombre'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($tipo === 'recetas'): ?>
                <!-- Dificultad (solo recetas) -->
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small fw-600">Dificultad</label>
                    <select class="form-select form-select-sm" name="dificultad">
                        <option value="">Cualquiera</option>
                        <?php foreach (['Fácil','Media','Difícil'] as $d): ?>
                        <option value="<?= $d ?>" <?= $filtros['dificultad'] === $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Destacada (solo recetas) -->
                <div class="col-sm-6 col-lg-1">
                    <label class="form-label small fw-600">Destacada</label>
                    <select class="form-select form-select-sm" name="destacada">
                        <option value="">Todas</option>
                        <option value="1" <?= $filtros['destacada'] === '1' ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= $filtros['destacada'] === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2 d-flex gap-2">
                <?php else: ?>
                <div class="col-sm-6 col-lg-5 d-flex gap-2 align-items-end">
                <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/view/admin/reportes.php?tipo=<?= $tipo ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </form>
        </div>
    </div>

    <?php if (!$ejecutar): ?>
    <!-- Estado inicial -->
    <div class="empty-state">
        <i class="bi bi-funnel" style="color:var(--secondary)"></i>
        <h5>Aplica filtros para generar el reporte</h5>
        <p>Selecciona un rango de fechas, categoría u otras opciones y haz clic en <strong>Filtrar</strong>.</p>
    </div>

    <?php elseif (empty($resultados)): ?>
    <!-- Sin resultados -->
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <h5>Sin resultados</h5>
        <p>No hay <?= $tipo ?> que coincidan con los filtros aplicados.</p>
    </div>

    <?php else: ?>

    <!-- ══ RESUMEN NUMÉRICO ═══════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <?php if ($tipo === 'recetas'): ?>
        <div class="col-6 col-lg-2">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:var(--primary)"><?= $resumen['total'] ?></div>
                <div class="text-muted small">Recetas</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:var(--accent)"><?= number_format($resumen['visitas']) ?></div>
                <div class="text-muted small">Visitas</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:#8e44ad"><?= $resumen['comentarios'] ?></div>
                <div class="text-muted small">Comentarios</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:#c0392b"><?= $resumen['favoritos'] ?></div>
                <div class="text-muted small">Favoritos</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:var(--secondary)"><?= $resumen['rating_prom'] ?: '—' ?></div>
                <div class="text-muted small">Rating prom.</div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-6 col-lg-3">
            <div class="card text-center py-3">
                <div class="fw-800 fs-4" style="color:#8e44ad"><?= $resumen['total'] ?></div>
                <div class="text-muted small">Comentarios encontrados</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ TABLA DE RESULTADOS ════════════════════════════════════════════════ -->
    <?php if ($tipo === 'recetas'): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="fw-800 mb-0">Recetas por categoría</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reiniciarGrafica">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Ver todo
                        </button>
                    </div>
                    <div style="height:260px"><canvas id="graficaCategorias" aria-label="Recetas por categoría"></canvas></div>
                    <div class="mt-3 p-3 rounded-3" style="background:var(--surface-3)" id="detalleCategoria">
                        <div class="text-muted small">Selecciona una porción para ver sus datos.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3">Dificultad</h5>
                    <div style="height:260px"><canvas id="graficaDificultad" aria-label="Recetas por dificultad"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="fw-800 mb-3">Visitas y favoritos</h5>
                    <div style="height:260px"><canvas id="graficaRendimiento" aria-label="Visitas y favoritos por receta"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-custom">
        <table class="table table-hover mb-0 align-middle" id="tablaReporte">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Dif.</th>
                    <th>Tiempo</th>
                    <th><i class="bi bi-eye" title="Visitas"></i></th>
                    <th><i class="bi bi-star-fill text-warning" title="Rating"></i></th>
                    <th><i class="bi bi-chat-dots text-muted" title="Comentarios"></i></th>
                    <th><i class="bi bi-heart-fill text-danger" title="Favoritos"></i></th>
                    <th>Chef</th>
                    <th>Fecha</th>
                    <th>★</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $difColor = ['Fácil' => 'success', 'Media' => 'warning', 'Difícil' => 'danger'];
                foreach ($resultados as $r):
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                           class="fw-700 small text-decoration-none">
                            <?= htmlspecialchars($r['titulo']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark" style="font-size:.7rem">
                            <?= htmlspecialchars($r['categoria'] ?? '—') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $difColor[$r['dificultad']] ?? 'secondary' ?>"
                              style="font-size:.7rem">
                            <?= $r['dificultad'] ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= $r['tiempo_preparacion'] ?> min</td>
                    <td class="fw-700 small"><?= number_format($r['visitas']) ?></td>
                    <td class="small"><?= $r['rating'] ?: '—' ?></td>
                    <td class="small text-muted"><?= $r['total_comentarios'] ?></td>
                    <td class="small text-muted"><?= $r['total_favoritos'] ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['chef_nombre'] ?? '—') ?></td>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <?php if ($r['destacada']): ?>
                        <i class="bi bi-star-fill" style="color:var(--secondary)" title="Destacada"></i>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: /* comentarios */ ?>

    <div class="table-custom">
        <table class="table table-hover mb-0 align-middle" id="tablaReporte">
            <thead>
                <tr>
                    <th>Comentario</th>
                    <th>Autor</th>
                    <th>Receta</th>
                    <th>Categoría</th>
                    <th>Chef</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $c): ?>
                <tr>
                    <td style="max-width:320px">
                        <span class="small">
                            <?= htmlspecialchars(mb_substr($c['contenido'], 0, 100)) ?>
                            <?= mb_strlen($c['contenido']) > 100 ? '…' : '' ?>
                        </span>
                    </td>
                    <td class="small fw-700"><?= htmlspecialchars($c['autor'] ?? '—') ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $c['receta_id'] ?>"
                           class="small fw-600 text-decoration-none">
                            <?= htmlspecialchars($c['receta_titulo'] ?? '—') ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark" style="font-size:.7rem">
                            <?= htmlspecialchars($c['categoria'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($c['chef_nombre'] ?? '—') ?></td>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

    <!-- Pie de tabla: total de filas + botón exportar repetido -->
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <small class="text-muted">
            Mostrando <strong><?= count($resultados) ?></strong>
            <?= $tipo === 'recetas' ? 'receta' : 'comentario' ?><?= count($resultados) !== 1 ? 's' : '' ?>
        </small>
        <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => '1'])) ?>"
           class="btn btn-success btn-sm fw-700">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Exportar CSV
        </a>
    </div>

    <?php endif; /* fin ejecutar */ ?>

</div>
</main>

<?php if ($ejecutar && $tipo === 'recetas' && !empty($resultados)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const datosGraficas = <?= json_encode($datosGraficas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const coloresGraficas = ['#c0392b', '#e8a838', '#6b8f3e', '#2980b9', '#8e44ad', '#d35400', '#1a6e8a', '#7d6608'];

const categoriaLabels = Object.keys(datosGraficas.categorias);
const categoriaValores = Object.values(datosGraficas.categorias);
const categoriaColores = categoriaLabels.map((_, index) => coloresGraficas[index % coloresGraficas.length]);
const totalCategorias = categoriaValores.reduce((total, valor) => total + valor, 0);
let categoriaSeleccionada = null;

const graficaCategorias = new Chart(document.getElementById('graficaCategorias'), {
    type: 'doughnut',
    data: {
        labels: categoriaLabels,
        datasets: [{ data: categoriaValores, backgroundColor: categoriaColores, borderColor: '#fff', borderWidth: 2, hoverOffset: 10 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (_, elementos) => {
            if (!elementos.length) return;
            categoriaSeleccionada = elementos[0].index;
            actualizarCategoriaSeleccionada();
        },
        plugins: { legend: { position: 'bottom' } }
    }
});

function actualizarCategoriaSeleccionada() {
    const dataset = graficaCategorias.data.datasets[0];
    dataset.backgroundColor = categoriaColores.map((color, index) => {
        if (categoriaSeleccionada === null || index === categoriaSeleccionada) return color;
        return color + '35';
    });
    graficaCategorias.update();

    if (categoriaSeleccionada === null) {
        document.getElementById('detalleCategoria').innerHTML = '<div class="text-muted small">Selecciona una porción para ver sus datos.</div>';
        return;
    }

    const nombre = categoriaLabels[categoriaSeleccionada];
    const cantidad = categoriaValores[categoriaSeleccionada];
    const porcentaje = Math.round((cantidad / totalCategorias) * 100);
    const filas = datosGraficas.rendimiento.filter(item => item.categoria === nombre);
    const visitas = filas.reduce((total, item) => total + item.visitas, 0);
    const favoritos = filas.reduce((total, item) => total + item.favoritos, 0);
    const comentarios = filas.reduce((total, item) => total + item.comentarios, 0);
    document.getElementById('detalleCategoria').innerHTML = categoriaSeleccionada === null
        ? '<div class="text-muted small">Selecciona una porción para ver sus datos.</div>'
        : `<strong>${nombre}</strong><div class="small text-muted mt-1">${cantidad} receta(s) · ${porcentaje}% del resultado</div><div class="small mt-2"><i class="bi bi-eye me-1"></i>${visitas} visitas · <i class="bi bi-heart-fill text-danger me-1"></i>${favoritos} favoritos · <i class="bi bi-chat-dots me-1"></i>${comentarios} comentarios</div>`;
}

document.getElementById('reiniciarGrafica').addEventListener('click', () => {
    categoriaSeleccionada = null;
    actualizarCategoriaSeleccionada();
});

new Chart(document.getElementById('graficaDificultad'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(datosGraficas.dificultades),
        datasets: [{ data: Object.values(datosGraficas.dificultades), backgroundColor: ['#5a9a3a', '#e8a838', '#c0392b'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('graficaRendimiento'), {
    type: 'bar',
    data: {
        labels: datosGraficas.rendimiento.map(item => item.titulo),
        datasets: [
            { label: 'Visitas', data: datosGraficas.rendimiento.map(item => item.visitas), backgroundColor: '#c0392b', borderRadius: 5 },
            { label: 'Favoritos', data: datosGraficas.rendimiento.map(item => item.favoritos), backgroundColor: '#e8a838', borderRadius: 5 }
        ]
    },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
