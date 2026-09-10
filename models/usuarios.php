<?php
require_once __DIR__ . '/../config/conexion.php';

class Usuario {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    private function columnasPerfil(): array {
        static $columnas = null;

        if ($columnas === null) {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM usuarios');
            $columnas = [];
            foreach ($stmt->fetchAll() as $col) {
                $columnas[] = $col['Field'];
            }
        }

        return $columnas;
    }

    // Buscar usuario por email para login
    public function buscarPorEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // Buscar usuario por ID
    public function buscarPorId($id) {
        $columnas = $this->columnasPerfil();
        $campos = ['id', 'nombre', 'email', 'rol', 'avatar', 'bio', 'created_at'];

        if (in_array('redes_sociales', $columnas, true)) {
            $campos[] = 'redes_sociales';
        }
        if (in_array('especialidad', $columnas, true)) {
            $campos[] = 'especialidad';
        }

        $stmt = $this->pdo->prepare(
            'SELECT ' . implode(', ', $campos) . ' FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Actualizar perfil del usuario
    public function actualizarPerfil($id, $nombre, $avatar, $bio, $redesSociales, $especialidad) {
        $nombre = limpiarTexto($nombre, 100);
        $avatar = limpiarTexto($avatar, 255);
        $bio = limpiarTexto($bio, 1000);
        $redesSociales = limpiarTexto($redesSociales, 500);
        $especialidad = limpiarTexto($especialidad, 255);

        $columnas = $this->columnasPerfil();
        $campos = ['nombre = ?', 'avatar = ?', 'bio = ?'];
        $valores = [$nombre, $avatar ?: null, $bio ?: null];

        if (in_array('redes_sociales', $columnas, true)) {
            $campos[] = 'redes_sociales = ?';
            $valores[] = $redesSociales ?: null;
        }
        if (in_array('especialidad', $columnas, true)) {
            $campos[] = 'especialidad = ?';
            $valores[] = $especialidad ?: null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $valores[] = $id;

        return $stmt->execute($valores);
    }

    // Registrar nuevo usuario
    public function registrar($nombre, $email, $password, $rol = 'usuario') {
        // Verificar que el email no exista
        if ($this->buscarPorEmail($email)) {
            return ['exito' => false, 'mensaje' => 'El correo ya está registrado.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$nombre, $email, $hash, $rol]);
        return ['exito' => true, 'id' => $this->pdo->lastInsertId()];
    }

    // Verificar credenciales
    public function verificarCredenciales($email, $password) {
        $usuario = $this->buscarPorEmail($email);
        if (!$usuario) {
            return false;
        }
        if (!password_verify($password, $usuario['password'])) {
            return false;
        }
        return $usuario;
    }

    // Listar todos los usuarios (admin)
    public function listarTodos() {
        $stmt = $this->pdo->query("SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    // Usuarios registrados recientemente (últimos N días)
    public function usuariosRecientes(int $dias = 30, int $limite = 8): array {
        $stmt = $this->pdo->prepare("
            SELECT id, nombre, email, rol, created_at
            FROM usuarios
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$dias, $limite]);
        return $stmt->fetchAll();
    }

    // Conteo de usuarios por rol
    public function statsPorRol(): array {
        $stmt = $this->pdo->query("
            SELECT rol, COUNT(*) AS total
            FROM usuarios
            GROUP BY rol
        ");
        $rows = $stmt->fetchAll();
        $result = ['admin' => 0, 'chef' => 0, 'usuario' => 0];
        foreach ($rows as $r) {
            $result[$r['rol']] = (int)$r['total'];
        }
        return $result;
    }

    // Total de usuarios nuevos en los últimos N días
    public function totalNuevosEnDias(int $dias = 30): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM usuarios
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$dias]);
        return (int)$stmt->fetchColumn();
    }

    // Cambiar contraseña del usuario
    public function cambiarPassword(int $id, string $hash): bool {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    // Actualizar rol
    public function actualizarRol($id, $rol) {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        return $stmt->execute([$rol, $id]);
    }

    // Eliminar usuario
    public function eliminar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
