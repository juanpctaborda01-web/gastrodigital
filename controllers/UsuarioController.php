<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/usuarios.php';

class UsuarioController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    /** POST /api/perfil/guardar — guardar perfil desde dashboard usuario o chef */
    public function guardarPerfil(): void
    {
        requireLogin();
        verificarCsrf();

        $id    = (int)$_SESSION['usuario_id'];
        $nombre       = trim($_POST['nombre']         ?? '');
        $bio          = trim($_POST['bio']            ?? '');
        $redes        = trim($_POST['redes_sociales'] ?? '');
        $especialidad = trim($_POST['especialidad']   ?? '');
        $avatar       = '';

        if (empty($nombre)) {
            $_SESSION['error'] = 'El nombre no puede estar vacío.';
            $this->redirigirPorRol();
        }

        // Subida de avatar
        if (!empty($_FILES['avatar_file']['name'])) {
            require_once __DIR__ . '/../config/seguridad.php';
            $subido = procesarImagenUpload($_FILES['avatar_file'], UPLOAD_PATH);
            if ($subido) {
                $avatar = $subido;
            } else {
                $_SESSION['error'] = 'La imagen no es válida (JPG, PNG, WEBP · máx. 3 MB).';
                $this->redirigirPorRol();
            }
        }

        if ($this->model->actualizarPerfil($id, $nombre, $avatar, $bio, $redes, $especialidad)) {
            $_SESSION['usuario_nombre'] = $nombre;
            if ($avatar) {
                $_SESSION['usuario_avatar'] = $avatar;
            }
            $_SESSION['exito'] = 'Perfil actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo guardar el perfil.';
        }
        $this->redirigirPorRol();
    }

    private function redirigirPorRol(): never
    {
        match ($_SESSION['rol'] ?? 'usuario') {
            ROL_ADMIN => redirect('admin'),
            ROL_CHEF  => redirect('mis-recetas'),
            default   => redirect('dashboard'),
        };
    }

    /** POST /admin/usuario/rol — cambiar rol de un usuario */
    public function cambiarRol(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id  = (int)($_POST['id']  ?? 0);
        $rol = $_POST['rol'] ?? '';

        if (!$id || !in_array($rol, ['admin', 'chef', 'usuario'])) {
            $_SESSION['error'] = 'Datos inválidos.';
        } else {
            $this->model->actualizarRol($id, $rol);
            $_SESSION['exito'] = 'Rol actualizado correctamente.';
        }
        redirect('admin/usuarios');
    }

    /** POST /admin/usuario/eliminar — eliminar un usuario */
    public function eliminar(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== (int)$_SESSION['usuario_id']) {
            $this->model->eliminar($id);
            $_SESSION['exito'] = 'Usuario eliminado.';
        } else {
            $_SESSION['error'] = 'No puedes eliminarte a ti mismo.';
        }
        redirect('admin/usuarios');
    }
}
