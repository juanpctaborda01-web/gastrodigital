<?php
require_once __DIR__ . '/../config/conexion.php';

class Receta {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    // ── Helper: detectar PK real de una tabla ──────────────────────────────
    private function pk(string $tabla): string {
        static $cache = [];
        if (!isset($cache[$tabla])) {
            try {
                $r = $this->pdo->query("SHOW KEYS FROM `$tabla` WHERE Key_name='PRIMARY'")->fetch(PDO::FETCH_ASSOC);
                $cache[$tabla] = $r ? $r['Column_name'] : 'id';
            } catch (\Throwable $e) {
                // Fallback: intentar con nombres comunes
                $common = [
                    'recetas' => 'id_receta',
                    'comentarios' => 'id_comentario',
                    'valoraciones' => 'id_valoracion',
                    'usuarios' => 'id_usuario',
                    'categorias' => 'id_categoria',
                    'paises' => 'id_pais',
                    'favoritos' => 'id'
                ];
                $cache[$tabla] = $common[$tabla] ?? 'id';
            }
        }
        return $cache[$tabla];
    }

    // ── Helper: alias seguros ──────────────────────────────────────────────
    // Devuelve si una columna existe en la tabla
    private function colExists(string $tabla, string $col): bool {
        static $cols = [];
        if (!isset($cols[$tabla])) {
            try {
                $rows = $this->pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll();
                $cols[$tabla] = array_column($rows, 'Field');
            } catch (\Throwable $e) {
                $cols[$tabla] = [];
            }
        }
        return in_array($col, $cols[$tabla], true);
    }

    // Listar recetas con filtros opcionales + paginación
    public function listarFiltradas(array $filtros = [], int $pagina = 1, int $porPagina = 9): array {
        // Detectar PKs reales
        $pkR   = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $pkP   = $this->pk('paises');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');

        // FK de ingredientes hacia recetas
        $fkIngReceta = $this->colExists('ingredientes', 'id_receta') ? 'id_receta' : $pkR;
        // FK de recetas hacia categoría/pais/chef
        $fkRecCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : $pkCat;
        $fkRecPais = $this->colExists('recetas', 'id_pais')      ? 'id_pais'      : $pkP;
        $fkRecChef = $this->colExists('recetas', 'id_chef')      ? 'id_chef'      : $pkU;
        $fkValRec  = $this->colExists('valoraciones', 'id_receta') ? 'id_receta'  : $pkR;
        $fkPaisIso = $this->colExists('paises', 'codigo_iso')    ? 'codigo_iso'   : '';

        $where  = ['r.publicada = 1'];
        $params = [];

        if (!empty($filtros['destacada'])) {
            $where[] = 'r.destacada = 1';
        }
        if (!empty($filtros['q'])) {
            $where[] = '(r.titulo LIKE ? OR r.descripcion LIKE ? OR i.nombre LIKE ?)';
            $like    = '%' . $filtros['q'] . '%';
            $params  = array_merge($params, [$like, $like, $like]);
        }
        if (!empty($filtros['categoria'])) {
            // Busca por nombre exacto en tabla categorías
            $where[]  = "c_lf.nombre = ?";
            $params[] = $filtros['categoria'];
        }
        if (!empty($filtros['id_pais'])) {
            // Por ID de país (legacy)
            $where[]  = "r.$fkRecPais = ?";
            $params[] = $filtros['id_pais'];
        }
        if (!empty($filtros['pais'])) {
            // Por nombre de país
            $where[]  = "p_lf.nombre = ?";
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

        $isoCol = $fkPaisIso ? "p_lf.$fkPaisIso AS pais_iso," : "'' AS pais_iso,";

        $countSQL = "SELECT COUNT(DISTINCT r.$pkR)
                     FROM recetas r
                     LEFT JOIN ingredientes i ON i.$fkIngReceta = r.$pkR
                     LEFT JOIN categorias c_lf ON c_lf.$pkCat = r.$fkRecCat
                     $whereSQL";
        $stmt = $this->pdo->prepare($countSQL);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT DISTINCT
                       r.$pkR AS id, r.titulo, r.descripcion, r.tiempo_preparacion,
                       r.dificultad, r.$fkRecCat AS id_categoria, r.$fkRecPais AS id_pais,
                       r.imagen, r.calorias, r.porciones, r.destacada, r.visitas,
                       c_lf.nombre AS categoria, c_lf.color AS cat_color, c_lf.icono AS cat_icono,
                       $isoCol
                       p_lf.nombre AS pais_nombre,
                       u.nombre AS chef_nombre,
                       ROUND(AVG(v.puntuacion), 1) AS rating,
                       COUNT(DISTINCT v.$pkV) AS total_valoraciones
                FROM recetas r
                LEFT JOIN ingredientes i ON i.$fkIngReceta = r.$pkR
                LEFT JOIN categorias c_lf ON c_lf.$pkCat = r.$fkRecCat
                LEFT JOIN paises p_lf     ON p_lf.$pkP = r.$fkRecPais
                LEFT JOIN usuarios u      ON u.$pkU = r.$fkRecChef
                LEFT JOIN valoraciones v  ON v.$fkValRec = r.$pkR
                $whereSQL
                GROUP BY r.$pkR
                ORDER BY r.destacada DESC, r.created_at DESC
                LIMIT $porPagina OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'recetas' => $stmt->fetchAll(),
            'total'   => $total,
            'paginas' => (int)ceil($total / $porPagina),
            'pagina'  => $pagina,
        ];
    }

