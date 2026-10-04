<?php
require_once __DIR__ . '/../config/conexion.php';

class Categoria {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    /** Detectar PK real de la tabla categorías */
    private function pk(): string {
        static $cache = null;
        if ($cache === null) {
            try {
                $r = $this->pdo->query("SHOW KEYS FROM categorias WHERE Key_name='PRIMARY'")->fetch(PDO::FETCH_ASSOC);
                $cache = $r ? $r['Column_name'] : 'id_categoria';
            } catch (\Throwable $e) {
                $cache = 'id_categoria'; // Fallback común
            }
        }
        return $cache;
    }

    /** Lista todas las categorías con icono, color y bg. */
    public function listarSimple(): array {
        $pk = $this->pk();
        $stmt = $this->pdo->query("
            SELECT $pk AS id, nombre, icono, color, bg
            FROM categorias
            ORDER BY nombre ASC
        ");
        return $stmt->fetchAll();
    }

    /** Lista todas las categorías con conteo de recetas asociadas. */
    public function listarTodas(): array {
        $pk = $this->pk();
        $pkR = 'id_receta'; // PK de recetas (podríamos detectarla también)
        try {
            // Intentar con id_categoria primero (nombre más común)
            $stmt = $this->pdo->query("
                SELECT c.id_categoria AS id,
                       c.nombre, c.icono, c.color, c.bg,
                       COUNT(r.id_receta) AS total_recetas
                FROM categorias c
                LEFT JOIN recetas r ON r.id_categoria = c.id_categoria
                GROUP BY c.id_categoria, c.nombre, c.icono, c.color, c.bg
                ORDER BY c.nombre ASC
            ");
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            // Fallback: PK = 'id'
            try {
                $stmt = $this->pdo->query("
                    SELECT c.id AS id,
                           c.nombre, c.icono, c.color, c.bg,
                           COUNT(r.id) AS total_recetas
                    FROM categorias c
                    LEFT JOIN recetas r ON r.id_categoria = c.id
                    GROUP BY c.id, c.nombre, c.icono, c.color, c.bg
                    ORDER BY c.nombre ASC
                ");
                return $stmt->fetchAll();
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    public function crear(string $nombre, string $icono, string $color, string $bg): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO categorias (nombre, icono, color, bg)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $icono, $color, $bg]);
        return (int)$this->pdo->lastInsertId();
    }

    public function eliminar(int $id): bool {
        $pk = $this->pk();
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE $pk = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function obtenerPorId(int $id): ?array {
        $pk = $this->pk();
        $stmt = $this->pdo->prepare("SELECT $pk AS id, nombre, icono, color, bg FROM categorias WHERE $pk = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function obtenerPorNombre(string $nombre): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE nombre = ?");
        $stmt->execute([$nombre]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
