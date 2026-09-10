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
        // Chef y admin tienen sus propios dashboards — redirigir si corresponde
        if (hasRole(ROL_CHEF))  redirect('mis-recetas');
        if (hasRole(ROL_ADMIN)) redirect('admin');
        require_once __DIR__ . '/../view/usuario/dashboard.php';
    }

    public function verRecetas(): void
    {
        // Accesible para todos (visitantes y logueados)
        require_once __DIR__ . '/../view/usuario/ver_recetas.php';
    }

    public function favoritos(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/favoritos.php';
    }

    public function categorias(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/categorias.php';
    }

    public function perfil(): void
    {
        requireLogin();
        require_once __DIR__ . '/../view/usuario/perfil.php';
    }

    public function perfilChef(string $chefId = ''): void
    {
        // El ID viene como parámetro capturado por el regex del router
        if (!$chefId) {
            $chefId = $_GET['id'] ?? '0';
        }
        $_GET['chef_id'] = (int)$chefId;
        require_once __DIR__ . '/../view/usuario/perfil_chef.php';
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

    public function adminModeracion(): void
    {
        requireLogin();
        requireRole(ROL_ADMIN);
        require_once __DIR__ . '/../view/admin/moderacion.php';
    }
}
