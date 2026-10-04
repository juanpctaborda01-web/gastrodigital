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
        $stmt = $this->pdo->prepare("SELECT *, id_usuario AS id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && !isset($row['id'])) {
            $row['id'] = $row['id_usuario'];
        }
        return $row ?: false;
    }

    // Buscar usuario por ID
    public function buscarPorId($id) {
        $columnas = $this->columnasPerfil();
        $campos = ['id_usuario AS id', 'id_usuario', 'nombre', 'email', 'rol', 'avatar', 'bio', 'created_at'];

        if (in_array('redes_sociales', $columnas, true)) {
            $campos[] = 'redes_sociales';
        }
        if (in_array('especialidad', $columnas, true)) {
            $campos[] = 'especialidad';
        }
        if (in_array('genero', $columnas, true)) {
            $campos[] = 'genero';
        }
        if (in_array('pais_origen', $columnas, true)) {
            $campos[] = 'pais_origen';
        }

        $stmt = $this->pdo->prepare(
            'SELECT ' . implode(', ', $campos) . ' FROM usuarios WHERE id_usuario = ? LIMIT 1'
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
            'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id_usuario = ?'
        );
        $valores[] = $id;

        return $stmt->execute($valores);
    }

    // Registrar nuevo usuario (con género, país y opción de rol chef)
    public function registrar($nombre, $email, $password, $rol = 'usuario', $genero = null, $pais_origen = null) {
        // Verificar que el email no exista
        if ($this->buscarPorEmail($email)) {
            return ['exito' => false, 'mensaje' => 'El correo ya está registrado.'];
        }

        // Solo permitir 'usuario' o 'chef' desde registro público
        $rolPermitido = in_array($rol, ['usuario', 'chef'], true) ? $rol : 'usuario';

        // Sanitizar género
        $generosValidos = ['masculino', 'femenino', 'otro', 'prefiero_no_decir'];
        $generoFinal = in_array($genero, $generosValidos, true) ? $genero : null;

        // Sanitizar país
        $paisFinal = !empty($pais_origen) ? limpiarTexto($pais_origen, 100) : null;

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $columnas = $this->columnasPerfil();

        $cols = ['nombre', 'email', 'password', 'rol', 'created_at'];
        $vals = [$nombre, $email, $hash, $rolPermitido];
        $placeholders = ['?', '?', '?', '?', 'NOW()'];

        if (in_array('genero', $columnas, true)) {
            $cols[] = 'genero';
            $vals[] = $generoFinal;
            $placeholders[] = '?';
        }
        if (in_array('pais_origen', $columnas, true)) {
            $cols[] = 'pais_origen';
            $vals[] = $paisFinal;
            $placeholders[] = '?';
        }

        $sql = "INSERT INTO usuarios (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        $idGenerado = (int) $this->pdo->lastInsertId();
        return ['exito' => true, 'id' => $idGenerado, 'id_usuario' => $idGenerado];
    }

    // Listar países desde la tabla paises
    public function listarPaises(): array {
        try {
            $stmt = $this->pdo->query("SELECT id_pais, nombre, codigo_iso AS codigo FROM paises ORDER BY nombre ASC");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
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
        if (!isset($usuario['id']) && isset($usuario['id_usuario'])) {
            $usuario['id'] = $usuario['id_usuario'];
        }
        return $usuario;
    }

    // Listar todos los usuarios (admin)
    public function listarTodos() {
        $stmt = $this->pdo->query("SELECT id_usuario AS id, id_usuario, nombre, email, rol, activo, created_at FROM usuarios ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    // Usuarios registrados recientemente (últimos N días)
    public function usuariosRecientes(int $dias = 30, int $limite = 8): array {
        $stmt = $this->pdo->prepare("
            SELECT id_usuario AS id, id_usuario, nombre, email, rol, created_at
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

    // Actualizar rol
    public function actualizarRol($id, $rol) {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
        return $stmt->execute([$rol, $id]);
    }

    // Eliminar usuario
    public function eliminar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }
}
