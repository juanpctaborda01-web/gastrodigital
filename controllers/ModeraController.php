<?php
require_once __DIR__ . '/../config/config.php';

/**
 * ModeraController — acciones de moderación directa del admin:
 * borrar comentarios y valoraciones desde el panel.
 */
class ModeraController
{
    private $pdo;

    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    /** POST /admin/moderacion/borrar-comentario */
    public function borrarComentario(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id     = (int)($_POST['id']     ?? 0);
        $origen = trim($_POST['origen']  ?? 'moderacion'); // de dónde viene: moderacion | receta

        if ($id) {
            $this->pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$id]);
            $_SESSION['exito'] = 'Comentario eliminado.';
        } else {
            $_SESSION['error'] = 'ID de comentario inválido.';
        }

        // Redirigir al origen correcto
        if ($origen === 'receta') {
            $recetaId = (int)($_POST['receta_id'] ?? 0);
            redirect('recetas' . ($recetaId ? "?id=$recetaId" : ''));
        } else {
            redirect('admin/moderacion');
        }
    }

    /** POST /admin/moderacion/borrar-valoracion */
    public function borrarValoracion(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id     = (int)($_POST['id']    ?? 0);
        $origen = trim($_POST['origen'] ?? 'moderacion');

        if ($id) {
            $this->pdo->prepare("DELETE FROM valoraciones WHERE id = ?")->execute([$id]);
            $_SESSION['exito'] = 'Valoración eliminada.';
        } else {
            $_SESSION['error'] = 'ID de valoración inválido.';
        }

        if ($origen === 'receta') {
            $recetaId = (int)($_POST['receta_id'] ?? 0);
            redirect('recetas' . ($recetaId ? "?id=$recetaId" : ''));
        } else {
            redirect('admin/moderacion');
        }
    }
}
