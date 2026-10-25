<?php
/**
 * UTILIDAD TEMPORAL — Re-hashear contraseñas de cuentas existentes
 *
 * USO:
 *   1. Abre este archivo en el navegador: http://localhost/GastroDigital1102/rehash_passwords.php
 *   2. Completa el formulario con el email y la nueva contraseña para cada cuenta
 *   3. Haz clic en "Actualizar contraseñas"
 *   4. ELIMINA este archivo inmediatamente después de usarlo
 *
 * SEGURIDAD: Este archivo NO tiene autenticación, SOLO úsalo localmente.
 */

// Solo accesible desde localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

require_once __DIR__ . '/config/conexion.php';

$pdo       = Conexion::getInstancia()->getPDO();
$mensajes  = [];
$usuarios  = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY rol, nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['actualizaciones'])) {
    foreach ($_POST['actualizaciones'] as $id => $datos) {
        $id       = (int)$id;
        $password = trim($datos['password'] ?? '');

        if ($password === '') continue; // saltar si no se ingresó contraseña

        if (strlen($password) < 8) {
            $mensajes[] = ['tipo' => 'error', 'texto' => "ID $id: contraseña demasiado corta (mínimo 8 caracteres)."];
            continue;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);

        // Buscar nombre para el mensaje
        $nombre = '';
        foreach ($usuarios as $u) {
            if ((int)$u['id'] === $id) { $nombre = $u['nombre']; break; }
        }
        $mensajes[] = ['tipo' => 'exito', 'texto' => "✓ Contraseña actualizada para <strong>" . htmlspecialchars($nombre) . "</strong> (ID: $id)"];
    }

    // Recargar la lista actualizada
    $usuarios = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY rol, nombre")->fetchAll();
}

$rolColor = ['admin' => '#6c3483', 'chef' => '#3b2a1a', 'usuario' => '#c0392b'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Re-hashear contraseñas — GastroDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5ede0; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .badge-rol { padding: 3px 10px; border-radius: 20px; font-size: .75rem; color: #fff; }
    </style>
</head>
<body class="py-5">
<div class="container" style="max-width:720px">

    <div class="text-center mb-4">
        <h2 class="fw-bold">🔑 Re-hashear contraseñas</h2>
        <p class="text-muted">
            Escribe la nueva contraseña para cada cuenta que quieras restablecer.<br>
            <strong>Deja el campo vacío</strong> para no cambiar esa contraseña.
        </p>
        <div class="alert alert-warning d-inline-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Elimina este archivo cuando termines: <code>rehash_passwords.php</code></span>
        </div>
    </div>

    <!-- Mensajes de resultado -->
    <?php foreach ($mensajes as $m): ?>
    <div class="alert alert-<?= $m['tipo'] === 'exito' ? 'success' : 'danger' ?> py-2">
        <?= $m['texto'] ?>
    </div>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST">
                <table class="table align-middle mb-4">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Nueva contraseña</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge-rol"
                                      style="background:<?= $rolColor[$u['rol']] ?? '#999' ?>">
                                    <?= ucfirst($u['rol']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="password"
                                           class="form-control"
                                           name="actualizaciones[<?= $u['id'] ?>][password]"
                                           placeholder="Nueva contraseña (mín. 8 car.)"
                                           autocomplete="new-password">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-toggle-pass"
                                            tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Solo se actualizan los campos que tengan contraseña escrita.
                    </small>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-shield-lock me-2"></i>Actualizar contraseñas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="http://localhost/GastroDigital1102" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al inicio
        </a>
    </div>

</div>
<script>
    // Toggle mostrar/ocultar contraseña en cada fila
    document.querySelectorAll('.btn-toggle-pass').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const icon  = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
</script>
</body>
</html>
