<?php
require_once __DIR__ . '/../config/conexion.php';

class Ingrediente {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    // Obtener ingredientes de una receta
    public function porReceta(int $recetaId): array {
        $stmt = $this->pdo->prepare(
            "SELECT id_ingrediente AS id, nombre, cantidad, unidad, opcional, orden FROM ingredientes WHERE id_receta = ? ORDER BY orden ASC, id_ingrediente ASC"
        );
        $stmt->execute([$recetaId]);
        return $stmt->fetchAll();
    }

    // Buscar recetas que usen un ingrediente específico
    public function recetasConIngrediente(string $ingrediente): array {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT r.id_receta AS id, r.id_receta, r.titulo, c.nombre AS categoria, r.tiempo_preparacion
            FROM recetas r
            INNER JOIN ingredientes i ON i.id_receta = r.id_receta
            LEFT JOIN categorias c ON c.id_categoria = r.id_categoria
            WHERE i.nombre LIKE ?
            LIMIT 5
        ");
        $stmt->execute(['%' . $ingrediente . '%']);
        return $stmt->fetchAll();
    }
}
