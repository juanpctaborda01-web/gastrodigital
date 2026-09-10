<?php
require_once __DIR__ . '/../config/conexion.php';

class Receta {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    // Listar recetas con filtros opcionales + paginación
    public function listarFiltradas(array $filtros = [], int $pagina = 1, int $porPagina = 9): array {
        $where  = ['r.publicada = 1'];
        $params = [];

        if (!empty($filtros['destacada'])) {
            $where[]  = 'r.destacada = 1';
        }
        if (!empty($filtros['q'])) {
            $where[]  = '(r.titulo LIKE ? OR r.descripcion LIKE ? OR i.nombre LIKE ?)';
            $like     = '%' . $filtros['q'] . '%';
            $params   = array_merge($params, [$like, $like, $like]);
        }
        if (!empty($filtros['categoria'])) {
            $where[]  = 'r.categoria_id LIKE ?';
            $params[] = '%' . $filtros['categoria'] . '%';
        }
        if (!empty($filtros['pais'])) {
            $where[] = 'r.pais = ?';
            $params[] = $filtros['pais'];
        }
        if (!empty($filtros['dificultad'])) {
            $where[]  = 'r.dificultad = ?';
            $params[] = $filtros['dificultad'];
        }
        if (!empty($filtros['tiempo'])) {
            $where[]  = 'r.tiempo_preparacion <= ?';
            $params[] = (int)$filtros['tiempo'];
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        // Total para paginación
        $countSQL = "SELECT COUNT(DISTINCT r.id) FROM recetas r LEFT JOIN ingredientes i ON i.receta_id = r.id $whereSQL";
        $stmt     = $this->pdo->prepare($countSQL);
        $stmt->execute($params);
        $total    = (int)$stmt->fetchColumn();

        // Recetas de esta página
        $sql = "SELECT DISTINCT r.id, r.titulo, r.descripcion, r.tiempo_preparacion,
                       r.dificultad, r.categoria_id, r.pais, r.imagen, r.calorias, r.porciones,
                       r.chef_id, r.destacada, r.visitas,
                       u.nombre AS chef_nombre,
                       ROUND(AVG(v.puntuacion), 1) AS rating,
                       COUNT(DISTINCT v.id)         AS total_valoraciones
                FROM recetas r
                LEFT JOIN ingredientes i ON i.receta_id = r.id
                LEFT JOIN usuarios u     ON u.id = r.chef_id
                LEFT JOIN valoraciones v ON v.receta_id = r.id
                $whereSQL
                GROUP BY r.id
                ORDER BY r.destacada DESC, r.created_at DESC
                LIMIT $porPagina OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'recetas'     => $stmt->fetchAll(),
            'total'       => $total,
            'paginas'     => (int)ceil($total / $porPagina),
            'pagina'      => $pagina,
        ];
    }

    public function listarPaises(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT pais FROM recetas WHERE pais IS NOT NULL AND pais <> '' ORDER BY pais");
        return array_column($stmt->fetchAll(), 'pais');
    }

