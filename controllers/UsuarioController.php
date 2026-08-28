<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/usuarios.php';

requireLogin();

$model  = new Usuario();
$accion = $_GET['accion'] ?? '';

// Verificar CSRF en todas las acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}

switch ($accion) {

    // Guardar perfil del usuario
    case 'guardarPerfil':
        $id = (int)($_POST['usuario_id'] ?? $_SESSION['usuario_id'] ?? 0);
        if (!$id || $id !== (int)($_SESSION['usuario_id'] ?? 0)) {
            $_SESSION['error'] = 'No puedes actualizar ese perfil.';
            redirect('dashboard');
        }

        $nombre = $_POST['nombre'] ?? '';
        $avatar = $_POST['avatar'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $redesSociales = $_POST['redes_sociales'] ?? '';
        $especialidad = $_POST['especialidad'] ?? '';

        if (!empty($_FILES['avatar_file']['name'])) {
            $rutaDestino = UPLOAD_PATH;
            $archivoSubido = procesarImagenUpload($_FILES['avatar_file'], $rutaDestino);
            if ($archivoSubido) {
                $avatar = $archivoSubido;
            } else {
                $_SESSION['error'] = 'La imagen de perfil no es válida.';
                $rolActual = $_SESSION['rol'] ?? 'usuario';
                $redirectPath = match ($rolActual) {
                    ROL_ADMIN => 'admin',
                    ROL_CHEF  => 'mis-recetas',
                    default   => 'dashboard',
                };
                redirect($redirectPath);
            }
        }

        if ($model->actualizarPerfil($id, $nombre, $avatar, $bio, $redesSociales, $especialidad)) {
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['exito'] = 'Perfil actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo guardar el perfil.';
        }

        $rolActual = $_SESSION['rol'] ?? 'usuario';
        $redirectPath = match ($rolActual) {
            ROL_ADMIN => 'admin',
            ROL_CHEF  => 'mis-recetas',
            default   => 'dashboard',
        };
        redirect($redirectPath);
        break;

    // Cambiar rol de un usuario
    case 'cambiarRol':
        requireRole(ROL_ADMIN);
        $id  = (int)($_POST['id'] ?? 0);
        $rol = $_POST['rol'] ?? '';
        if (!$id || !in_array($rol, ['admin', 'chef', 'usuario'])) {
            $_SESSION['error'] = 'Datos inválidos.';
        } else {
            $model->actualizarRol($id, $rol);
            $_SESSION['exito'] = 'Rol actualizado correctamente.';
        }
        redirect('admin/usuarios');
        break;

    // Eliminar usuario
    case 'eliminar':
        requireRole(ROL_ADMIN);
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== (int)$_SESSION['usuario_id']) {
            $model->eliminar($id);
            $_SESSION['exito'] = 'Usuario eliminado.';
        } else {
            $_SESSION['error'] = 'No puedes eliminarte a ti mismo.';
        }
        redirect('admin/usuarios');
        break;

    default:
        redirect('admin/usuarios');
}