    // Listar países distintos desde recetas con nombres
    public function listarPaises(): array {
        try {
            // Intenta JOIN con tabla paises
            $stmt = $this->pdo->query("
                SELECT DISTINCT p.nombre
                FROM recetas r
                JOIN paises p ON p.id_pais = r.id_pais
                WHERE r.id_pais IS NOT NULL AND r.publicada = 1
                ORDER BY p.nombre ASC
            ");
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (\Throwable $e1) {
            // Fallback 1: con PK = id
            try {
                $stmt = $this->pdo->query("
                    SELECT DISTINCT p.nombre
                    FROM recetas r
                    JOIN paises p ON p.id = r.id_pais
                    WHERE r.id_pais IS NOT NULL AND r.publicada = 1
                    ORDER BY p.nombre ASC
                ");
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                return $result ?: [];
            } catch (\Throwable $e2) {
                // Fallback 2: devolver IDs únicos de recetas
                try {
                    $stmt = $this->pdo->query("
                        SELECT DISTINCT id_pais
                        FROM recetas
                        WHERE id_pais IS NOT NULL AND id_pais <> '' AND publicada = 1
                        ORDER BY id_pais ASC
                    ");
                    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                } catch (\Throwable $e3) {
                    return [];
                }
            }
        }
    }

    // Obtener receta para edición
    public function obtenerParaEditar(int $id, int $chefId): ?array {
        $pkR   = $this->pk('recetas');
        $fkChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $stmt = $this->pdo->prepare("SELECT * FROM recetas WHERE $pkR = ? AND $fkChef = ?");
        $stmt->execute([$id, $chefId]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE id_receta = ? ORDER BY orden, id");
        $stmt2->execute([$id]);
        $receta['ingredientes'] = $stmt2->fetchAll();

        $stmt3 = $this->pdo->prepare("SELECT * FROM pasos WHERE id_receta = ? ORDER BY numero");
        $stmt3->execute([$id]);
        $receta['pasos'] = $stmt3->fetchAll();

        return $receta;
    }

    // Actualizar receta existente
    public function actualizar(int $id, array $datos): bool {
        $pkR  = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $idCategoria = !empty($datos['id_categoria']) ? (int)$datos['id_categoria'] : null;
        if (!$idCategoria && !empty($datos['categoria'])) {
            $stmtCat = $this->pdo->prepare("SELECT $pkCat FROM categorias WHERE nombre = ? LIMIT 1");
            $stmtCat->execute([$datos['categoria']]);
            $idCategoria = $stmtCat->fetchColumn() ?: null;
        }
        $imgPart = !empty($datos['imagen']) ? ", imagen = ?" : "";
        $fkChef  = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $stmt = $this->pdo->prepare("
            UPDATE recetas
            SET titulo = ?, descripcion = ?, tiempo_preparacion = ?, porciones = ?,
                dificultad = ?, id_categoria = ?, calorias = ?
                $imgPart
            WHERE $pkR = ? AND $fkChef = ?
        ");
        $params = [
            $datos['titulo'], $datos['descripcion'], (int)$datos['tiempo'],
            (int)($datos['porciones'] ?? 4), $datos['dificultad'], $idCategoria,
            $datos['calorias'] ?? null,
        ];
        if (!empty($datos['imagen'])) $params[] = $datos['imagen'];
        $params[] = $id;
        $params[] = (int)$datos['id_chef'];
        $stmt->execute($params);
        return true;
    }

    // Obtener receta completa con ingredientes, pasos, comentarios
    public function obtenerCompleta(int $id): ?array {
        $pkR   = $this->pk('recetas');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $pkCat = $this->pk('categorias');
        $fkValRec = $this->colExists('valoraciones', 'id_receta') ? 'id_receta' : $pkR;
        $fkRecChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $fkRecCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'id_categoria';

        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   u.nombre AS chef_nombre, u.bio AS chef_bio,
                   u.$pkU AS chef_id,
                   c_cat.nombre AS categoria,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones
            FROM recetas r
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkRecChef
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            WHERE r.$pkR = ?
            GROUP BY r.$pkR
        ");
        $stmt->execute([$id]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE id_receta = ? ORDER BY orden, id");
        $stmt2->execute([$id]);
        $receta['ingredientes'] = $stmt2->fetchAll();

        $stmt3 = $this->pdo->prepare("SELECT * FROM pasos WHERE id_receta = ? ORDER BY numero");
        $stmt3->execute([$id]);
        $receta['pasos'] = $stmt3->fetchAll();

        $pkCom = $this->pk('comentarios');
        $fkComRec = $this->colExists('comentarios', 'id_receta') ? 'id_receta' : $pkR;
        $fkComUsr = $this->colExists('comentarios', 'id_usuario') ? 'id_usuario' : $pkU;
        $stmt4 = $this->pdo->prepare("
            SELECT c.*, c.$pkCom AS id_comentario, u.nombre AS autor
            FROM comentarios c JOIN usuarios u ON u.$pkU = c.$fkComUsr
            WHERE c.$fkComRec = ? ORDER BY c.created_at ASC
        ");
        $stmt4->execute([$id]);
        $receta['comentarios'] = $stmt4->fetchAll();

        // Incrementar visitas
        $this->pdo->prepare("UPDATE recetas SET visitas = visitas + 1 WHERE $pkR = ?")->execute([$id]);
        return $receta;
    }

    // Recetas del chef logueado
    public function porChef(int $chefId): array {
        $pkR   = $this->pk('recetas');
        $pkV   = $this->pk('valoraciones');
        $pkF   = $this->pk('favoritos');
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta' : $pkR;
        $fkFavRec = $this->colExists('favoritos','id_receta')    ? 'id_receta' : $pkR;
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'   : 'id_chef';
        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones,
                   COUNT(DISTINCT f.$pkF) AS total_favoritos
            FROM recetas r
            LEFT JOIN valoraciones v ON v.$fkValRec = r.$pkR
            LEFT JOIN favoritos f    ON f.$fkFavRec = r.$pkR
            WHERE r.$fkChef = ?
            GROUP BY r.$pkR ORDER BY r.created_at DESC
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    // Crear nueva receta
    public function crear(array $datos, int $chefId): int {
        $pkCat = $this->pk('categorias');
        $idCategoria = !empty($datos['id_categoria']) ? (int)$datos['id_categoria'] : null;
        if (!$idCategoria && !empty($datos['categoria'])) {
            $stmtCat = $this->pdo->prepare("SELECT $pkCat FROM categorias WHERE nombre = ? LIMIT 1");
            $stmtCat->execute([$datos['categoria']]);
            $idCategoria = $stmtCat->fetchColumn() ?: null;
        }
        $stmt = $this->pdo->prepare("
            INSERT INTO recetas (titulo, descripcion, tiempo_preparacion, porciones,
                                 dificultad, id_categoria, imagen, calorias, id_chef, publicada, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $datos['titulo'], $datos['descripcion'] ?? '',
            (int)$datos['tiempo'], (int)($datos['porciones'] ?? 4),
            $datos['dificultad'] ?? 'Fácil', $idCategoria,
            $datos['imagen'] ?? null, $datos['calorias'] ?? null, $chefId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function guardarIngredientes(int $recetaId, array $ingredientes): void {
        $this->pdo->prepare("DELETE FROM ingredientes WHERE id_receta = ?")->execute([$recetaId]);
        $stmt = $this->pdo->prepare("INSERT INTO ingredientes (id_receta, nombre, cantidad, unidad, orden) VALUES (?,?,?,?,?)");
        foreach ($ingredientes as $i => $ing) {
            if (empty(trim($ing['nombre']))) continue;
            $stmt->execute([$recetaId, trim($ing['nombre']), $ing['cantidad'] ?? null, $ing['unidad'] ?? null, $i + 1]);
        }
    }

    public function guardarPasos(int $recetaId, array $pasos): void {
        $this->pdo->prepare("DELETE FROM pasos WHERE id_receta = ?")->execute([$recetaId]);
        $stmt = $this->pdo->prepare("INSERT INTO pasos (id_receta, numero, descripcion) VALUES (?,?,?)");
        foreach ($pasos as $i => $paso) {
            if (empty(trim($paso))) continue;
            $stmt->execute([$recetaId, $i + 1, trim($paso)]);
        }
    }

    public function toggleFavorito(int $userId, int $recetaId): array {
        $stmt = $this->pdo->prepare("SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_receta = ?");
        $stmt->execute([$userId, $recetaId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_receta = ?")->execute([$userId, $recetaId]);
            return ['activo' => false];
        }
        $this->pdo->prepare("INSERT INTO favoritos (id_usuario, id_receta) VALUES (?,?)")->execute([$userId, $recetaId]);
        return ['activo' => true];
    }

    public function esFavorito(int $userId, int $recetaId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_receta = ?");
        $stmt->execute([$userId, $recetaId]);
        return (bool)$stmt->fetch();
    }

    public function favoritosDeUsuario(int $userId): array {
        $pkR   = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $pkP   = $this->pk('paises');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $fkValRec  = $this->colExists('valoraciones','id_receta')  ? 'id_receta'  : $pkR;
        $fkFavRec  = $this->colExists('favoritos','id_receta')     ? 'id_receta'  : $pkR;
        $fkFavUsr  = $this->colExists('favoritos','id_usuario')    ? 'id_usuario' : 'id_usuario';
        $fkRecCat  = $this->colExists('recetas','id_categoria')    ? 'id_categoria': 'id_categoria';
        $fkRecPais = $this->colExists('recetas','id_pais')         ? 'id_pais'    : 'id_pais';
        $fkRecChef = $this->colExists('recetas','id_chef')         ? 'id_chef'    : 'id_chef';
        $isoCol    = $this->colExists('paises','codigo_iso')       ? "p_f.codigo_iso AS pais_iso," : "'' AS pais_iso,";

        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   c_cat.nombre AS categoria, c_cat.color AS cat_color, c_cat.icono AS cat_icono,
                   $isoCol
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating
            FROM favoritos f
            JOIN recetas r          ON r.$pkR  = f.$fkFavRec
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p_f       ON p_f.$pkP = r.$fkRecPais
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkRecChef
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            WHERE f.$fkFavUsr = ?
            GROUP BY r.$pkR ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function valorar(int $userId, int $recetaId, int $puntuacion, string $comentario): array {
        $pkV = $this->pk('valoraciones');
        $stmt = $this->pdo->prepare("SELECT $pkV FROM valoraciones WHERE id_usuario = ? AND id_receta = ?");
        $stmt->execute([$userId, $recetaId]);
        if ($stmt->fetch()) {
            $this->pdo->prepare("UPDATE valoraciones SET puntuacion=?, comentario=? WHERE id_usuario=? AND id_receta=?")
                      ->execute([$puntuacion, $comentario, $userId, $recetaId]);
        } else {
            $this->pdo->prepare("INSERT INTO valoraciones (id_receta, id_usuario, puntuacion, comentario) VALUES (?,?,?,?)")
                      ->execute([$recetaId, $userId, $puntuacion, $comentario]);
        }
        return ['exito' => true];
    }

    public function comentar(int $userId, int $recetaId, string $contenido): array {
        if (trim($contenido) === '') return ['error' => 'El comentario no puede estar vacío'];
        $this->pdo->prepare("INSERT INTO comentarios (id_receta, id_usuario, contenido) VALUES (?,?,?)")
            ->execute([$recetaId, $userId, trim($contenido)]);
        return ['exito' => true];
    }

    public function reportar(int $userId, string $tipo, int $objetivoId, string $motivo, string $descripcion): array {
        if (trim($motivo) === '') return ['error' => 'Selecciona un motivo'];
        $idReceta     = ($tipo === 'receta')     ? $objetivoId : null;
        $idComentario = ($tipo === 'comentario') ? $objetivoId : null;
        $idUsuario    = ($tipo === 'usuario')    ? $objetivoId : null;
        $idValoracion = ($tipo === 'valoracion') ? $objetivoId : null;
        $this->pdo->prepare("
            INSERT INTO reportes (id_receta,id_comentario,id_usuario,id_valoracion,reportado_por,motivo,descripcion,estado,created_at)
            VALUES (?,?,?,?,?,?,?,'pendiente',NOW())
        ")->execute([$idReceta,$idComentario,$idUsuario,$idValoracion,$userId,$motivo,trim($descripcion)]);
        return ['exito' => true];
    }

    public function eliminar(int $recetaId, int $userId, string $rol = 'chef'): array {
        $pkR    = $this->pk('recetas');
        $fkChef = $this->colExists('recetas','id_chef') ? 'id_chef' : 'id_chef';
        $stmt   = $this->pdo->prepare("SELECT $fkChef FROM recetas WHERE $pkR = ?");
        $stmt->execute([$recetaId]);
        $receta = $stmt->fetch();
        if (!$receta)                                          return ['error' => 'Receta no encontrada'];
        if ($rol !== 'admin' && (int)$receta[$fkChef] !== $userId) return ['error' => 'Sin permiso'];

        $this->pdo->prepare("DELETE FROM ingredientes WHERE id_receta = ?")->execute([$recetaId]);
        $this->pdo->prepare("DELETE FROM pasos       WHERE id_receta = ?")->execute([$recetaId]);
        $this->pdo->prepare("DELETE FROM favoritos   WHERE id_receta = ?")->execute([$recetaId]);
        $this->pdo->prepare("DELETE FROM comentarios WHERE id_receta = ?")->execute([$recetaId]);
        $this->pdo->prepare("DELETE FROM valoraciones WHERE id_receta = ?")->execute([$recetaId]);
        $this->pdo->prepare("DELETE FROM recetas WHERE $pkR = ?")->execute([$recetaId]);
        return ['exito' => true];
    }

    public function stats(): array {
        return [
            'total'      => (int)$this->pdo->query("SELECT COUNT(*) FROM recetas")->fetchColumn(),
            'publicadas' => (int)$this->pdo->query("SELECT COUNT(*) FROM recetas WHERE publicada=1")->fetchColumn(),
            'categorias' => (int)$this->pdo->query("SELECT COUNT(DISTINCT id_categoria) FROM recetas")->fetchColumn(),
        ];
    }

    public function statsAdmin(): array {
        $base = $this->stats();
        $base['total_visitas']     = (int)$this->pdo->query("SELECT COALESCE(SUM(visitas),0) FROM recetas")->fetchColumn();
        $base['total_comentarios'] = (int)$this->pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
        $base['total_valoraciones']= (int)$this->pdo->query("SELECT COUNT(*) FROM valoraciones")->fetchColumn();
        $base['rating_global']     = round((float)$this->pdo->query("SELECT COALESCE(AVG(puntuacion),0) FROM valoraciones")->fetchColumn(), 1);
        return $base;
    }

    public function masVistas(int $limite = 8): array {
        $pkR   = $this->pk('recetas');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $pkF   = $this->pk('favoritos');
        $pkCat = $this->pk('categorias');
        $fkChef    = $this->colExists('recetas','id_chef')       ? 'id_chef'    : 'id_chef';
        $fkValRec  = $this->colExists('valoraciones','id_receta')? 'id_receta'  : $pkR;
        $fkFavRec  = $this->colExists('favoritos','id_receta')   ? 'id_receta'  : $pkR;
        $fkRecCat  = $this->colExists('recetas','id_categoria')  ? 'id_categoria':'id_categoria';
        $stmt = $this->pdo->prepare("
            SELECT r.$pkR AS id, r.titulo, r.dificultad, r.visitas,
                   c_cat.nombre AS categoria,
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating,
                   COUNT(DISTINCT f.$pkF) AS total_favoritos
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkChef
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            LEFT JOIN favoritos f      ON f.$fkFavRec = r.$pkR
            GROUP BY r.$pkR ORDER BY r.visitas DESC LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function topCategorias(int $limite = 6): array {
        $pkCat = $this->pk('categorias');
        $pkR   = $this->pk('recetas');
        $fkRecCat = $this->colExists('recetas','id_categoria') ? 'id_categoria' : 'id_categoria';
        $stmt = $this->pdo->prepare("
            SELECT c.$pkCat AS id, c.nombre, c.icono, c.color, c.bg,
                   COUNT(r.$pkR) AS total_recetas,
                   COALESCE(SUM(r.visitas), 0) AS total_visitas
            FROM categorias c
            LEFT JOIN recetas r ON r.$fkRecCat = c.$pkCat
            GROUP BY c.$pkCat, c.nombre, c.icono, c.color, c.bg
            ORDER BY total_recetas DESC LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function visitasPorChef(int $chefId): int {
        $fkChef = $this->colExists('recetas','id_chef') ? 'id_chef' : 'id_chef';
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(visitas),0) FROM recetas WHERE $fkChef = ?");
        $stmt->execute([$chefId]);
        return (int)$stmt->fetchColumn();
    }

    public function comentariosPorChef(int $chefId): array {
        $pkR   = $this->pk('recetas');
        $pkCom = $this->pk('comentarios');
        $pkU   = $this->pk('usuarios');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'   : 'id_chef';
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta' : $pkR;
        $fkComUsr = $this->colExists('comentarios','id_usuario') ? 'id_usuario': $pkU;
        $stmt = $this->pdo->prepare("
            SELECT c.$pkCom AS id_comentario, c.contenido, c.created_at,
                   u.nombre AS autor, r.titulo AS receta_titulo, r.$pkR AS id_receta
            FROM comentarios c
            JOIN recetas  r ON r.$pkR = c.$fkComRec
            JOIN usuarios u ON u.$pkU = c.$fkComUsr
            WHERE r.$fkChef = ?
            ORDER BY c.created_at DESC LIMIT 20
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    public function totalComentariosPorChef(int $chefId): int {
        $pkR   = $this->pk('recetas');
        $pkCom = $this->pk('comentarios');
        $fkChef   = $this->colExists('recetas','id_chef')       ? 'id_chef'   : 'id_chef';
        $fkComRec = $this->colExists('comentarios','id_receta') ? 'id_receta' : $pkR;
        $stmt = $this->pdo->prepare("
            SELECT COUNT(c.$pkCom) FROM comentarios c
            JOIN recetas r ON r.$pkR = c.$fkComRec WHERE r.$fkChef = ?
        ");
        $stmt->execute([$chefId]);
        return (int)$stmt->fetchColumn();
    }

    public function porChefConVisitas(int $chefId): array {
        $pkR   = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $pkV   = $this->pk('valoraciones');
        $pkF   = $this->pk('favoritos');
        $pkCom = $this->pk('comentarios');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'    : 'id_chef';
        $fkRecCat = $this->colExists('recetas','id_categoria')   ? 'id_categoria':'id_categoria';
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta'  : $pkR;
        $fkFavRec = $this->colExists('favoritos','id_receta')    ? 'id_receta'  : $pkR;
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta'  : $pkR;
        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   c_cat.nombre AS categoria,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones,
                   COUNT(DISTINCT f.$pkF) AS total_favoritos,
                   COUNT(DISTINCT c.$pkCom) AS total_comentarios
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            LEFT JOIN favoritos f      ON f.$fkFavRec = r.$pkR
            LEFT JOIN comentarios c    ON c.$fkComRec = r.$pkR
            WHERE r.$fkChef = ?
            GROUP BY r.$pkR ORDER BY r.visitas DESC, r.created_at DESC
        ");
        $stmt->execute([$chefId]);
        return $stmt->fetchAll();
    }

    public function reporte(array $filtros = []): array {
        $pkR   = $this->pk('recetas');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $pkF   = $this->pk('favoritos');
        $pkCom = $this->pk('comentarios');
        $pkCat = $this->pk('categorias');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'    : 'id_chef';
        $fkRecCat = $this->colExists('recetas','id_categoria')   ? 'id_categoria':'id_categoria';
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta'  : $pkR;
        $fkFavRec = $this->colExists('favoritos','id_receta')    ? 'id_receta'  : $pkR;
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta'  : $pkR;
        $where  = ['1=1']; $params = [];
        if (!empty($filtros['desde']))    { $where[] = 'r.created_at >= ?'; $params[] = $filtros['desde'] . ' 00:00:00'; }
        if (!empty($filtros['hasta']))    { $where[] = 'r.created_at <= ?'; $params[] = $filtros['hasta'] . ' 23:59:59'; }
        if (!empty($filtros['categoria'])){ $where[] = "r.$fkRecCat = ?";   $params[] = $filtros['categoria']; }
        if (!empty($filtros['dificultad'])){ $where[] = 'r.dificultad = ?'; $params[] = $filtros['dificultad']; }
        if (isset($filtros['destacada']) && $filtros['destacada'] !== '') { $where[] = 'r.destacada = ?'; $params[] = (int)$filtros['destacada']; }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->pdo->prepare("
            SELECT r.$pkR AS id, r.titulo, r.dificultad, r.tiempo_preparacion,
                   r.visitas, r.destacada, r.created_at,
                   c_cat.nombre AS categoria,
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating,
                   COUNT(DISTINCT v.$pkV)   AS total_valoraciones,
                   COUNT(DISTINCT c.$pkCom) AS total_comentarios,
                   COUNT(DISTINCT f.$pkF)   AS total_favoritos
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkChef
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            LEFT JOIN comentarios c    ON c.$fkComRec = r.$pkR
            LEFT JOIN favoritos f      ON f.$fkFavRec = r.$pkR
            WHERE $whereSQL
            GROUP BY r.$pkR ORDER BY r.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reporteComentarios(array $filtros = []): array {
        $pkR   = $this->pk('recetas');
        $pkU   = $this->pk('usuarios');
        $pkCom = $this->pk('comentarios');
        $pkCat = $this->pk('categorias');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'    : 'id_chef';
        $fkRecCat = $this->colExists('recetas','id_categoria')   ? 'id_categoria':'id_categoria';
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta'  : $pkR;
        $fkComUsr = $this->colExists('comentarios','id_usuario') ? 'id_usuario' : $pkU;
        $where = ['1=1']; $params = [];
        if (!empty($filtros['desde']))    { $where[] = 'c.created_at >= ?'; $params[] = $filtros['desde'] . ' 00:00:00'; }
        if (!empty($filtros['hasta']))    { $where[] = 'c.created_at <= ?'; $params[] = $filtros['hasta'] . ' 23:59:59'; }
        if (!empty($filtros['categoria'])){ $where[] = "r.$fkRecCat = ?";   $params[] = $filtros['categoria']; }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->pdo->prepare("
            SELECT c.$pkCom AS id_comentario, c.contenido, c.created_at,
                   u.nombre AS autor,
                   r.$pkR AS id_receta, r.titulo AS receta_titulo,
                   c_cat.nombre AS categoria,
                   ch.nombre AS chef_nombre
            FROM comentarios c
            JOIN recetas  r    ON r.$pkR = c.$fkComRec
            JOIN usuarios u    ON u.$pkU = c.$fkComUsr
            JOIN usuarios ch   ON ch.$pkU = r.$fkChef
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            WHERE $whereSQL ORDER BY c.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Chatbot
    public function buscar(string $termino): array {
        $pkR = $this->pk('recetas');
        $fkIngRec = $this->colExists('ingredientes','id_receta') ? 'id_receta' : $pkR;
        $like = '%' . $termino . '%';
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT r.$pkR AS id, r.titulo, r.descripcion, r.tiempo_preparacion, r.dificultad, r.id_categoria, r.imagen
            FROM recetas r LEFT JOIN ingredientes i ON i.$fkIngRec = r.$pkR
            WHERE r.titulo LIKE ? OR r.descripcion LIKE ? OR i.nombre LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function porCategoria(string $categoria): array {
        $pkR   = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $fkRecCat = $this->colExists('recetas','id_categoria') ? 'id_categoria' : 'id_categoria';
        $stmt = $this->pdo->prepare("
            SELECT r.$pkR AS id, r.titulo, r.tiempo_preparacion, r.dificultad
            FROM recetas r JOIN categorias c ON c.$pkCat = r.$fkRecCat
            WHERE c.nombre = ? LIMIT 6
        ");
        $stmt->execute([$categoria]);
        return $stmt->fetchAll();
    }

    public function porPais(string $id_pais): array {
        $pkR = $this->pk('recetas');
        $fkPais = $this->colExists('recetas','id_pais') ? 'id_pais' : 'id_pais';
        $stmt = $this->pdo->prepare("SELECT $pkR AS id, titulo, tiempo_preparacion, dificultad FROM recetas WHERE $fkPais = ? ORDER BY destacada DESC LIMIT 6");
        $stmt->execute([$id_pais]);
        return $stmt->fetchAll();
    }

    public function porTiempo(int $minutos): array {
        $pkR = $this->pk('recetas');
        $stmt = $this->pdo->prepare("SELECT $pkR AS id, titulo, tiempo_preparacion, dificultad FROM recetas WHERE tiempo_preparacion <= ? ORDER BY tiempo_preparacion ASC LIMIT 5");
        $stmt->execute([$minutos]);
        return $stmt->fetchAll();
    }

    public function porDificultad(string $dificultad): array {
        $pkR = $this->pk('recetas');
        $stmt = $this->pdo->prepare("SELECT $pkR AS id, titulo, tiempo_preparacion FROM recetas WHERE dificultad LIKE ? LIMIT 6");
        $stmt->execute(['%' . $dificultad . '%']);
        return $stmt->fetchAll();
    }

    public function listarTodas(): array {
        $pkR = $this->pk('recetas');
        $stmt = $this->pdo->query("SELECT $pkR AS id, titulo, id_categoria, tiempo_preparacion, dificultad FROM recetas ORDER BY titulo ASC LIMIT 20");
        return $stmt->fetchAll();
    }

    public function categorias(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT id_categoria FROM recetas WHERE id_categoria IS NOT NULL");
        return array_column($stmt->fetchAll(), 'id_categoria');
    }

    public function contarPublicadas(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM recetas WHERE publicada = 1")->fetchColumn();
    }

    public function contarPaisesCulinarios(): int {
        $fkPais = $this->colExists('recetas','id_pais') ? 'id_pais' : 'id_pais';
        return (int)$this->pdo->query("SELECT COUNT(DISTINCT $fkPais) FROM recetas WHERE publicada=1 AND $fkPais IS NOT NULL")->fetchColumn();
    }

    public function contarFavoritosUsuario(int $userId): int {
        $fkUsr = $this->colExists('favoritos','id_usuario') ? 'id_usuario' : 'id_usuario';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE $fkUsr = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function masPopulares(int $limite = 3): array {
        $pkR   = $this->pk('recetas');
        $pkCat = $this->pk('categorias');
        $pkP   = $this->pk('paises');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $pkF   = $this->pk('favoritos');
        $pkCom = $this->pk('comentarios');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'    : 'id_chef';
        $fkRecCat = $this->colExists('recetas','id_categoria')   ? 'id_categoria':'id_categoria';
        $fkRecPais= $this->colExists('recetas','id_pais')        ? 'id_pais'    : 'id_pais';
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta'  : $pkR;
        $fkFavRec = $this->colExists('favoritos','id_receta')    ? 'id_receta'  : $pkR;
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta'  : $pkR;
        $isoCol   = $this->colExists('paises','codigo_iso')      ? "p_mp.codigo_iso AS pais_iso," : "'' AS pais_iso,";
        $stmt = $this->pdo->prepare("
            SELECT r.$pkR AS id, r.titulo, r.descripcion, r.imagen,
                   r.tiempo_preparacion, r.dificultad, r.visitas, r.$fkRecPais AS id_pais,
                   c_cat.nombre AS categoria, c_cat.color AS cat_color, c_cat.icono AS cat_icono,
                   $isoCol
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV)   AS total_valoraciones,
                   COUNT(DISTINCT f.$pkF)   AS total_favoritos,
                   COUNT(DISTINCT cm.$pkCom) AS total_comentarios,
                   (r.visitas + COUNT(DISTINCT f.$pkF)*3 + COUNT(DISTINCT cm.$pkCom)*2 + COUNT(DISTINCT v.$pkV)*2) AS score
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p_mp      ON p_mp.$pkP  = r.$fkRecPais
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkChef
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            LEFT JOIN favoritos f      ON f.$fkFavRec = r.$pkR
            LEFT JOIN comentarios cm   ON cm.$fkComRec = r.$pkR
            WHERE r.publicada = 1
            GROUP BY r.$pkR ORDER BY score DESC LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    public function contarRecetasChef(int $chefId): int {
        $fkChef = $this->colExists('recetas','id_chef') ? 'id_chef' : 'id_chef';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM recetas WHERE $fkChef = ?");
        $stmt->execute([$chefId]);
        return (int)$stmt->fetchColumn();
    }

    public function ratingPromedioChef(int $chefId): string {
        $pkR    = $this->pk('recetas');
        $fkChef = $this->colExists('recetas','id_chef')        ? 'id_chef'  : 'id_chef';
        $fkValRec= $this->colExists('valoraciones','id_receta')? 'id_receta': $pkR;
        $stmt = $this->pdo->prepare("
            SELECT ROUND(AVG(v.puntuacion), 1)
            FROM valoraciones v JOIN recetas r ON r.$pkR = v.$fkValRec
            WHERE r.$fkChef = ?
        ");
        $stmt->execute([$chefId]);
        return (string)($stmt->fetchColumn() ?: '—');
    }

    public function buscarDashboard(string $termino, int $limite = 12): array {
        $pkR      = $this->pk('recetas');
        $pkU      = $this->pk('usuarios');
        $pkV      = $this->pk('valoraciones');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'   : 'id_chef';
        $fkIngRec = $this->colExists('ingredientes','id_receta') ? 'id_receta' : $pkR;
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta' : $pkR;
        $like = '%' . $termino . '%';
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT r.$pkR AS id, r.titulo, r.descripcion, r.tiempo_preparacion,
                   r.dificultad, r.id_categoria, r.id_pais, r.imagen,
                   u.nombre AS chef_nombre,
                   ROUND(AVG(v.puntuacion), 1) AS rating
            FROM recetas r
            LEFT JOIN ingredientes i ON i.$fkIngRec = r.$pkR
            LEFT JOIN usuarios u     ON u.$pkU = r.$fkChef
            LEFT JOIN valoraciones v ON v.$fkValRec = r.$pkR
            WHERE r.publicada = 1 AND (r.titulo LIKE ? OR r.descripcion LIKE ? OR i.nombre LIKE ?)
            GROUP BY r.$pkR ORDER BY r.destacada DESC, r.created_at DESC LIMIT ?
        ");
        $stmt->execute([$like, $like, $like, $limite]);
        return $stmt->fetchAll();
    }
}
