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

        // Solo filtrar por publicada si la columna existe
        $filtrarPublicada = $this->colExists('recetas', 'publicada');
        $where  = $filtrarPublicada ? ['r.publicada = 1'] : ['1=1'];
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
        if (!empty($filtros['excluir_chef_id'])) {
            $where[]  = "r.$fkRecChef <> ?";
            $params[] = (int)$filtros['excluir_chef_id'];
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
                       r.$fkRecChef AS id_chef,
                       r.imagen, r.calorias, r.porciones, r.destacada, r.visitas,
                       COALESCE(c_lf.nombre, 'Otros')          AS categoria,
                       COALESCE(c_lf.color,  '#8e8e8e')        AS cat_color,
                       COALESCE(c_lf.icono,  'plato-principal.png') AS cat_icono,
                       $isoCol
                       p_lf.nombre AS pais_nombre,
                       COALESCE(u.nombre, 'Chef desconocido')  AS chef_nombre,
                       u.avatar AS chef_avatar,
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
        $pkCat = $this->pk('categorias');
        $fkChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $fkCat = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : $pkCat;
        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id, COALESCE(c.nombre, '') AS categoria
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCat = r.$fkCat
            WHERE r.$pkR = ? AND r.$fkChef = ?
        ");
        $stmt->execute([$id, $chefId]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE id_receta = ? ORDER BY orden");
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
        $imgPart      = !empty($datos['imagen']) ? ", imagen = ?" : "";
        // publicada: si viene en datos la usamos; si no, la mantenemos (no tocamos)
        $pubPart      = array_key_exists('publicada', $datos) ? ", publicada = ?" : "";
        $fkChef       = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $stmt = $this->pdo->prepare("
            UPDATE recetas
            SET titulo = ?, descripcion = ?, tiempo_preparacion = ?, porciones = ?,
                dificultad = ?, id_categoria = ?, calorias = ?
                $imgPart
                $pubPart
            WHERE $pkR = ? AND $fkChef = ?
        ");
        $params = [
            $datos['titulo'], $datos['descripcion'] ?? '', (int)$datos['tiempo'],
            (int)($datos['porciones'] ?? 4), $datos['dificultad'], $idCategoria,
            $datos['calorias'] ?? null,
        ];
        if (!empty($datos['imagen']))                    $params[] = $datos['imagen'];
        if (array_key_exists('publicada', $datos))       $params[] = (int)(bool)$datos['publicada'];
        $params[] = $id;
        $params[] = (int)$datos['id_chef'];
        $stmt->execute($params);
        return true;
    }

    /** Cambia publicada ↔ borrador para el chef dueño. Devuelve el nuevo estado. */
    public function togglePublicada(int $recetaId, int $chefId): array {
        $pkR    = $this->pk('recetas');
        $fkChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $stmt   = $this->pdo->prepare("SELECT publicada FROM recetas WHERE $pkR = ? AND $fkChef = ?");
        $stmt->execute([$recetaId, $chefId]);
        $row = $stmt->fetch();
        if (!$row) return ['error' => 'Receta no encontrada o sin permiso'];
        $nuevo = $row['publicada'] ? 0 : 1;
        $this->pdo->prepare("UPDATE recetas SET publicada = ? WHERE $pkR = ?")->execute([$nuevo, $recetaId]);
        return ['publicada' => (bool)$nuevo];
    }

    // Obtener receta completa con ingredientes, pasos, comentarios
    public function obtenerCompleta(int $id): ?array {
        $pkR   = $this->pk('recetas');
        $pkU   = $this->pk('usuarios');
        $pkV   = $this->pk('valoraciones');
        $pkCat = $this->pk('categorias');
        $pkPais = $this->pk('paises');
        $fkValRec = $this->colExists('valoraciones', 'id_receta') ? 'id_receta' : $pkR;
        $fkRecChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'id_chef';
        $fkRecCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'id_categoria';
        $fkRecPais = $this->colExists('recetas', 'id_pais') ? 'id_pais' : 'pais_id';
        $isoPais = $this->colExists('paises', 'codigo_iso') ? 'p.codigo_iso' : "''";

        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   COALESCE(u.nombre, 'Chef desconocido') AS chef_nombre,
                   u.avatar AS chef_avatar,
                   u.bio AS chef_bio,
                   u.$pkU AS chef_id,
                   COALESCE(c_cat.nombre, 'Otros') AS categoria,
                   p.nombre AS pais_nombre,
                   $isoPais AS pais_iso,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones
            FROM recetas r
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkRecChef
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p         ON p.$pkPais = r.$fkRecPais
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            WHERE r.$pkR = ?
            GROUP BY r.$pkR
        ");
        $stmt->execute([$id]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        $stmt2 = $this->pdo->prepare("SELECT * FROM ingredientes WHERE id_receta = ? ORDER BY orden");
        $stmt2->execute([$id]);
        $receta['ingredientes'] = $stmt2->fetchAll();

        $stmt3 = $this->pdo->prepare("SELECT * FROM pasos WHERE id_receta = ? ORDER BY numero");
        $stmt3->execute([$id]);
        $receta['pasos'] = $stmt3->fetchAll();

        $pkCom = $this->pk('comentarios');
        $fkComRec = $this->colExists('comentarios', 'id_receta') ? 'id_receta' : $pkR;
        $fkComUsr = $this->colExists('comentarios', 'id_usuario') ? 'id_usuario' : $pkU;
        $stmt4 = $this->pdo->prepare("
            SELECT c.*, c.$pkCom AS id_comentario, u.nombre AS autor,
                   u.avatar AS autor_avatar
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $datos['titulo'], $datos['descripcion'] ?? '',
            (int)$datos['tiempo'], (int)($datos['porciones'] ?? 4),
            $datos['dificultad'] ?? 'Fácil', $idCategoria,
            $datos['imagen'] ?? null, $datos['calorias'] ?? null, $chefId,
            isset($datos['publicada']) ? (int)(bool)$datos['publicada'] : 1,
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
        $stmt = $this->pdo->prepare(
            "INSERT INTO pasos (id_receta, numero, descripcion, imagen, duracion) VALUES (?,?,?,?,?)"
        );
        foreach ($pasos as $i => $paso) {
            // Acepta string simple (retrocompatibilidad chef) o array con campos extendidos
            if (is_string($paso)) {
                if (trim($paso) === '') continue;
                $stmt->execute([$recetaId, $i + 1, trim($paso), null, null]);
            } else {
                $desc = trim($paso['descripcion'] ?? '');
                if ($desc === '') continue;
                $img  = !empty($paso['imagen'])   ? trim($paso['imagen'])       : null;
                $dur  = isset($paso['duracion']) && (int)$paso['duracion'] > 0
                        ? (int)$paso['duracion'] : null;
                $stmt->execute([$recetaId, $i + 1, $desc, $img, $dur]);
            }
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

    public function favoritosIds(int $userId, array $recetaIds): array {
        $recetaIds = array_values(array_unique(array_filter(array_map('intval', $recetaIds), static fn(int $id): bool => $id > 0)));
        if (!$recetaIds) return [];

        $pkR = $this->pk('recetas');
        $fkFavRec = $this->colExists('favoritos', 'id_receta') ? 'id_receta' : $pkR;
        $placeholders = implode(',', array_fill(0, count($recetaIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT f.$fkFavRec
            FROM favoritos f
            WHERE f.id_usuario = ? AND f.$fkFavRec IN ($placeholders)
        ");
        $stmt->execute(array_merge([$userId], $recetaIds));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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
                   COALESCE(c_cat.nombre, 'Otros')         AS categoria,
                   COALESCE(c_cat.color,  '#8e8e8e')       AS cat_color,
                   COALESCE(c_cat.icono,  'plato-principal.png') AS cat_icono,
                   $isoCol
                   COALESCE(u.nombre, 'Chef desconocido')  AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating
            FROM favoritos f
            JOIN recetas r          ON r.$pkR  = f.$fkFavRec
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p_f       ON p_f.$pkP = r.$fkRecPais
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkRecChef
            LEFT JOIN valoraciones v   ON v.$fkValRec = r.$pkR
            WHERE f.$fkFavUsr = ?
            GROUP BY r.$pkR
            ORDER BY r.created_at DESC, r.$pkR DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function valorar(int $userId, int $recetaId, int $puntuacion, string $comentario): array {
        $pkV = $this->pk('valoraciones');
        $stmt = $this->pdo->prepare("SELECT $pkV FROM valoraciones WHERE id_usuario = ? AND id_receta = ?");
        $stmt->execute([$userId, $recetaId]);
        $esNueva = !$stmt->fetch();
        if (!$esNueva) {
            $this->pdo->prepare("UPDATE valoraciones SET puntuacion=?, comentario=? WHERE id_usuario=? AND id_receta=?")
                      ->execute([$puntuacion, $comentario, $userId, $recetaId]);
        } else {
            $this->pdo->prepare("INSERT INTO valoraciones (id_receta, id_usuario, puntuacion, comentario) VALUES (?,?,?,?)")
                      ->execute([$recetaId, $userId, $puntuacion, $comentario]);
            $this->notificarChefReceta($recetaId, $userId, 'valoracion', 'Nueva valoración para tu receta: ');
        }
        return ['exito' => true];
    }

    public function comentar(int $userId, int $recetaId, string $contenido): array {
        if (trim($contenido) === '') return ['error' => 'El comentario no puede estar vacío'];
        $this->pdo->prepare("INSERT INTO comentarios (id_receta, id_usuario, contenido) VALUES (?,?,?)")
            ->execute([$recetaId, $userId, trim($contenido)]);
        $this->notificarChefReceta($recetaId, $userId, 'comentario', 'Nuevo comentario en tu receta: ');
        return ['exito' => true];
    }

    public function reportar(int $userId, string $tipo, int $objetivoId, string $motivo, string $descripcion): array {
        $motivo = trim($motivo);
        $descripcion = trim($descripcion);
        $objetivos = [
            'receta' => ['recetas', $this->pk('recetas')],
            'comentario' => ['comentarios', $this->pk('comentarios')],
            'usuario' => ['usuarios', $this->pk('usuarios')],
            'valoracion' => ['valoraciones', $this->pk('valoraciones')],
        ];

        if (!isset($objetivos[$tipo]) || $objetivoId < 1) {
            return ['error' => 'El contenido que intentas reportar no es válido.'];
        }
        if ($motivo === '') {
            return ['error' => 'Selecciona un motivo.'];
        }

        [$tablaObjetivo, $pkObjetivo] = $objetivos[$tipo];
        $stmtObjetivo = $this->pdo->prepare("SELECT 1 FROM $tablaObjetivo WHERE $pkObjetivo = ?");
        $stmtObjetivo->execute([$objetivoId]);
        if (!$stmtObjetivo->fetchColumn()) {
            return ['error' => 'El contenido que intentas reportar ya no está disponible.'];
        }

        $idReceta = $tipo === 'receta' ? $objetivoId : null;
        $idComentario = $tipo === 'comentario' ? $objetivoId : null;
        $idUsuario = $tipo === 'usuario' ? $objetivoId : null;
        $idValoracion = $tipo === 'valoracion' ? $objetivoId : null;
        $iniciaTransaccion = !$this->pdo->inTransaction();

        try {
            if ($iniciaTransaccion) {
                $this->pdo->beginTransaction();
            }

            $this->pdo->prepare("
                INSERT INTO reportes
                    (id_receta, id_comentario, id_usuario, id_valoracion, reportado_por,
                     motivo, descripcion, estado, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
            ")->execute([
                $idReceta, $idComentario, $idUsuario, $idValoracion, $userId,
                mb_substr($motivo, 0, 120, 'UTF-8'), mb_substr($descripcion, 0, 2000, 'UTF-8'),
            ]);

            $admins = $this->pdo->query(
                "SELECT id_usuario FROM usuarios WHERE rol = 'admin' AND activo = 1"
            )->fetchAll(PDO::FETCH_COLUMN);
            require_once __DIR__ . '/Notificacion.php';
            $notificacion = new Notificacion();
            foreach ($admins as $adminId) {
                $notificacion->crear(
                    (int)$adminId,
                    'reporte',
                    'Se recibió un nuevo reporte para moderación.',
                    'index.php?route=admin/moderacion&tab=reportes'
                );
            }

            if ($iniciaTransaccion) {
                $this->pdo->commit();
            }
            return ['exito' => true];
        } catch (Throwable $e) {
            if ($iniciaTransaccion && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('[Receta] No se pudo guardar el reporte: ' . $e->getMessage());
            return ['error' => 'No se pudo enviar el reporte. Inténtalo de nuevo.'];
        }
    }

    private function notificarChefReceta(int $recetaId, int $actorId, string $tipo, string $prefijo): void {
        $fkChef = $this->colExists('recetas', 'id_chef') ? 'id_chef' : 'chef_id';
        $pkR = $this->pk('recetas');
        $stmt = $this->pdo->prepare("SELECT $fkChef AS id_chef, titulo FROM recetas WHERE $pkR = ?");
        $stmt->execute([$recetaId]);
        $receta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$receta || empty($receta['id_chef']) || (int)$receta['id_chef'] === $actorId) {
            return;
        }

        require_once __DIR__ . '/Notificacion.php';
        (new Notificacion())->crear(
            (int)$receta['id_chef'],
            $tipo,
            $prefijo . $receta['titulo'],
            'index.php?route=recetas&id=' . $recetaId
        );
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
        $stmt = $this->pdo->query("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(publicada = 1), 0) AS publicadas,
                   COUNT(DISTINCT id_categoria) AS categorias
            FROM recetas
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) $stats['total'],
            'publicadas' => (int) $stats['publicadas'],
            'categorias' => (int) $stats['categorias'],
        ];
    }

    public function statsAdmin(): array {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(publicada = 1), 0) AS publicadas,
                   COUNT(DISTINCT id_categoria) AS categorias,
                   COALESCE(SUM(visitas), 0) AS total_visitas,
                   (SELECT COUNT(*) FROM comentarios) AS total_comentarios,
                   (SELECT COUNT(*) FROM valoraciones) AS total_valoraciones,
                   COALESCE((SELECT ROUND(AVG(puntuacion), 1) FROM valoraciones), 0) AS rating_global
            FROM recetas
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) $stats['total'],
            'publicadas' => (int) $stats['publicadas'],
            'categorias' => (int) $stats['categorias'],
            'total_visitas' => (int) $stats['total_visitas'],
            'total_comentarios' => (int) $stats['total_comentarios'],
            'total_valoraciones' => (int) $stats['total_valoraciones'],
            'rating_global' => (float) $stats['rating_global'],
        ];
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
                   COALESCE(c_cat.nombre, 'Otros') AS categoria,
                   COALESCE(u.nombre, 'Chef desconocido') AS chef_nombre,
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
                   u.nombre AS autor, u.avatar AS autor_avatar,
                   r.titulo AS receta_titulo, r.$pkR AS id_receta
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
        $pkPais = $this->pk('paises');
        $fkChef   = $this->colExists('recetas','id_chef')        ? 'id_chef'    : 'id_chef';
        $fkRecCat = $this->colExists('recetas','id_categoria')   ? 'id_categoria':'id_categoria';
        $fkRecPais = $this->colExists('recetas','id_pais')       ? 'id_pais'    : 'pais_id';
        $fkValRec = $this->colExists('valoraciones','id_receta') ? 'id_receta'  : $pkR;
        $fkFavRec = $this->colExists('favoritos','id_receta')    ? 'id_receta'  : $pkR;
        $fkComRec = $this->colExists('comentarios','id_receta')  ? 'id_receta'  : $pkR;
        $stmt = $this->pdo->prepare("
            SELECT r.*, r.$pkR AS id,
                   c_cat.nombre AS categoria,
                   p.nombre AS pais_nombre,
                   ROUND(AVG(v.puntuacion), 1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones,
                   COUNT(DISTINCT f.$pkF) AS total_favoritos,
                   COUNT(DISTINCT c.$pkCom) AS total_comentarios
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p         ON p.$pkPais = r.$fkRecPais
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
                   v.rating,
                   COALESCE(v.total_valoraciones, 0) AS total_valoraciones,
                   COALESCE(f.total_favoritos, 0) AS total_favoritos,
                   COALESCE(cm.total_comentarios, 0) AS total_comentarios,
                   (r.visitas + COALESCE(f.total_favoritos, 0)*3
                       + COALESCE(cm.total_comentarios, 0)*2
                       + COALESCE(v.total_valoraciones, 0)*2) AS score
            FROM recetas r
            LEFT JOIN categorias c_cat ON c_cat.$pkCat = r.$fkRecCat
            LEFT JOIN paises p_mp      ON p_mp.$pkP  = r.$fkRecPais
            LEFT JOIN usuarios u       ON u.$pkU = r.$fkChef
            LEFT JOIN (
                SELECT $fkValRec AS id_receta, ROUND(AVG(puntuacion), 1) AS rating,
                       COUNT(*) AS total_valoraciones
                FROM valoraciones
                GROUP BY $fkValRec
            ) v ON v.id_receta = r.$pkR
            LEFT JOIN (
                SELECT $fkFavRec AS id_receta, COUNT(*) AS total_favoritos
                FROM favoritos
                GROUP BY $fkFavRec
            ) f ON f.id_receta = r.$pkR
            LEFT JOIN (
                SELECT $fkComRec AS id_receta, COUNT(*) AS total_comentarios
                FROM comentarios
                GROUP BY $fkComRec
            ) cm ON cm.id_receta = r.$pkR
            WHERE r.publicada = 1
            ORDER BY score DESC LIMIT ?
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

    // ══════════════════════════════════════════════════════════════════════
    // ── MÉTODOS EXCLUSIVOS DEL ADMINISTRADOR ─────────────────────────────
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Listado paginado de TODAS las recetas para el panel admin,
     * incluyendo filtros por título, categoría, país, chef, dificultad,
     * publicada y destacada.
     */
    public function listarAdmin(array $f = [], int $pag = 1, int $pp = 15): array
    {
        $pkR  = $this->pk('recetas');
        $pkC  = $this->pk('categorias');
        $pkP  = $this->pk('paises');
        $pkU  = $this->pk('usuarios');
        $pkV  = $this->pk('valoraciones');

        $fkCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'categoria_id';
        $fkPais = $this->colExists('recetas', 'id_pais')      ? 'id_pais'      : 'pais_id';
        $fkChef = $this->colExists('recetas', 'id_chef')      ? 'id_chef'      : 'chef_id';
        $fkVRec = $this->colExists('valoraciones', 'id_receta') ? 'id_receta'  : $pkR;

        $where  = ['1=1'];
        $params = [];

        if (!empty($f['q'])) {
            $where[]  = 'r.titulo LIKE ?';
            $params[] = '%' . $f['q'] . '%';
        }
        if (!empty($f['id_categoria'])) {
            $where[]  = "r.$fkCat = ?";
            $params[] = (int)$f['id_categoria'];
        }
        if (!empty($f['id_pais'])) {
            $where[]  = "r.$fkPais = ?";
            $params[] = (int)$f['id_pais'];
        }
        if (!empty($f['id_chef'])) {
            $where[]  = "r.$fkChef = ?";
            $params[] = (int)$f['id_chef'];
        }
        if (!empty($f['dificultad'])) {
            $where[]  = 'r.dificultad = ?';
            $params[] = $f['dificultad'];
        }
        if (isset($f['publicada']) && $f['publicada'] !== '') {
            $where[]  = 'r.publicada = ?';
            $params[] = (int)$f['publicada'];
        }
        if (isset($f['destacada']) && $f['destacada'] !== '') {
            $where[]  = 'r.destacada = ?';
            $params[] = (int)$f['destacada'];
        }

        $sql   = implode(' AND ', $where);
        $off   = ($pag - 1) * $pp;

        $total = (int)$this->pdo->prepare(
            "SELECT COUNT(*) FROM recetas r WHERE $sql"
        )->execute($params) ? (function() use ($sql, $params) {
            $s = $this->pdo->prepare("SELECT COUNT(*) FROM recetas r WHERE $sql");
            $s->execute($params);
            return (int)$s->fetchColumn();
        })() : 0;
        // ── rehacer la cuenta limpiamente ──
        $stmtC = $this->pdo->prepare("SELECT COUNT(*) FROM recetas r WHERE $sql");
        $stmtC->execute($params);
        $total = (int)$stmtC->fetchColumn();

        $stmtR = $this->pdo->prepare("
            SELECT r.$pkR AS id_receta,
                   r.titulo, r.dificultad, r.tiempo_preparacion, r.porciones,
                   r.imagen, r.publicada, r.destacada, r.visitas,
                   r.created_at, r.updated_at,
                   r.$fkCat AS id_categoria,
                   r.$fkPais AS id_pais,
                   r.$fkChef AS id_chef,
                   COALESCE(cat.nombre, 'Otros')           AS categoria_nombre,
                   p.nombre                                AS pais_nombre,
                   COALESCE(u.nombre, 'Chef desconocido')  AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating
            FROM recetas r
            LEFT JOIN categorias cat ON cat.$pkC = r.$fkCat
            LEFT JOIN paises     p   ON p.$pkP   = r.$fkPais
            LEFT JOIN usuarios   u   ON u.$pkU   = r.$fkChef
            LEFT JOIN valoraciones v ON v.$fkVRec = r.$pkR
            WHERE $sql
            GROUP BY r.$pkR
            ORDER BY r.created_at DESC
            LIMIT $pp OFFSET $off
        ");
        $stmtR->execute($params);

        return [
            'recetas' => $stmtR->fetchAll(),
            'total'   => $total,
            'paginas' => (int)ceil($total / $pp),
            'pagina'  => $pag,
        ];
    }

    /**
     * Obtiene una receta completa con ingredientes y pasos para el admin.
     * No filtra por publicada ni por chef — acceso total.
     */
    public function obtenerCompletaAdmin(int $id): ?array
    {
        $pkR  = $this->pk('recetas');
        $pkC  = $this->pk('categorias');
        $pkP  = $this->pk('paises');
        $pkU  = $this->pk('usuarios');
        $pkV  = $this->pk('valoraciones');

        $fkCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'categoria_id';
        $fkPais = $this->colExists('recetas', 'id_pais')      ? 'id_pais'      : 'pais_id';
        $fkChef = $this->colExists('recetas', 'id_chef')      ? 'id_chef'      : 'chef_id';
        $fkVRec = $this->colExists('valoraciones', 'id_receta') ? 'id_receta'  : $pkR;

        $stmt = $this->pdo->prepare("
            SELECT r.*,
                   r.$pkR AS id_receta,
                   cat.nombre AS categoria_nombre,
                   p.nombre   AS pais_nombre,
                   p.codigo_iso,
                   u.nombre   AS chef_nombre,
                   ROUND(AVG(v.puntuacion),1) AS rating,
                   COUNT(DISTINCT v.$pkV) AS total_valoraciones
            FROM recetas r
            LEFT JOIN categorias cat ON cat.$pkC = r.$fkCat
            LEFT JOIN paises     p   ON p.$pkP   = r.$fkPais
            LEFT JOIN usuarios   u   ON u.$pkU   = r.$fkChef
            LEFT JOIN valoraciones v ON v.$fkVRec = r.$pkR
            WHERE r.$pkR = ?
            GROUP BY r.$pkR
        ");
        $stmt->execute([$id]);
        $receta = $stmt->fetch();
        if (!$receta) return null;

        // Ingredientes
        $pkI    = $this->pk('ingredientes');
        $fkIRec = $this->colExists('ingredientes', 'id_receta') ? 'id_receta' : $pkR;
        $stmtI  = $this->pdo->prepare("
            SELECT $pkI AS id_ingrediente, nombre, cantidad, unidad, opcional, orden
            FROM ingredientes
            WHERE $fkIRec = ?
            ORDER BY orden ASC, $pkI ASC
        ");
        $stmtI->execute([$id]);
        $receta['ingredientes'] = $stmtI->fetchAll();

        // Pasos
        $pkPs    = $this->pk('pasos');
        $fkPsRec = $this->colExists('pasos', 'id_receta') ? 'id_receta' : $pkR;
        $stmtPs  = $this->pdo->prepare("
            SELECT $pkPs AS id_pasos, numero, descripcion, duracion
            FROM pasos
            WHERE $fkPsRec = ?
            ORDER BY numero ASC
        ");
        $stmtPs->execute([$id]);
        $receta['pasos'] = $stmtPs->fetchAll();

        return $receta;
    }

    /**
     * Crea una receta completa (receta + ingredientes + pasos) dentro
     * de una transacción. El admin puede asignar cualquier chef.
     * Devuelve el id_receta creado, o lanza excepción si falla.
     */
    public function crearAdmin(array $d): int
    {
        $fkCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'categoria_id';
        $fkPais = $this->colExists('recetas', 'id_pais')      ? 'id_pais'      : 'pais_id';
        $fkChef = $this->colExists('recetas', 'id_chef')      ? 'id_chef'      : 'chef_id';
        $fkIRec = $this->colExists('ingredientes', 'id_receta') ? 'id_receta'  : 'receta_id';
        $fkPRec = $this->colExists('pasos',        'id_receta') ? 'id_receta'  : 'receta_id';

        $this->pdo->beginTransaction();
        try {
            // 1 — Receta principal
            $stmt = $this->pdo->prepare("
                INSERT INTO recetas
                    (titulo, descripcion, tiempo_preparacion, porciones,
                     dificultad, $fkCat, $fkPais, imagen, video_url,
                     calorias, $fkChef, publicada, destacada, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
            ");
            $stmt->execute([
                $d['titulo'],
                $d['descripcion'] ?? null,
                (int)($d['tiempo_preparacion'] ?? 0),
                (int)($d['porciones'] ?? 4),
                $d['dificultad']   ?? 'Media',
                $d['id_categoria'] ? (int)$d['id_categoria'] : null,
                $d['id_pais']      ? (int)$d['id_pais']      : null,
                $d['imagen']       ?? null,
                $d['video_url']    ?? null,
                $d['calorias']     ? (int)$d['calorias']     : null,
                $d['id_chef']      ? (int)$d['id_chef']      : null,
                (int)(bool)($d['publicada'] ?? 0),
                (int)(bool)($d['destacada'] ?? 0),
            ]);
            $recetaId = (int)$this->pdo->lastInsertId();

            // 2 — Ingredientes
            if (!empty($d['ingredientes'])) {
                $stmtI = $this->pdo->prepare("
                    INSERT INTO ingredientes ($fkIRec, nombre, cantidad, unidad, opcional, orden)
                    VALUES (?,?,?,?,?,?)
                ");
                foreach ($d['ingredientes'] as $i => $ing) {
                    $nombre = trim($ing['nombre'] ?? '');
                    if ($nombre === '') continue;
                    $stmtI->execute([
                        $recetaId,
                        $nombre,
                        trim($ing['cantidad'] ?? ''),
                        trim($ing['unidad']   ?? ''),
                        0,
                        $i + 1,
                    ]);
                }
            }

            // 3 — Pasos
            if (!empty($d['pasos'])) {
                $stmtP = $this->pdo->prepare("
                    INSERT INTO pasos ($fkPRec, numero, descripcion, imagen, duracion)
                    VALUES (?,?,?,?,?)
                ");
                foreach ($d['pasos'] as $num => $paso) {
                    if (is_string($paso)) {
                        $desc = trim($paso);
                        $img  = null;
                        $dur  = null;
                    } else {
                        $desc = trim($paso['descripcion'] ?? '');
                        $img  = !empty($paso['imagen'])   ? trim($paso['imagen'])    : null;
                        $dur  = isset($paso['duracion']) && (int)$paso['duracion'] > 0
                                ? (int)$paso['duracion'] : null;
                    }
                    if ($desc === '') continue;
                    $stmtP->execute([$recetaId, $num + 1, $desc, $img, $dur]);
                }
            }

            $this->pdo->commit();
            return $recetaId;

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza una receta existente (receta + ingredientes + pasos)
     * dentro de una transacción. El admin puede editar cualquier receta.
     */
    public function editarAdmin(int $id, array $d): bool
    {
        $pkR    = $this->pk('recetas');
        $fkCat  = $this->colExists('recetas', 'id_categoria') ? 'id_categoria' : 'categoria_id';
        $fkPais = $this->colExists('recetas', 'id_pais')      ? 'id_pais'      : 'pais_id';
        $fkChef = $this->colExists('recetas', 'id_chef')      ? 'id_chef'      : 'chef_id';
        $fkIRec = $this->colExists('ingredientes', 'id_receta') ? 'id_receta'  : 'receta_id';
        $fkPRec = $this->colExists('pasos',        'id_receta') ? 'id_receta'  : 'receta_id';

        // Verificar que la receta existe
        $existe = $this->pdo->prepare("SELECT $pkR FROM recetas WHERE $pkR = ?")->execute([$id]);
        $row    = $this->pdo->prepare("SELECT $pkR FROM recetas WHERE $pkR = ?");
        $row->execute([$id]);
        if (!$row->fetch()) return false;

        $this->pdo->beginTransaction();
        try {
            // 1 — Actualizar receta
            $imgPart = !empty($d['imagen']) ? ", imagen = ?" : '';
            $stmt = $this->pdo->prepare("
                UPDATE recetas SET
                    titulo              = ?,
                    descripcion         = ?,
                    tiempo_preparacion  = ?,
                    porciones           = ?,
                    dificultad          = ?,
                    $fkCat              = ?,
                    $fkPais             = ?,
                    video_url           = ?,
                    calorias            = ?,
                    $fkChef             = ?,
                    publicada           = ?,
                    destacada           = ?,
                    updated_at          = NOW()
                    $imgPart
                WHERE $pkR = ?
            ");
            $params = [
                $d['titulo'],
                $d['descripcion'] ?? null,
                (int)($d['tiempo_preparacion'] ?? 0),
                (int)($d['porciones'] ?? 4),
                $d['dificultad']   ?? 'Media',
                $d['id_categoria'] ? (int)$d['id_categoria'] : null,
                $d['id_pais']      ? (int)$d['id_pais']      : null,
                $d['video_url']    ?? null,
                $d['calorias']     ? (int)$d['calorias']     : null,
                $d['id_chef']      ? (int)$d['id_chef']      : null,
                (int)(bool)($d['publicada'] ?? 0),
                (int)(bool)($d['destacada'] ?? 0),
            ];
            if (!empty($d['imagen'])) $params[] = $d['imagen'];
            $params[] = $id;
            $stmt->execute($params);

            // 2 — Reemplazar ingredientes (DELETE + INSERT dentro de la tx)
            $this->pdo->prepare("DELETE FROM ingredientes WHERE $fkIRec = ?")->execute([$id]);
            if (!empty($d['ingredientes'])) {
                $stmtI = $this->pdo->prepare("
                    INSERT INTO ingredientes ($fkIRec, nombre, cantidad, unidad, opcional, orden)
                    VALUES (?,?,?,?,?,?)
                ");
                foreach ($d['ingredientes'] as $i => $ing) {
                    $nombre = trim($ing['nombre'] ?? '');
                    if ($nombre === '') continue;
                    $stmtI->execute([
                        $id,
                        $nombre,
                        trim($ing['cantidad'] ?? ''),
                        trim($ing['unidad']   ?? ''),
                        0,
                        $i + 1,
                    ]);
                }
            }

            // 3 — Reemplazar pasos (DELETE + INSERT dentro de la tx)
            $this->pdo->prepare("DELETE FROM pasos WHERE $fkPRec = ?")->execute([$id]);
            if (!empty($d['pasos'])) {
                $stmtP = $this->pdo->prepare("
                    INSERT INTO pasos ($fkPRec, numero, descripcion, imagen, duracion)
                    VALUES (?,?,?,?,?)
                ");
                foreach ($d['pasos'] as $num => $paso) {
                    if (is_string($paso)) {
                        $desc = trim($paso);
                        $img  = null;
                        $dur  = null;
                    } else {
                        $desc = trim($paso['descripcion'] ?? '');
                        $img  = !empty($paso['imagen'])   ? trim($paso['imagen'])    : null;
                        $dur  = isset($paso['duracion']) && (int)$paso['duracion'] > 0
                                ? (int)$paso['duracion'] : null;
                    }
                    if ($desc === '') continue;
                    $stmtP->execute([$id, $num + 1, $desc, $img, $dur]);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina una receta y todos sus datos relacionados dentro
     * de una transacción. Uso exclusivo del administrador.
     */
    public function eliminarAdmin(int $id): bool
    {
        $pkR    = $this->pk('recetas');
        $fkIRec = $this->colExists('ingredientes', 'id_receta') ? 'id_receta' : 'receta_id';
        $fkPRec = $this->colExists('pasos',        'id_receta') ? 'id_receta' : 'receta_id';

        // Verificar que existe
        $chk = $this->pdo->prepare("SELECT $pkR FROM recetas WHERE $pkR = ?");
        $chk->execute([$id]);
        if (!$chk->fetch()) return false;

        $this->pdo->beginTransaction();
        try {
            // Eliminar en orden para respetar FKs (si no hay CASCADE)
            foreach ([
                "DELETE FROM ingredientes WHERE $fkIRec = ?",
                "DELETE FROM pasos        WHERE $fkPRec = ?",
                "DELETE FROM favoritos    WHERE id_receta = ?",
                "DELETE FROM comentarios  WHERE id_receta = ?",
                "DELETE FROM valoraciones WHERE id_receta = ?",
                "DELETE FROM recetas      WHERE $pkR = ?",
            ] as $sql) {
                $this->pdo->prepare($sql)->execute([$id]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Devuelve la lista de usuarios con rol chef o admin para el
     * selector "Chef" del formulario admin.
     */
    public function listarChefs(): array
    {
        $pkU = $this->pk('usuarios');
        $stmt = $this->pdo->query("
            SELECT $pkU AS id_usuario, nombre
            FROM usuarios
            WHERE rol IN ('chef','admin') AND activo = 1
            ORDER BY nombre ASC
        ");
        return $stmt->fetchAll();
    }
}
