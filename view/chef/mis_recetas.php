<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/receta.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireRole(ROL_CHEF);

$pageTitle = 'Mis Recetas';
$model     = new Receta();
$userModel = new Usuario();
$nombre    = $_SESSION['usuario_nombre'] ?? 'Chef';
$userId    = $_SESSION['usuario_id'];
$perfil    = $userModel->buscarPorId($userId) ?: [];
$perfilNombre = $perfil['nombre'] ?? $nombre;
$perfilAvatar = $perfil['avatar'] ?? '';
$perfilBio = $perfil['bio'] ?? '';
$perfilRedes = $perfil['redes_sociales'] ?? '';
$perfilEspecialidad = $perfil['especialidad'] ?? '';
$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

// Datos enriquecidos (incluye visitas y comentarios por receta)
$recetas    = $model->porChefConVisitas($userId);
$comentarios = $model->comentariosPorChef($userId);

// Stats del chef
$totalRecetas      = count($recetas);
$totalFavoritos    = array_sum(array_column($recetas, 'total_favoritos'));
$totalVisitas      = $model->visitasPorChef($userId);
$totalComentarios  = $model->totalComentariosPorChef($userId);
$promedioRating    = $totalRecetas
    ? round(array_sum(array_filter(array_column($recetas, 'rating'))) /
            max(1, count(array_filter(array_column($recetas, 'rating')))), 1)
    : 0;

$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);

