<?php
/**
 * ChatbotController.php
 * ─────────────────────────────────────────────────────────────
 * Endpoint AJAX para Chefbot.
 * Recibe POST JSON → valida CSRF → llama a ChatbotModel → devuelve JSON.
 *
 * Ruta registrada en RouterController:
 *   POST chatbot → ChatbotController::respuesta()
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/ChatbotModel.php';

class ChatbotController
{
    public function historial(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        try {
            $userId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
            $model = new ChatbotModel();
            echo json_encode([
                'mensajes' => $model->obtenerHistorial(
                    $userId,
                    $this->obtenerSessionKey($_GET['chat_id'] ?? null)
                ),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('[ChatbotController] No se pudo cargar el historial: ' . $e->getMessage());
            http_response_code($e instanceof \InvalidArgumentException ? 400 : 500);
            echo json_encode(['error' => $e instanceof \InvalidArgumentException
                ? 'Identificador de conversación inválido.'
                : 'No se pudo cargar la conversación.']);
        }
    }

    public function respuesta(): void
    {
        // ── Cabeceras ──────────────────────────────────────────
        header('Content-Type: application/json; charset=utf-8');
        // Evitar que el buffer de salida interfiera con la respuesta
        if (ob_get_level()) ob_end_clean();

        // ── Solo POST ──────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido.']);
            return;
        }

        // ── Leer body JSON ─────────────────────────────────────
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cuerpo de la petición inválido.']);
            return;
        }

        // ── Validar CSRF ───────────────────────────────────────
        $tokenPost   = $body['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenPost) || empty($tokenSesion) ||
            !hash_equals((string)$tokenSesion, (string)$tokenPost)) {
            http_response_code(403);
            echo json_encode(['error' => 'Petición no autorizada.']);
            return;
        }

        // ── Validar mensaje ────────────────────────────────────
        if (($body['accion'] ?? null) === 'limpiar') {
            try {
                $userId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
                (new ChatbotModel())->limpiarHistorial(
                    $userId,
                    $this->obtenerSessionKey($body['chat_id'] ?? null)
                );
                echo json_encode(['exito' => true]);
            } catch (\Throwable $e) {
                error_log('[ChatbotController] No se pudo limpiar el historial: ' . $e->getMessage());
                http_response_code($e instanceof \InvalidArgumentException ? 400 : 500);
                echo json_encode(['error' => $e instanceof \InvalidArgumentException
                    ? 'Identificador de conversación inválido.'
                    : 'No se pudo limpiar la conversación.']);
            }
            return;
        }

        $mensaje = isset($body['mensaje']) && is_string($body['mensaje'])
            ? trim($body['mensaje'])
            : '';

        if ($mensaje === '') {
            echo json_encode([
                'respuesta' => 'Por favor escríbeme algo para poder ayudarte. 👨‍🍳',
                'tipo'      => 'error',
            ]);
            return;
        }

        if (mb_strlen($mensaje, 'UTF-8') > 500) {
            echo json_encode([
                'respuesta' => 'Tu mensaje es demasiado largo. Por favor resúmelo en menos de 500 caracteres.',
                'tipo'      => 'error',
            ]);
            return;
        }

        // ── ID de usuario logueado (puede ser null) ────────────
        $userId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;

        // ── Procesar con el modelo ─────────────────────────────
        try {
            $model     = new ChatbotModel();
            $resultado = $model->procesar(
                $mensaje,
                $userId,
                $this->obtenerSessionKey($body['chat_id'] ?? null)
            );
        } catch (\Throwable $e) {
            error_log('[ChatbotController] Error inesperado: ' . $e->getMessage());
            http_response_code($e instanceof \InvalidArgumentException ? 400 : 500);
            echo json_encode([
                'respuesta' => 'Ocurrió un error interno. Por favor intenta de nuevo en un momento.',
                'tipo'      => 'error',
            ]);
            return;
        }

        // ── Devolver respuesta ─────────────────────────────────
        echo json_encode([
            'respuesta' => $resultado['respuesta'],
            'tipo'      => $resultado['tipo'] ?? 'general',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function obtenerSessionKey($valor): string
    {
        if ($valor === null || $valor === '') {
            return session_id();
        }
        if (!is_string($valor) || !preg_match('/^[a-f0-9]{64}$/', $valor)) {
            throw new \InvalidArgumentException('Identificador de conversación inválido.');
        }
        return $valor;
    }
}

// ── Ejecución directa (compatibilidad con instalaciones antiguas) ──────────
if (
    isset($_SERVER['SCRIPT_FILENAME']) &&
    basename($_SERVER['SCRIPT_FILENAME']) === 'ChatbotController.php'
) {
    (new ChatbotController())->respuesta();
    exit();
}
