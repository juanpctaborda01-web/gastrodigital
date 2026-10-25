<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/usuarios.php';

class PerfilController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    public function guardar(): void
    {
        requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('perfil');
        }

        verificarCsrf();

        $userId       = $_SESSION['usuario_id'];
        $nombre       = trim($_POST['nombre']         ?? '');
        $bio          = trim($_POST['bio']            ?? '');
        $redes        = trim($_POST['redes_sociales'] ?? '');
        $especialidad = trim($_POST['especialidad']   ?? '');
        $passActual   = $_POST['password_actual']     ?? '';
        $passNuevo    = $_POST['password_nuevo']      ?? '';
        $passConf     = $_POST['password_confirmar']  ?? '';

        if (empty($nombre)) {
            $_SESSION['error'] = 'El nombre no puede estar vacío.';
            redirect('perfil');
        }

        // Subida de avatar (opcional)
        $avatar = '';
        if (!empty($_FILES['avatar']['name'])) {
            $subido = procesarImagenUpload($_FILES['avatar'], UPLOAD_PATH);
            if ($subido === null) {
                $_SESSION['error'] = 'La imagen de perfil no es válida (JPG, PNG, WEBP · máx. 3 MB).';
                redirect('perfil');
            }
            $avatar = $subido;
        }

        // Actualizar datos del perfil
        $ok = $this->model->actualizarPerfil(
            $userId, $nombre, $avatar, $bio, $redes, $especialidad
        );

        if (!$ok) {
            $_SESSION['error'] = 'No se pudo actualizar el perfil. Intenta de nuevo.';
            redirect('perfil');
        }

        $_SESSION['usuario_nombre'] = $nombre;
        if ($avatar) {
            $_SESSION['usuario_avatar'] = $avatar;
        }

        // Cambio de contraseña (opcional — solo si escribió algo)
        if ($passNuevo !== '') {
            if (empty($passActual)) {
                $_SESSION['error'] = 'Debes ingresar tu contraseña actual para cambiarla.';
                redirect('perfil');
            }

            $usuarioCompleto = $this->model->buscarPorEmail(
                $this->model->buscarPorId($userId)['email'] ?? ''
            );

            if (!$usuarioCompleto || !password_verify($passActual, $usuarioCompleto['password'])) {
                $_SESSION['error'] = 'La contraseña actual es incorrecta.';
                redirect('perfil');
            }

            if ($passNuevo !== $passConf) {
                $_SESSION['error'] = 'Las contraseñas nuevas no coinciden.';
                redirect('perfil');
            }

            $errores = validarPassword($passNuevo);
            if (!empty($errores)) {
                $_SESSION['error'] = implode(' ', $errores);
                redirect('perfil');
            }

            $hash = password_hash($passNuevo, PASSWORD_BCRYPT);
            $this->model->cambiarPassword($userId, $hash);
        }

        $_SESSION['exito'] = 'Perfil actualizado correctamente.';
        redirect('perfil');
    }
}
