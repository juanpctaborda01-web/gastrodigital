<?php
/**
 * PageController — renderiza las vistas según la ruta solicitada.
 */
class PageController
{
    // ── Auth ──────────────────────────────────────────────────────────────────

    public function login(): void
    {
        require_once __DIR__ . '/../view/auth/login.php';
    }

    public function register(): void
    {
        $registroForm = $_SESSION['registro_form'] ?? [];
        unset($_SESSION['registro_form']);
        if (!empty($registroForm)) {
            $_POST = array_merge($_POST, $registroForm);
        }
        require_once __DIR__ . '/../view/auth/register.php';
    }

    // ── Usuario ───────────────────────────────────────────────────────────────

    public function dashboardUsuario(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/dashboard.php';
    }

    public function verRecetas(): void
    {
        require_once __DIR__ . '/../view/usuario/ver_recetas.php';
    }

    public function cocinarReceta(): void
    {
        require_once __DIR__ . '/../view/usuario/cook_mode.php';
    }

    public function favoritos(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/favoritos.php';
    }

    public function favorito(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Inicia sesión para guardar recetas favoritas.']);
            return;
        }

        $tokenPost = $_POST['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (!$tokenPost || !$tokenSesion || !hash_equals($tokenSesion, $tokenPost)) {
            http_response_code(403);
            echo json_encode(['error' => 'Petición no válida']);
            return;
        }

