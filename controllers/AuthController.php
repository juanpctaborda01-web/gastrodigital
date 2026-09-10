<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/usuarios.php';

class AuthController {
    private Usuario $model;

    public function __construct() {
        $this->model = new Usuario();
    }

    // ── Login ─────────────────────────────────────────────────────────────────
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }

        verificarCsrf();

        if (loginBloqueado()) {
            $min = ceil(tiempoRestanteBloqueo() / 60);
            $_SESSION['error'] = "Demasiados intentos fallidos. Espera {$min} minuto(s).";
            redirect('login');
        }

        $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password) || !validarEmail($email)) {
            $_SESSION['error'] = 'Por favor completa todos los campos correctamente.';
            redirect('login');
        }

        $usuario = $this->model->verificarCredenciales($email, $password);

        if (!$usuario) {
            registrarIntentoLogin();
            $_SESSION['error'] = 'Correo o contraseña incorrectos.';
            redirect('login');
        }

        if (isset($usuario['activo']) && !$usuario['activo']) {
            $_SESSION['error'] = 'Tu cuenta está suspendida. Contacta al administrador.';
            redirect('login');
        }

        // Login exitoso
        resetIntentoLogin();
        regenerarSesion();

        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['rol']            = $usuario['rol'];
        $_SESSION['usuario_avatar'] = $usuario['avatar'] ?? null;

        // Recordar sesión: guardar cookie si el usuario lo pidió
        if (!empty($_POST['recordarme'])) {
            guardarCookieRecordar($usuario);
        }

        $this->redirigirPorRol($usuario['rol']);
    }

    // ── Registro ──────────────────────────────────────────────────────────────
    public function registro(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('register');
        }

        verificarCsrf();

        $nombre    = limpiarTexto($_POST['nombre']   ?? '', 100);
        $email     = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password  = $_POST['password']          ?? '';
        $confirmar = $_POST['confirmar_password'] ?? '';

        if (empty($nombre) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Por favor completa todos los campos.';
            redirect('register');
        }

        if (!validarEmail($email)) {
            $_SESSION['error'] = 'El correo electrónico no es válido.';
            redirect('register');
        }

        $erroresPass = validarPassword($password);
        if (!empty($erroresPass)) {
            $_SESSION['error'] = implode(' ', $erroresPass);
            redirect('register');
        }

        if ($password !== $confirmar) {
            $_SESSION['error'] = 'Las contraseñas no coinciden.';
            redirect('register');
        }

        $resultado = $this->model->registrar($nombre, $email, $password);

        if (!$resultado['exito']) {
            $_SESSION['error'] = $resultado['mensaje'];
            redirect('register');
        }

        // Si pidió recordar sesión, iniciamos sesión automáticamente tras registro
        if (!empty($_POST['recordarme'])) {
            $usuario = $this->model->buscarPorEmail($email);
            if ($usuario) {
                regenerarSesion();
                $_SESSION['usuario_id']     = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email']  = $usuario['email'];
                $_SESSION['rol']            = $usuario['rol'];
                guardarCookieRecordar($usuario);
                $this->redirigirPorRol($usuario['rol']);
            }
        }

        $_SESSION['exito'] = 'Cuenta creada. Ya puedes iniciar sesión.';
        redirect('login');
    }

    // ── Logout ────────────────────────────────────────────────────────────────
    public function logout(): void {
        // Borrar cookie de "recordar sesión" si existe
        if (isset($_COOKIE['gd_remember'])) {
            setcookie('gd_remember', '', time() - 3600, '/', '', false, true);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        redirect('login');
    }

    private function redirigirPorRol(string $rol): void {
        match ($rol) {
            ROL_ADMIN => redirect('admin'),
            ROL_CHEF  => redirect('mis-recetas'),
            default   => redirect('dashboard'),
        };
    }
}