$difColor = ['Fácil' => 'success', 'Media' => 'warning', 'Difícil' => 'danger'];
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-4">
<div class="container">

    <!-- ══ BANNER CHEF ══════════════════════════════════════════════════════ -->
    <div class="p-4 rounded-4 text-white mb-4"
         style="background:linear-gradient(135deg,var(--dark),var(--dark-2))">
        <div class="row align-items-center">
            <div class="col-md-8">
                <small class="opacity-75 fw-600 text-uppercase">
                    <i class="bi bi-journal-richtext me-1"></i>Panel del chef
                </small>
                <h2 class="fw-800 mb-1 mt-1">
                    <?= $saludo ?>, <?= htmlspecialchars($nombre) ?> 👨‍🍳
                </h2>
                <p class="mb-0 opacity-75">
                    <?= $totalRecetas ?> receta<?= $totalRecetas !== 1 ? 's' : '' ?> ·
                    <?= number_format($totalVisitas) ?> visita<?= $totalVisitas !== 1 ? 's' : '' ?> ·
                    <?= $totalComentarios ?> comentario<?= $totalComentarios !== 1 ? 's' : '' ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/crear-receta"
                   class="btn btn-primary fw-700">
                    <i class="bi bi-plus-circle me-2"></i>Nueva receta
                </a>
            </div>
        </div>
    </div>

    <!-- ══ STATS DEL CHEF (5 tarjetas) ═════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="stat-card"
                 style="background:linear-gradient(135deg,var(--primary),var(--primary-light))">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $totalRecetas ?></div>
                        <div class="stat-label">Mis recetas</div>
                    </div>
                    <i class="bi bi-journal-richtext stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="background:linear-gradient(135deg,var(--accent),#4a7a28)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= number_format($totalVisitas) ?></div>
                        <div class="stat-label">Visitas totales</div>
                    </div>
                    <i class="bi bi-eye stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card"
                 style="background:linear-gradient(135deg,#c0392b,#e74c3c)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $totalFavoritos ?></div>
                        <div class="stat-label">Total favoritos</div>
                    </div>
                    <i class="bi bi-heart stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card" style="background:linear-gradient(135deg,#8e44ad,#9b59b6)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $totalComentarios ?></div>
                        <div class="stat-label">Comentarios</div>
                    </div>
                    <i class="bi bi-chat-dots stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card"
                 style="background:linear-gradient(135deg,var(--secondary),#c8891a)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?= $promedioRating ?: '—' ?></div>
                        <div class="stat-label">Rating promedio</div>
                    </div>
                    <i class="bi bi-star stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($exito): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ══ LAYOUT: Tabla recetas + Comentarios recientes ════════════════════ -->
    <div class="row g-4">

        <!-- Tabla de recetas -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-800 mb-0">
                    <i class="bi bi-journal-richtext me-2" style="color:var(--primary)"></i>Mis recetas
                </h5>
                <a href="<?= BASE_URL ?>/index.php?route=recetas"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye me-1"></i>Ver catálogo público
                </a>
            </div>

            <?php if (empty($recetas)): ?>
            <div class="empty-state">
                <i class="bi bi-journal-plus"></i>
                <h5>Todavía no tienes recetas</h5>
                <p>Comparte tu primera creación con la comunidad.</p>
                <a href="<?= BASE_URL ?>/crear-receta" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-2"></i>Crear primera receta
                </a>
            </div>
            <?php else: ?>
            <div class="table-custom">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Receta</th>
                            <th>Categoría</th>
                            <th>Dif.</th>
                            <th><i class="bi bi-eye" title="Visitas"></i></th>
                            <th><i class="bi bi-star-fill text-warning" title="Rating"></i></th>
                            <th><i class="bi bi-heart-fill text-danger" title="Favoritos"></i></th>
                            <th><i class="bi bi-chat-dots text-muted" title="Comentarios"></i></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recetas as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-700 small"><?= htmlspecialchars($r['titulo']) ?></div>
                                <?php if ($r['destacada']): ?>
                                <span class="badge" style="background:var(--secondary);color:#3b2a1a;font-size:.65rem">
                                    <i class="bi bi-star-fill me-1"></i>Destacada
                                </span>
                                <?php endif; ?>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-clock me-1"></i><?= $r['tiempo_preparacion'] ?> min
                                </div>
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
                            <td class="fw-700 small"><?= number_format($r['visitas']) ?></td>
                            <td class="small">
                                <?php if ($r['rating']): ?>
                                    <?= $r['rating'] ?>
                                    <small class="text-muted">(<?= $r['total_valoraciones'] ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= $r['total_favoritos'] ?></td>
                            <td class="small text-muted"><?= $r['total_comentarios'] ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $r['id'] ?>"
                                       class="btn btn-outline-primary btn-sm" title="Ver receta">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/editar-receta?id=<?= $r['id'] ?>"
                                       class="btn btn-outline-secondary btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="<?= BASE_URL ?>/controllers/RecetaController.php?accion=eliminar"
                                          onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars($r['titulo'], ENT_QUOTES) ?>»?')">
                                        <input type="hidden" name="receta_id" value="<?= $r['id'] ?>">
                                        <?php csrfField(); ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Perfil del chef + comentarios -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4" id="perfil">
                <div class="card-body text-center pt-4 pb-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-800 mb-3"
                         style="width:78px;height:78px;background:linear-gradient(135deg,var(--primary),var(--secondary));font-size:1.8rem">
                        <?= strtoupper(substr($nombre, 0, 1)) ?>
                    </div>
                    <h5 class="fw-800 mb-1"><?= htmlspecialchars($nombre) ?></h5>
                    <p class="text-muted small mb-2">Chef · Especialista en cocina creativa</p>
                    <span class="badge rounded-pill px-3 py-1 mb-3" style="background:var(--secondary);color:#3b2a1a;font-size:.75rem">
                        <i class="bi bi-star-fill me-1"></i>Chef destacado
                    </span>

                    <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardarPerfil" class="border rounded-3 p-3 text-start mb-3" style="background:var(--surface-2)" enctype="multipart/form-data">
                        <?php csrfField(); ?>
                        <input type="hidden" name="usuario_id" value="<?= (int)$userId ?>">
                        <div class="fw-700 mb-2"><i class="bi bi-person-badge me-2" style="color:var(--primary)"></i>Perfil profesional</div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Nombre</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilNombre) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Foto</label>
                            <input type="text" name="avatar" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilAvatar) ?>" placeholder="URL de foto o déjalo vacío para usar la subida">
                            <input type="file" name="avatar_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Biografía</label>
                            <textarea name="bio" class="form-control form-control-sm" rows="3" placeholder="Describe tu estilo culinario..."><?= htmlspecialchars($perfilBio) ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Especialidad</label>
                            <input type="text" name="especialidad" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilEspecialidad) ?>" placeholder="Ej. Cocina mexicana / Postres">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-600">Redes sociales</label>
                            <input type="text" name="redes_sociales" class="form-control form-control-sm" value="<?= htmlspecialchars($perfilRedes) ?>" placeholder="Instagram / TikTok">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                            <i class="bi bi-save me-1"></i>Guardar perfil
                        </button>
                    </form>

                    <div class="row g-0 text-center mb-3">
                        <div class="col-4 border-end py-2">
                            <div class="fw-800 fs-5" style="color:var(--primary)"><?= $totalRecetas ?></div>
                            <div class="text-muted" style="font-size:.72rem">Recetas</div>
                        </div>
                        <div class="col-4 border-end py-2">
                            <div class="fw-800 fs-5" style="color:var(--accent)"><?= $totalFavoritos ?></div>
                            <div class="text-muted" style="font-size:.72rem">Favoritos</div>
                        </div>
                        <div class="col-4 py-2">
                            <div class="fw-800 fs-5" style="color:var(--secondary)"><?= $promedioRating ?: '—' ?></div>
                            <div class="text-muted" style="font-size:.72rem">Rating</div>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="fw-800 mb-3">
                <i class="bi bi-chat-dots me-2" style="color:#8e44ad"></i>Comentarios recientes
            </h5>

            <?php if (empty($comentarios)): ?>
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-chat-dots fs-1 text-muted opacity-50 d-block mb-2"></i>
                    <p class="text-muted small mb-0">Aún no hay comentarios en tus recetas.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach (array_slice($comentarios, 0, 8) as $c): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3 px-3">
                        <!-- Cabecera: autor + fecha -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center
                                         text-white fw-700 flex-shrink-0"
                                  style="width:30px;height:30px;background:var(--primary);font-size:.75rem">
                                <?= strtoupper(substr($c['autor'], 0, 1)) ?>
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="fw-700 small"><?= htmlspecialchars($c['autor']) ?></span>
                                <small class="text-muted d-block" style="font-size:.7rem">
                                    <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                        <!-- Comentario -->
                        <p class="mb-1 small" style="line-height:1.5">
                            <?= htmlspecialchars(mb_substr($c['contenido'], 0, 120)) ?>
                            <?= mb_strlen($c['contenido']) > 120 ? '…' : '' ?>
                        </p>
                        <!-- Receta referenciada -->
                        <a href="<?= BASE_URL ?>/index.php?route=recetas&id=<?= $c['receta_id'] ?>"
                           class="d-flex align-items-center gap-1 mt-1"
                           style="font-size:.72rem;color:var(--primary)">
                            <i class="bi bi-book"></i>
                            <?= htmlspecialchars($c['receta_titulo']) ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($totalComentarios > 8): ?>
                <p class="text-muted small text-center mt-1">
                    Y <?= $totalComentarios - 8 ?> comentario<?= $totalComentarios - 8 !== 1 ? 's' : '' ?> más…
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row -->

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
