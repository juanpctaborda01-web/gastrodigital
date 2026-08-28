<?php
require_once __DIR__ . '/../config/conexion.php';

class Categoria {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    /**
     * Lista todas las categorías con icono, color y bg.
     * Usado en index_home.php y vistas de filtros.
     */
    public function listarSimple(): array {
        $stmt = $this->pdo->query("
            SELECT id, nombre, icono, color, bg
            FROM categorias
            ORDER BY nombre ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lista todas las categorías incluyendo el conteo de recetas asociadas.
     * Usado en view/admin/categorias.php.
     */


    public function listarTodas(): array
{
    $stmt = $this->pdo->query("
        SELECT 
            c.id,
            c.nombre,
            c.icono,
            c.color,
            c.bg,
            COUNT(r.id) AS total_recetas
        FROM categorias c
        LEFT JOIN recetas r 
            ON r.categoria_id = c.id
        GROUP BY 
            c.id,
            c.nombre,
            c.icono,
            c.color,
            c.bg
        ORDER BY c.nombre ASC
    ");

    return $stmt->fetchAll();
}

    public function crear(string $nombre, string $icono, string $color, string $bg): int
{
    $stmt = $this->pdo->prepare("
        INSERT INTO categorias (nombre, icono, color, bg)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$nombre, $icono, $color, $bg]);

    return (int) $this->pdo->lastInsertId();
}



    /**
     * Elimina una categoría por ID.
     */
    public function eliminar(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene una categoría por su ID.
     */
    public function obtenerPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtiene una categoría por nombre (útil para validar duplicados).
     */
    public function obtenerPorNombre(string $nombre): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE nombre = ?");
        $stmt->execute([$nombre]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
