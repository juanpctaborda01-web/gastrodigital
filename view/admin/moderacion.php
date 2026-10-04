<?php
require_once __DIR__ . '/../../config/config.php';
requireRole(ROL_ADMIN);

$pageTitle = 'Moderación';
$pdo = Conexion::getInstancia()->getPDO();

// Comentarios recientes para moderar
$stmtCom = $pdo->query("
    SELECT c.id_comentario AS id, c.contenido, c.created_at, u.nombre as autor, r.titulo as receta_titulo
    FROM comentarios c
    JOIN usuarios u ON u.id_usuario = c.id_usuario
    JOIN recetas r ON r.id_receta = c.id_receta
    ORDER BY c.created_at DESC LIMIT 50
");
$comentarios = $stmtCom->fetchAll();

// Valoraciones recientes
$stmtVal = $pdo->query("
    SELECT v.id_valoracion AS id, v.puntuacion, v.comentario, v.created_at, u.nombre as autor, r.titulo as receta_titulo
    FROM valoraciones v
    JOIN usuarios u ON u.id_usuario = v.id_usuario
    JOIN recetas r ON r.id_receta = v.id_receta
    ORDER BY v.created_at DESC LIMIT 50
");
$valoraciones = $stmtVal->fetchAll();

// Características
$stmtCar = $pdo->query("SELECT id_caracteristica AS id, nombre, icono FROM caracteristicas ORDER BY nombre ASC");
$caracteristicas = $stmtCar->fetchAll();

$baseUrl = rtrim(BASE_URL, '/');
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-4" style="background:var(--surface-1); min-height:100vh;">
<div class="container">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-shield-lock text-primary fs-2 me-3"></i>
        <h2 class="fw-800 mb-0" style="color:var(--dark)">Moderación</h2>
    </div>

    <!-- Comentarios -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="comentarios">
        <div class="card-header bg-white p-4 border-bottom-0">
            <h4 class="fw-800 mb-0"><i class="bi bi-chat-dots me-2 text-primary"></i>Gestión de Comentarios</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Autor</th>
                            <th>Receta</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comentarios as $c): ?>
                        <tr>
                            <td><span class="fw-600"><?= htmlspecialchars($c['autor']) ?></span></td>
                            <td><span class="text-muted small"><?= htmlspecialchars($c['receta_titulo']) ?></span></td>
                            <td><div class="text-wrap" style="max-width:300px; font-size:0.85rem"><?= htmlspecialchars($c['contenido']) ?></div></td>
                            <td><span class="text-muted small"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Valoraciones -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="valoraciones">
        <div class="card-header bg-white p-4 border-bottom-0">
            <h4 class="fw-800 mb-0"><i class="bi bi-star me-2 text-warning"></i>Moderación de Valoraciones</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Autor</th>
                            <th>Receta</th>
                            <th>Rating</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valoraciones as $v): ?>
                        <tr>
                            <td><span class="fw-600"><?= htmlspecialchars($v['autor']) ?></span></td>
                            <td><span class="text-muted small"><?= htmlspecialchars($v['receta_titulo']) ?></span></td>
                            <td>
                                <div class="text-warning">
                                    <?php for($i=1; $i<=5; $i++) echo '<i class="bi bi-star'.($i<=$v['puntuacion']?'-fill':'').'"></i>'; ?>
                                </div>
                            </td>
                            <td><div class="text-wrap" style="max-width:300px; font-size:0.85rem"><?= htmlspecialchars($v['comentario']??'-') ?></div></td>
                            <td><span class="text-muted small"><?= date('d/m/Y', strtotime($v['created_at'])) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Características -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="caracteristicas">
        <div class="card-header bg-white p-4 border-bottom-0 d-flex justify-content-between align-items-center">
            <h4 class="fw-800 mb-0"><i class="bi bi-list-check me-2 text-success"></i>Características Culinarias</h4>
            <button class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Nueva Característica</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($caracteristicas as $car): ?>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center h-100">
                        <i class="bi <?= htmlspecialchars($car['icono']??'bi-tag') ?> fs-3 text-secondary mb-2 d-block"></i>
                        <h6 class="fw-700 mb-0"><?= htmlspecialchars($car['nombre']) ?></h6>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
