<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/reporte.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../models/usuarios.php';

class ReporteController
{
    private Reporte $model;

    public function __construct()
    {
        $this->model = new Reporte();
    }

    /**
     * POST /api/reportar
     * Body JSON: { tipo, referencia_id, motivo, descripcion, csrf }
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        requireLogin();

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $csrf = $body['csrf'] ?? ($_POST['csrf_token'] ?? '');

        if (!$csrf || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            http_response_code(403);
            echo json_encode(['error' => 'Petición inválida.']);
            return;
        }

        $tipo         = $body['tipo']          ?? '';
        $referenciaId = (int)($body['referencia_id'] ?? 0);
        $motivo       = trim($body['motivo']   ?? '');
        $descripcion  = trim($body['descripcion'] ?? '');
        $userId       = (int)$_SESSION['usuario_id'];

        $tiposValidos = ['receta', 'comentario', 'perfil', 'valoracion'];
        if (!in_array($tipo, $tiposValidos) || !$referenciaId || empty($motivo)) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos.']);
            return;
        }

        if ($this->model->yaReporto($userId, $tipo, $referenciaId)) {
            echo json_encode(['error' => 'Ya enviaste un reporte para este elemento.']);
            return;
        }

        $id = $this->model->crear($tipo, $referenciaId, $userId,
                                   mb_substr($motivo, 0, 120),
                                   mb_substr($descripcion, 0, 1000));

        echo json_encode(['exito' => true, 'id' => $id]);
    }

    /**
     * GET /admin/moderacion — vista del panel de moderación
     */
    public function panel(): void
    {
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/moderacion.php';
    }

    /**
     * POST /admin/moderacion/accion — tomar acción sobre un reporte
     * Permite: revisado | desestimado | eliminado
     */
    public function accion(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $reporteId = (int)($_POST['reporte_id'] ?? 0);
        $accion    = $_POST['accion_mod']       ?? '';
        $adminId   = (int)$_SESSION['usuario_id'];

        $estadosValidos = ['revisado', 'desestimado', 'eliminado'];
        if (!$reporteId || !in_array($accion, $estadosValidos)) {
            $_SESSION['error'] = 'Acción inválida.';
            redirect('admin/moderacion');
        }

        $this->model->actualizarEstado($reporteId, $accion, $adminId);

        // Si la acción es "eliminado", borrar el elemento reportado
        if ($accion === 'eliminado') {
            $this->eliminarElemento($reporteId);
        }

        $_SESSION['exito'] = 'Reporte actualizado.';
        redirect('admin/moderacion');
    }

    /** Elimina el elemento correspondiente al reporte */
    private function eliminarElemento(int $reporteId): void
    {
        // Re-leer el reporte para saber qué borrar
        $stmt = Conexion::getInstancia()->getPDO()->prepare(
            "SELECT tipo, referencia_id FROM reportes WHERE id = ?"
        );
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();
        if (!$reporte) return;

        $pdo  = Conexion::getInstancia()->getPDO();
        $tipo = $reporte['tipo'];
        $ref  = (int)$reporte['referencia_id'];

        match ($tipo) {
            'receta'     => $pdo->prepare("DELETE FROM recetas     WHERE id = ?")->execute([$ref]),
            'comentario' => $pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$ref]),
            'valoracion' => $pdo->prepare("DELETE FROM valoraciones WHERE id = ?")->execute([$ref]),
            'perfil'     => null, // perfiles no se eliminan automáticamente — el admin lo hace manualmente
            default      => null,
        };
    }
}
