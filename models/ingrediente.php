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
            "SELECT nombre, cantidad, unidad FROM ingredientes WHERE receta_id = ? ORDER BY id ASC"
        );
        $stmt->execute([$recetaId]);
        return $stmt->fetchAll();
    }

    // Buscar recetas que usen un ingrediente específico
    public function recetasConIngrediente(string $ingrediente): array {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT r.id, r.titulo, r.categoria, r.tiempo_preparacion
            FROM recetas r
            INNER JOIN ingredientes i ON i.receta_id = r.id
            WHERE i.nombre LIKE ?
            LIMIT 5
        ");
        $stmt->execute(['%' . $ingrediente . '%']);
        return $stmt->fetchAll();
    }
}
