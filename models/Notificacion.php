<?php
require_once __DIR__ . '/../config/conexion.php';

class Notificacion
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leida = 0'
        );
        $stmt->execute([$usuarioId]);
        return (int)$stmt->fetchColumn();
    }

    public function listarPorUsuario(int $usuarioId, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_notificacion, tipo, mensaje, leida, url, created_at
             FROM notificaciones
             WHERE id_usuario = ?
             ORDER BY created_at DESC, id_notificacion DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, min($limite, 100)), PDO::PARAM_INT);
        $stmt->execute();
        $notificaciones = $stmt->fetchAll();
        foreach ($notificaciones as &$notificacion) {
            if (($notificacion['tipo'] ?? '') === 'reporte') {
                $notificacion['url'] = 'index.php?route=admin/moderacion&tab=reportes';
            }
        }
        unset($notificacion);
        return $notificaciones;
    }

    public function crear(int $usuarioId, string $tipo, string $mensaje, string $url): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notificaciones (id_usuario, tipo, mensaje, url)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $usuarioId,
            mb_substr($tipo, 0, 50, 'UTF-8'),
            mb_substr($mensaje, 0, 255, 'UTF-8'),
            mb_substr($url, 0, 255, 'UTF-8'),
        ]);
    }

    public function marcarLeida(int $notificacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notificaciones SET leida = 1
             WHERE id_notificacion = ? AND id_usuario = ? AND leida = 0'
        );
        $stmt->execute([$notificacionId, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    public function marcarTodasLeidas(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notificaciones SET leida = 1 WHERE id_usuario = ? AND leida = 0'
        );
        $stmt->execute([$usuarioId]);
    }
}