    // Obtener receta para edición (verifica propiedad del chef)
    public function obtenerParaEditar(int $id, int $chefId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM recetas WHERE id = ? AND chef_id = ?");
        $stmt->execute([$id, $chefId]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE receta_id = ? ORDER BY orden, id");
        $stmt2->execute([$id]);
        $receta['ingredientes'] = $stmt2->fetchAll();

        $stmt3 = $this->pdo->prepare("SELECT * FROM pasos WHERE receta_id = ? ORDER BY numero");
        $stmt3->execute([$id]);
        $receta['pasos'] = $stmt3->fetchAll();

        return $receta;
    }

    // Actualizar receta existente
    public function actualizar(int $id, array $datos): bool {
        $stmt = $this->pdo->prepare("
            UPDATE recetas
            SET titulo = ?, descripcion = ?, tiempo_preparacion = ?, porciones = ?,
                dificultad = ?, categoria_id = ?, calorias = ?
                " . (!empty($datos['imagen']) ? ", imagen = ?" : "") . "
            WHERE id = ? AND chef_id = ?
        ");

        $params = [
            $datos['titulo'],
            $datos['descripcion'],
            (int)$datos['tiempo'],
            (int)($datos['porciones'] ?? 4),
            $datos['dificultad'],
            $datos['categoria'],
            $datos['calorias'] ?? null,
        ];
        if (!empty($datos['imagen'])) {
            $params[] = $datos['imagen'];
        }
        $params[] = $id;
        $params[] = (int)$datos['chef_id'];

        $stmt->execute($params);
        return $stmt->rowCount() > 0 || true; // rowCount=0 si no hubo cambios es OK
    }

    // Obtener receta completa con ingredientes, pasos, valoraciones y comentarios
    public function obtenerCompleta(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.nombre AS chef_nombre, u.bio AS chef_bio, u.avatar AS chef_avatar,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.id) AS total_valoraciones
            FROM recetas r
            LEFT JOIN usuarios u    ON u.id = r.chef_id
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            WHERE r.id = ?
            GROUP BY r.id
        ");
        $stmt->execute([$id]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        // Ingredientes
        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE receta_id = ? ORDER BY orden, id");
        $stmt2->execute([$id]);
        $receta['ingredientes'] = $stmt2->fetchAll();

        // Pasos
        $stmt3 = $this->pdo->prepare("SELECT * FROM pasos WHERE receta_id = ? ORDER BY numero");
        $stmt3->execute([$id]);
        $receta['pasos'] = $stmt3->fetchAll();

        // Comentarios (incluye avatar del autor)
        $stmt4 = $this->pdo->prepare("
            SELECT c.*, u.nombre AS autor, u.avatar AS autor_avatar
            FROM comentarios c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.receta_id = ? ORDER BY c.created_at ASC
        ");
        $stmt4->execute([$id]);
        $receta['comentarios'] = $stmt4->fetchAll();

        // Incrementar visitas
        $this->pdo->prepare("UPDATE recetas SET visitas = visitas + 1 WHERE id = ?")->execute([$id]);

        return $receta;
    }

    // Recetas del chef logueado
    public function porChef(int $chefId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.id) AS total_valoraciones,
                   COUNT(DISTINCT f.usuario_id) AS total_favoritos
            FROM recetas r
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            LEFT JOIN favoritos f    ON f.receta_id = r.id
            WHERE r.chef_id = ?
            GROUP BY r.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    // Crear nueva receta
    public function crear(array $datos, int $chefId): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones,
                                 dificultad, categoria_id, imagen, calorias, chef_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $datos['titulo'],
            $datos['descripcion'],
            (int)$datos['tiempo'],
            (int)($datos['porciones'] ?? 4),
            $datos['dificultad'],
            $datos['categoria_id'] ?? ($datos['categoria'] ?? null),
            $datos['imagen'] ?? null,
            $datos['calorias'] ?? null,
            $chefId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // Guardar ingredientes de una receta
    public function guardarIngredientes(int $recetaId, array $ingredientes): void {
        $this->pdo->prepare("DELETE FROM ingredientes WHERE receta_id = ?")->execute([$recetaId]);
        $stmt = $this->pdo->prepare("INSERT INTO ingredientes (receta_id, nombre, cantidad, unidad, orden) VALUES (?,?,?,?,?)");
        foreach ($ingredientes as $i => $ing) {
            if (empty(trim($ing['nombre']))) continue;
            $stmt->execute([$recetaId, trim($ing['nombre']), $ing['cantidad'] ?? null, $ing['unidad'] ?? null, $i + 1]);
        }
    }

    // Guardar pasos de una receta
    public function guardarPasos(int $recetaId, array $pasos): void {
        $this->pdo->prepare("DELETE FROM pasos WHERE receta_id = ?")->execute([$recetaId]);
        $stmt = $this->pdo->prepare("INSERT INTO pasos (receta_id, numero, descripcion) VALUES (?,?,?)");
        foreach ($pasos as $i => $paso) {
            if (empty(trim($paso))) continue;
            $stmt->execute([$recetaId, $i + 1, trim($paso)]);
        }
    }

    // Toggle favorito
    public function toggleFavorito(int $userId, int $recetaId): array {
        $stmt = $this->pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
        $stmt->execute([$userId, $recetaId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND receta_id = ?")->execute([$userId, $recetaId]);
            return ['activo' => false];
        }
        $this->pdo->prepare("INSERT INTO favoritos (usuario_id, receta_id) VALUES (?,?)")->execute([$userId, $recetaId]);
        return ['activo' => true];
    }

    // Verificar si es favorito
    public function esFavorito(int $userId, int $recetaId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
        $stmt->execute([$userId, $recetaId]);
        return (bool)$stmt->fetch();
    }

    // Favoritos del usuario
    public function favoritosDeUsuario(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, ROUND(AVG(v.puntuacion),1) AS rating
            FROM favoritos f
            JOIN recetas r     ON r.id = f.receta_id
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            WHERE f.usuario_id = ?
            GROUP BY r.id ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Valorar receta
    public function valorar(int $userId, int $recetaId, int $puntuacion, string $comentario): array {
        $stmt = $this->pdo->prepare("SELECT id FROM valoraciones WHERE usuario_id = ? AND receta_id = ?");
        $stmt->execute([$userId, $recetaId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("UPDATE valoraciones SET puntuacion=?, comentario=? WHERE usuario_id=? AND receta_id=?")
                      ->execute([$puntuacion, $comentario, $userId, $recetaId]);
        } else {
            $this->pdo->prepare("INSERT INTO valoraciones (receta_id, usuario_id, puntuacion, comentario) VALUES (?,?,?,?)")
                      ->execute([$recetaId, $userId, $puntuacion, $comentario]);
        }
        return ['exito' => true];
    }

    public function comentar(int $userId, int $recetaId, string $contenido): array {
        if (trim($contenido) === '') {
            return ['error' => 'El comentario no puede estar vacío'];
        }
        $this->pdo->prepare("INSERT INTO comentarios (receta_id, usuario_id, contenido) VALUES (?,?,?)")
            ->execute([$recetaId, $userId, trim($contenido)]);
        return ['exito' => true];
    }

    public function seguirChef(int $userId, int $chefId): array {
        if ($userId === $chefId) {
            return ['error' => 'No puedes seguirte a ti mismo'];
        }
        $stmt = $this->pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND chef_id = ?");
        $stmt->execute([$userId, $chefId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND chef_id = ?")->execute([$userId, $chefId]);
            return ['activo' => false];
        }
        $this->pdo->prepare("INSERT INTO seguidores (seguidor_id, chef_id) VALUES (?, ?)")->execute([$userId, $chefId]);
        return ['activo' => true];
    }

    public function esSeguidor(int $userId, int $chefId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND chef_id = ?");
        $stmt->execute([$userId, $chefId]);
        return (bool)$stmt->fetch();
    }

    public function valorarChef(int $userId, int $chefId, int $puntuacion, string $comentario): array {
        if ($userId === $chefId) {
            return ['error' => 'No puedes valorarte a ti mismo'];
        }
        $stmt = $this->pdo->prepare("SELECT id FROM valoraciones_chef WHERE usuario_id = ? AND chef_id = ?");
        $stmt->execute([$userId, $chefId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("UPDATE valoraciones_chef SET puntuacion=?, comentario=? WHERE usuario_id=? AND chef_id=?")
                ->execute([$puntuacion, $comentario, $userId, $chefId]);
        } else {
            $this->pdo->prepare("INSERT INTO valoraciones_chef (chef_id, usuario_id, puntuacion, comentario) VALUES (?,?,?,?)")
                ->execute([$chefId, $userId, $puntuacion, $comentario]);
        }
        return ['exito' => true];
    }

    public function reportar(int $userId, string $tipo, int $objetivoId, string $motivo, string $descripcion): array {
        if (trim($motivo) === '') {
            return ['error' => 'Selecciona un motivo'];
        }
        $this->pdo->prepare("INSERT INTO reportes (tipo, objetivo_id, usuario_id, motivo, descripcion) VALUES (?,?,?,?,?)")
            ->execute([$tipo, $objetivoId, $userId, $motivo, trim($descripcion)]);
        return ['exito' => true];
    }

    // Eliminar receta
    public function eliminar(int $recetaId, int $userId, string $rol): array {
        $stmt = $this->pdo->prepare("SELECT chef_id FROM recetas WHERE id = ?");
        $stmt->execute([$recetaId]);
        $receta = $stmt->fetch();
        if (!$receta) return ['error' => 'Receta no encontrada'];
        if ($rol !== 'admin' && (int)$receta['chef_id'] !== $userId) return ['error' => 'Sin permiso'];
        $this->pdo->prepare("DELETE FROM recetas WHERE id = ?")->execute([$recetaId]);
        return ['exito' => true];
    }

    // Stats para dashboards
    public function stats(): array {
        return [
            'total'      => (int)$this->pdo->query("SELECT COUNT(*) FROM recetas")->fetchColumn(),
            'publicadas' => (int)$this->pdo->query("SELECT COUNT(*) FROM recetas WHERE publicada=1")->fetchColumn(),
            'categorias' => (int)$this->pdo->query("SELECT COUNT(DISTINCT categoria_id) FROM recetas")->fetchColumn(),
        ];
    }

    // Stats extendidas para admin
    public function statsAdmin(): array {
        $base = $this->stats();
        $base['total_visitas']    = (int)$this->pdo->query("SELECT COALESCE(SUM(visitas),0) FROM recetas")->fetchColumn();
        $base['total_comentarios']= (int)$this->pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
        $base['total_valoraciones']=(int)$this->pdo->query("SELECT COUNT(*) FROM valoraciones")->fetchColumn();
        $base['rating_global']    = round((float)$this->pdo->query("SELECT COALESCE(AVG(puntuacion),0) FROM valoraciones")->fetchColumn(), 1);
        return $base;
    }

    // Recetas más vistas (top N)
    public function masVistas(int $limite = 8): array {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.titulo, r.categoria_id, r.visitas, r.dificultad,
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating,
                   COUNT(DISTINCT f.usuario_id) AS total_favoritos
            FROM recetas r
            LEFT JOIN usuarios u    ON u.id = r.chef_id
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            LEFT JOIN favoritos f    ON f.receta_id = r.id
            GROUP BY r.id
            ORDER BY r.visitas DESC
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Top categorías por número de recetas
    public function topCategorias(int $limite = 6): array {
        $stmt = $this->pdo->prepare("
            SELECT c.nombre, c.icono, c.color, c.bg,
                   COUNT(r.id) AS total_recetas,
                   COALESCE(SUM(r.visitas), 0) AS total_visitas
            FROM categorias c
            LEFT JOIN recetas r ON r.categoria_id = c.nombre
            GROUP BY c.id
            ORDER BY total_recetas DESC
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    // Visitas totales de las recetas de un chef
    public function visitasPorChef(int $chefId): int {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(visitas),0) FROM recetas WHERE chef_id = ?");
        $stmt->execute([$chefId]);
        return (int)$stmt->fetchColumn();
    }

    // Comentarios recibidos en las recetas de un chef
    public function comentariosPorChef(int $chefId): array {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.contenido, c.created_at,
                   u.nombre AS autor, u.avatar AS autor_avatar,
                   r.titulo AS receta_titulo, r.id AS receta_id
            FROM comentarios c
            JOIN recetas  r ON r.id = c.receta_id
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE r.chef_id = ?
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    // Total de comentarios de las recetas de un chef
    public function totalComentariosPorChef(int $chefId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM comentarios c
            JOIN recetas r ON r.id = c.receta_id
            WHERE r.chef_id = ?
        ");
        $stmt->execute([$chefId]);
        return (int)$stmt->fetchColumn();
    }

    // Recetas del chef con visitas incluidas
    public function porChefConVisitas(int $chefId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.id) AS total_valoraciones,
                   COUNT(DISTINCT f.usuario_id) AS total_favoritos,
                   COUNT(DISTINCT c.id) AS total_comentarios
            FROM recetas r
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            LEFT JOIN favoritos f    ON f.receta_id = r.id
            LEFT JOIN comentarios c  ON c.receta_id = r.id
            WHERE r.chef_id = ?
            GROUP BY r.id
            ORDER BY r.visitas DESC, r.created_at DESC
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    // Datos para reportes admin con filtros opcionales
    public function reporte(array $filtros = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]  = 'r.created_at >= ?';
            $params[] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 'r.created_at <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }
        if (!empty($filtros['categoria'])) {
            $where[]  = 'r.categoria_id = ?';
            $params[] = $filtros['categoria'];
        }
        if (!empty($filtros['dificultad'])) {
            $where[]  = 'r.dificultad = ?';
            $params[] = $filtros['dificultad'];
        }
        if (isset($filtros['destacada']) && $filtros['destacada'] !== '') {
            $where[]  = 'r.destacada = ?';
            $params[] = (int)$filtros['destacada'];
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT r.id, r.titulo, r.categoria_id, r.dificultad,
                   r.tiempo_preparacion, r.visitas, r.destacada,
                   r.created_at,
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating,
                   COUNT(DISTINCT v.id)  AS total_valoraciones,
                   COUNT(DISTINCT c.id)  AS total_comentarios,
                   COUNT(DISTINCT f.usuario_id) AS total_favoritos
            FROM recetas r
            LEFT JOIN usuarios u     ON u.id = r.chef_id
            LEFT JOIN valoraciones v ON v.receta_id = r.id
            LEFT JOIN comentarios c  ON c.receta_id = r.id
            LEFT JOIN favoritos f    ON f.receta_id = r.id
            WHERE $whereSQL
            GROUP BY r.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Reporte de comentarios con filtros opcionales
    public function reporteComentarios(array $filtros = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]  = 'c.created_at >= ?';
            $params[] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 'c.created_at <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }
        if (!empty($filtros['categoria'])) {
            $where[]  = 'r.categoria = ?';
            $params[] = $filtros['categoria'];
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT c.id, c.contenido, c.created_at,
                   u.nombre AS autor,
                   r.id AS receta_id, r.titulo AS receta_titulo, r.categoria_id,
                   ch.nombre AS chef_nombre
            FROM comentarios c
            JOIN recetas  r  ON r.id  = c.receta_id
            JOIN usuarios u  ON u.id  = c.usuario_id
            JOIN usuarios ch ON ch.id = r.chef_id
            WHERE $whereSQL
            ORDER BY c.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Métodos del chatbot (sin cambios)
    public function buscar(string $termino): array {
        $like = '%' . $termino . '%';
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT r.id, r.titulo, r.descripcion, r.tiempo_preparacion, r.dificultad, r.categoria_id, r.imagen
            FROM recetas r LEFT JOIN ingredientes i ON i.receta_id = r.id
            WHERE r.titulo LIKE ? OR r.descripcion LIKE ? OR r.categoria_id LIKE ? OR i.nombre LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function porCategoria(string $categoria): array {
        $stmt = $this->pdo->prepare("SELECT id, titulo, tiempo_preparacion, dificultad FROM recetas WHERE categoria_id LIKE ? LIMIT 6");
        $stmt->execute(['%' . $categoria . '%']);
        return $stmt->fetchAll();
    }

    public function porPais(string $pais): array {
        $stmt = $this->pdo->prepare("SELECT id, titulo, tiempo_preparacion, dificultad, categoria_id FROM recetas WHERE pais = ? ORDER BY destacada DESC, titulo ASC LIMIT 6");
        $stmt->execute([$pais]);
        return $stmt->fetchAll();
    }

    public function porTiempo(int $minutos): array {
        $stmt = $this->pdo->prepare("SELECT id, titulo, tiempo_preparacion, dificultad, categoria_id FROM recetas WHERE tiempo_preparacion <= ? ORDER BY tiempo_preparacion ASC LIMIT 5");
        $stmt->execute([$minutos]);
        return $stmt->fetchAll();
    }

    public function porDificultad(string $dificultad): array {
        $stmt = $this->pdo->prepare("SELECT id, titulo, tiempo_preparacion, categoria_id FROM recetas WHERE dificultad LIKE ? LIMIT 6");
        $stmt->execute(['%' . $dificultad . '%']);
        return $stmt->fetchAll();
    }

    public function listarTodas(): array {
        $stmt = $this->pdo->query("SELECT id, titulo, categoria_id, tiempo_preparacion, dificultad FROM recetas ORDER BY titulo ASC LIMIT 20");
        return $stmt->fetchAll();
    }

    public function categorias(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT categoria_id FROM recetas WHERE categoria_id IS NOT NULL ORDER BY categoria_id");
        return array_column($stmt->fetchAll(), 'categoria');
    }
}
