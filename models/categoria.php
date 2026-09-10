<?php
require_once __DIR__ . '/../config/conexion.php';

class Categoria {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    /**
     * Lista todas las categorías con icono, color y bg.
     * Enriquece con el campo 'img' desde config/categorias.php si existe.
     * Usado en index_home.php y vistas de filtros.
     */
    public function listarSimple(): array {
        $stmt = $this->pdo->query("
            SELECT id, nombre, icono, color, bg
            FROM categorias
            ORDER BY nombre ASC
        ");
        $rows = $stmt->fetchAll();
        return $this->enriquecerConImg($rows);
    }

    /**
     * Lista todas las categorías incluyendo el conteo de recetas asociadas.
     * Enriquece con el campo 'img' desde config/categorias.php si existe.
     * Usado en view/admin/categorias.php y view/usuario/categorias.php.
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
            LEFT JOIN recetas r ON r.categoria_id = c.id
            GROUP BY c.id, c.nombre, c.icono, c.color, c.bg
            ORDER BY c.nombre ASC
        ");
        $rows = $stmt->fetchAll();
        return $this->enriquecerConImg($rows);
    }

    /**
     * Cruza los arrays de la BD con config/categorias.php para
     * agregar el campo 'img' a cada fila por nombre de categoría.
     */
    private function enriquecerConImg(array $rows): array
    {
        // Cargar el mapa nombre → img desde la constante CATEGORIAS si está disponible
        if (!defined('CATEGORIAS')) {
            $cfg = __DIR__ . '/../config/categorias.php';
            if (file_exists($cfg)) require_once $cfg;
        }

        if (!defined('CATEGORIAS')) {
            // Si por algún motivo no se pudo cargar, devolver sin enriquecer
            foreach ($rows as &$row) { $row['img'] = null; }
            return $rows;
        }

        // Construir mapa: strtolower(nombre) → img
        $mapaImg = [];
        foreach (CATEGORIAS as $cat) {
            $mapaImg[strtolower(trim($cat['nombre']))] = $cat['img'] ?? null;
        }

        foreach ($rows as &$row) {
            $key = strtolower(trim($row['nombre']));
            $row['img'] = $mapaImg[$key] ?? null;
        }
        unset($row);

        return $rows;
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
