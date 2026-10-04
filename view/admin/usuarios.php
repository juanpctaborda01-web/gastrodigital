<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/usuarios.php';
requireRole(ROL_ADMIN);

$pageTitle = 'Gestión de Usuarios';
$model     = new Usuario();
$usuarios  = $model->listarTodos();

$exito = $_SESSION['exito'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['exito'], $_SESSION['error']);
?>
<?php include __DIR__ . '/../../assets/header.php'; ?>
<?php include __DIR__ . '/../../assets/navbar.php'; ?>

<main class="py-5">
<div class="container">

    <div class="mb-4">
        <h2 class="fw-800 mb-0">Gestión de Usuarios</h2>
        <p class="text-muted"><?= count($usuarios) ?> usuarios registrados</p>
    </div>

    <?php if ($exito): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="table-custom">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol actual</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700"
                                 style="width:36px;height:36px;background:var(--primary);flex-shrink:0;font-size:.85rem">
                                <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                            </div>
                            <span class="fw-600"><?= htmlspecialchars($u['nombre']) ?></span>
                        </div>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge-rol badge-<?= $u['rol'] ?>"><?= ucfirst($u['rol']) ?></span></td>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <!-- Cambiar rol -->
                            <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=cambiarRol"
                                  class="d-flex gap-1">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <?php csrfField(); ?>
                                <select name="rol" class="form-select form-select-sm" style="width:auto">
                                    <?php foreach (['usuario','chef','admin'] as $rol): ?>
                                    <option value="<?= $rol ?>" <?= $u['rol'] === $rol ? 'selected' : '' ?>>
                                        <?= ucfirst($rol) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-outline-primary btn-sm" title="Guardar rol">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <!-- Eliminar (no puede eliminarse a sí mismo) -->
                            <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=eliminar"
                                  onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>?')">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <?php csrfField(); ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</main>

<?php include __DIR__ . '/../../assets/fooder.php'; ?>
