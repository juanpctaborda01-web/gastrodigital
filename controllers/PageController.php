<?php
/**
 * PageController — renderiza las vistas según la ruta solicitada.
 */
class PageController
{
    // ── Auth ──────────────────────────────────────────────────────────────────

    public function login(): void
    {
        require_once __DIR__ . '/../view/auth/login.php';
    }

    public function register(): void
    {
        require_once __DIR__ . '/../view/auth/register.php';
    }

    // ── Usuario ───────────────────────────────────────────────────────────────

    public function dashboardUsuario(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/dashboard.php';
    }

    public function verRecetas(): void
    {
        require_once __DIR__ . '/../view/usuario/ver_recetas.php';
    }

    public function favoritos(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/favoritos.php';
    }

    public function favorito(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        requireLogin();

        $tokenPost = $_POST['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (!$tokenPost || !$tokenSesion || !hash_equals($tokenSesion, $tokenPost)) {
            http_response_code(403);
            echo json_encode(['error' => 'Petición no válida']);
            return;
        }

        $recetaId = (int)($_POST['receta_id'] ?? 0);
        if (!$recetaId) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }

        require_once __DIR__ . '/../models/receta.php';
        $resultado = (new Receta())->toggleFavorito((int)$_SESSION['usuario_id'], $recetaId);
        echo json_encode($resultado);
    }

    // ── Chef ──────────────────────────────────────────────────────────────────

    public function misRecetas(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        require_once __DIR__ . '/../view/chef/mis_recetas.php';
    }

    public function crearReceta(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        require_once __DIR__ . '/../view/chef/crear_receta.php';
    }

    public function editarReceta(): void
    {
        requireLogin();
        requireRole(ROL_CHEF);
        require_once __DIR__ . '/../view/chef/editar_receta.php';
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function dashboardAdmin(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/dashboard.php';
    }

    public function adminUsuarios(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/usuarios.php';
    }

    public function adminCategorias(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/categorias.php';
    }

    public function adminReportes(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/reportes.php';
    }
}
