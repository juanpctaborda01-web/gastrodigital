<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../config/conexion.php';

class ComentarioController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    /**
     * POST /api/comentario
     * Body: { receta_id, contenido, csrf }
     * Devuelve el comentario creado en JSON.
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        requireLogin();

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $csrf    = $body['csrf']       ?? ($_POST['csrf_token'] ?? '');
        $recetaId= (int)($body['receta_id'] ?? 0);
        $contenido = trim($body['contenido'] ?? '');

        if (!$csrf || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sesión inválida.']); return;
        }
        if (!$recetaId || mb_strlen($contenido) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'El comentario no puede estar vacío.']); return;
        }
        if (mb_strlen($contenido) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Máximo 1000 caracteres.']); return;
        }

        // Verificar que la receta existe
        $stmt = $this->pdo->prepare("SELECT id FROM recetas WHERE id = ? AND publicada = 1");
        $stmt->execute([$recetaId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Receta no encontrada.']); return;
        }

        $userId = (int)$_SESSION['usuario_id'];
        $stmt = $this->pdo->prepare(
            "INSERT INTO comentarios (receta_id, usuario_id, contenido) VALUES (?, ?, ?)"
        );
        $stmt->execute([$recetaId, $userId, $contenido]);
        $nuevoId = (int)$this->pdo->lastInsertId();

        echo json_encode([
            'exito'        => true,
            'id'           => $nuevoId,
            'autor'        => $_SESSION['usuario_nombre'],
            'autor_avatar' => $_SESSION['usuario_avatar'] ?? null,
            'contenido'    => $contenido,
            'fecha'        => date('d/m/Y H:i'),
        ]);
    }

    /**
     * GET /api/buscar?q=...&categoria=...&dificultad=...&tiempo=...&pagina=...
     * Devuelve las recetas filtradas como JSON (para búsqueda en tiempo real).
     */
    public function buscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        require_once __DIR__ . '/../models/receta.php';
        $model = new Receta();

        $filtros = [
            'q'          => trim($_GET['q']          ?? ''),
            'categoria'  => trim($_GET['categoria']  ?? ''),
            'dificultad' => trim($_GET['dificultad'] ?? ''),
            'tiempo'     => trim($_GET['tiempo']     ?? ''),
        ];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));

        $resultado = $model->listarFiltradas($filtros, $pagina);

        // Serializar solo los campos que necesita el frontend
        $recetas = array_map(fn($r) => [
            'id'                => $r['id'],
            'titulo'            => $r['titulo'],
            'categoria'         => $r['categoria'] ?? '',
            'dificultad'        => $r['dificultad'],
            'tiempo_preparacion'=> (int)$r['tiempo_preparacion'],
            'chef_nombre'       => $r['chef_nombre'] ?? '',
            'chef_id'           => (int)($r['chef_id'] ?? 0),
            'rating'            => $r['rating'],
            'destacada'         => (bool)$r['destacada'],
            'imagen'            => $r['imagen'] ?? '',
        ], $resultado['recetas']);

        echo json_encode([
            'recetas' => $recetas,
            'total'   => $resultado['total'],
            'paginas' => $resultado['paginas'],
            'pagina'  => $resultado['pagina'],
        ]);
    }

    /**
     * GET /api/notificaciones
     * Devuelve contadores para el usuario actual (reportes para admin,
     * comentarios nuevos para chefs).
     */
    public function notificaciones(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!isLoggedIn()) {
            echo json_encode(['ok' => false]); return;
        }

        $userId = (int)$_SESSION['usuario_id'];
        $rol    = $_SESSION['rol'] ?? 'usuario';
        $data   = ['ok' => true, 'rol' => $rol];

        if ($rol === 'admin') {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) FROM reportes WHERE estado = 'pendiente'"
            );
            $data['reportes_pendientes'] = (int)$stmt->fetchColumn();
        }

        if ($rol === 'chef') {
            // Comentarios recibidos en los últimos 5 minutos
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM comentarios c
                JOIN recetas r ON r.id = c.receta_id
                WHERE r.chef_id = ?
                  AND c.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute([$userId]);
            $data['comentarios_nuevos'] = (int)$stmt->fetchColumn();
        }

        echo json_encode($data);
    }
}