        $recetaId = (int)($_POST['receta_id'] ?? 0);
        if (!$recetaId) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }

        require_once __DIR__ . '/../models/receta.php';
        $resultado = (new Receta())->toggleFavorito((int)$_SESSION['usuario_id'], $recetaId);
        echo json_encode($resultado);
    }

    public function categorias(): void
    {
        require_once __DIR__ . '/../view/usuario/categorias.php';
    }

    public function paises(): void
    {
        require_once __DIR__ . '/../view/usuario/paises.php';
    }

    public function misComentarios(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/mis_comentarios.php';
    }

    // ── Común ─────────────────────────────────────────────────────────────────

    public function perfil(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/auth/perfil.php';
    }

    public function notificaciones(): void
    {
        requireLogin();
        require_once __DIR__ . '/../models/Notificacion.php';
        $modelo = new Notificacion();
        $notificaciones = $modelo->listarPorUsuario((int)$_SESSION['usuario_id']);
        $totalNoLeidas = $modelo->contarNoLeidas((int)$_SESSION['usuario_id']);
        require_once __DIR__ . '/../view/usuario/notificaciones.php';
    }

    public function marcarNotificacionLeida(): void
    {
        requireLogin();
        verificarCsrf();

        $notificacionId = (int)($_POST['notificacion_id'] ?? 0);
        if ($notificacionId > 0) {
            require_once __DIR__ . '/../models/Notificacion.php';
            (new Notificacion())->marcarLeida($notificacionId, (int)$_SESSION['usuario_id']);
        }

        redirect('notificaciones');
    }

    public function marcarTodasNotificacionesLeidas(): void
    {
        requireLogin();
        verificarCsrf();
        require_once __DIR__ . '/../models/Notificacion.php';
        (new Notificacion())->marcarTodasLeidas((int)$_SESSION['usuario_id']);
        redirect('notificaciones');
    }

    // ── Chef ──────────────────────────────────────────────────────────────────

    public function misRecetas(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        require_once __DIR__ . '/../view/chef/mis_recetas.php';
    }

    public function crearReceta(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verificarCsrf();
            require_once __DIR__ . '/../models/receta.php';
            require_once __DIR__ . '/../models/categoria.php';

            $titulo = limpiarTexto($_POST['titulo']      ?? '', 150);
            $desc   = limpiarTexto($_POST['descripcion'] ?? '', 2000);
            $cat    = limpiarTexto($_POST['categoria']   ?? '', 80);
            $dif    = $_POST['dificultad'] ?? '';
            $tiempo = (int)($_POST['tiempo'] ?? 0);

            if (!$titulo || !$cat || !in_array($dif, ['Fácil','Media','Difícil']) || !$tiempo) {
                $_SESSION['error'] = 'Completa todos los campos obligatorios correctamente.';
                redirect('crear-receta');
            }

            $imagen = null;
            if (!empty($_FILES['imagen']['name'])) {
                $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
                if ($imagen === null) {
                    $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                    redirect('crear-receta');
                }
            }

            $model    = new Receta();
            $recetaId = $model->crear([
                'titulo'      => $titulo,
                'descripcion' => $desc,
                'tiempo'      => $tiempo,
                'porciones'   => (int)($_POST['porciones'] ?? 4),
                'dificultad'  => $dif,
                'categoria'   => $cat,
                'imagen'      => $imagen,
                'calorias'    => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
                'publicada'   => isset($_POST['publicada']) ? (int)(bool)$_POST['publicada'] : 1,
            ], (int)$_SESSION['usuario_id']);

            $ingredientes = [];
            foreach (($_POST['ing_nombre'] ?? []) as $i => $nombre) {
                if (trim($nombre)) {
                    $ingredientes[] = [
                        'nombre'   => $nombre,
                        'cantidad' => $_POST['ing_cantidad'][$i] ?? '',
                        'unidad'   => $_POST['ing_unidad'][$i]   ?? '',
                    ];
                }
            }
            $model->guardarIngredientes($recetaId, $ingredientes);
            $model->guardarPasos($recetaId, $_POST['pasos'] ?? []);

            $_SESSION['exito'] = isset($_POST['publicada']) && !$_POST['publicada']
                ? "Receta guardada como borrador."
                : "Receta publicada correctamente.";
            redirect('mis-recetas');
        }
        require_once __DIR__ . '/../view/chef/crear_receta.php';
    }

    public function editarReceta(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verificarCsrf();
            require_once __DIR__ . '/../models/receta.php';
            require_once __DIR__ . '/../models/categoria.php';

            $recetaId = (int)($_POST['receta_id'] ?? 0);
            $userId   = (int)$_SESSION['usuario_id'];
            if (!$recetaId) {
                $_SESSION['error'] = 'Receta no válida.';
                redirect('mis-recetas');
            }

            $model  = new Receta();
            $receta = $model->obtenerParaEditar($recetaId, $userId);
            if (!$receta) {
                $_SESSION['error'] = 'No tienes permiso para editar esa receta.';
                redirect('mis-recetas');
            }

            $titulo = limpiarTexto($_POST['titulo']      ?? '', 150);
            $desc   = limpiarTexto($_POST['descripcion'] ?? '', 2000);
            $cat    = limpiarTexto($_POST['categoria']   ?? '', 80);
            $dif    = $_POST['dificultad'] ?? '';
            $tiempo = (int)($_POST['tiempo'] ?? 0);

            if (!$titulo || !$cat || !in_array($dif, ['Fácil','Media','Difícil']) || !$tiempo) {
                $_SESSION['error'] = 'Completa todos los campos obligatorios.';
                redirect("editar-receta?id=$recetaId");
            }

            $imagen = null;
            if (!empty($_FILES['imagen']['name'])) {
                $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
                if ($imagen === null) {
                    $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                    redirect("editar-receta?id=$recetaId");
                }
            }

            $model->actualizar($recetaId, [
                'titulo'      => $titulo,
                'descripcion' => $desc,
                'tiempo'      => $tiempo,
                'porciones'   => (int)($_POST['porciones'] ?? 4),
                'dificultad'  => $dif,
                'categoria'   => $cat,
                'calorias'    => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
                'imagen'      => $imagen,
                'chef_id'     => $userId,
                'publicada'   => isset($_POST['publicada']) ? (int)(bool)$_POST['publicada'] : null,
            ]);

            $ingredientes = [];
            foreach (($_POST['ing_nombre'] ?? []) as $i => $nombre) {
                if (trim($nombre)) {
                    $ingredientes[] = [
                        'nombre'   => $nombre,
                        'cantidad' => $_POST['ing_cantidad'][$i] ?? '',
                        'unidad'   => $_POST['ing_unidad'][$i]   ?? '',
                    ];
                }
            }
            $model->guardarIngredientes($recetaId, $ingredientes);
            $model->guardarPasos($recetaId, $_POST['pasos'] ?? []);

            $_SESSION['exito'] = "Receta \"$titulo\" actualizada correctamente.";
            redirect('mis-recetas');
        }
        require_once __DIR__ . '/../view/chef/editar_receta.php';
    }

    public function eliminarReceta(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        verificarCsrf();
        require_once __DIR__ . '/../models/receta.php';
        $recetaId = (int)($_POST['receta_id'] ?? 0);
        $userId   = (int)$_SESSION['usuario_id'];
        $rol      = $_SESSION['rol'];
        if ($recetaId) {
            $res = (new Receta())->eliminar($recetaId, $userId, $rol);
            if (isset($res['exito'])) {
                $_SESSION['exito'] = 'Receta eliminada correctamente.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo eliminar la receta.';
            }
        }
        redirect('mis-recetas');
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function dashboardAdmin(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/dashboard.php';
    }

    public function adminUsuarios(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/usuarios.php';
    }

    public function adminCategorias(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/categorias.php';
    }

    public function adminReportes(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/reportes.php';
    }

    public function adminModeracion(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/moderacion.php';
    }

    // ── Admin — Gestión de recetas ─────────────────────────────────────────────

    public function adminRecetas(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../controllers/AdminRecetaController.php';
        (new AdminRecetaController())->listado();
    }

    public function adminRecetaNueva(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../controllers/AdminRecetaController.php';
        $ctrl = new AdminRecetaController();
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->crearPost() : $ctrl->nueva();
    }

    public function adminRecetaEditar(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../controllers/AdminRecetaController.php';
        $ctrl = new AdminRecetaController();
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->editarPost() : $ctrl->editar();
    }

    public function adminRecetaVer(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../controllers/AdminRecetaController.php';
        (new AdminRecetaController())->ver();
    }

    public function adminRecetaEliminar(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../controllers/AdminRecetaController.php';
        (new AdminRecetaController())->eliminarPost();
    }

    public function adminCambiarContrasenas(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/cambiar_contrasenas.php';
    }

    public function adminCambiarContrasenasPost(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id           = (int)($_POST['usuario_id']        ?? 0);
        $nueva        = $_POST['nueva_password']           ?? '';
        $confirmacion = $_POST['confirmar_password']       ?? '';
        $baseUrl      = rtrim(BASE_URL, '/');

        if (!$id) {
            $_SESSION['error'] = 'Usuario no válido.';
            redirect('index.php?route=admin/cambiar-contrasenas');
        }
        if (strlen($nueva) < 8) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
            redirect('index.php?route=admin/cambiar-contrasenas&id=' . $id);
        }
        if ($nueva !== $confirmacion) {
            $_SESSION['error'] = 'Las contraseñas no coinciden.';
            redirect('index.php?route=admin/cambiar-contrasenas&id=' . $id);
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        try {
            require_once __DIR__ . '/../config/conexion.php';
            $pdo = Conexion::getInstancia()->getPDO();
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?")
                ->execute([$hash, $id]);
            $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $nombreUsr = $stmt->fetchColumn() ?: "ID $id";
            $_SESSION['exito'] = "✅ Contraseña de «$nombreUsr» actualizada correctamente.";
        } catch (Throwable $e) {
            error_log('[cambiarPassword] ' . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar: ' . $e->getMessage();
        }
        redirect('index.php?route=admin/cambiar-contrasenas&id=' . $id);
    }
}
