<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = rtrim(BASE_URL, '/');
$rol     = $_SESSION['rol'] ?? '';
$nombre  = $_SESSION['usuario_nombre'] ?? '';
$email   = $_SESSION['usuario_email'] ?? '';
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Abrir menú">
    <i class="bi bi-list"></i>
</button>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?= $baseUrl ?>/">
            <img src="<?= $baseUrl ?>/img/logoGastroDigital.jpeg" alt="GastroDigital">
            <div>
                <span>GastroDigital</span>
                <small>Recetas & cocina</small>
            </div>
        </a>
        <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Cerrar menú">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Explorar</div>
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <a class="nav-link <?= $paginaActual === 'ver_recetas.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/usuario/ver_recetas.php">
                    <i class="bi bi-book"></i>Recetas
                </a>
                <a class="nav-link <?= $paginaActual === 'favoritos.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/index.php?route=favoritos">
                    <i class="bi bi-heart-fill"></i>Recetas favoritas
                </a>
            <?php elseif ($rol === 'admin'): ?>
                <a class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>"
                         href="<?= $baseUrl ?>/index.php?route=admin">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
                <a class="nav-link <?= $paginaActual === 'usuarios.php' ? 'active' : '' ?>"
                         href="<?= $baseUrl ?>/index.php?route=admin/usuarios">
                    <i class="bi bi-people"></i>Usuarios
                </a>
                <a class="nav-link <?= $paginaActual === 'categorias.php' ? 'active' : '' ?>"
                         href="<?= $baseUrl ?>/index.php?route=admin/categorias">
                    <i class="bi bi-tags"></i>Categorías
                </a>
                <a class="nav-link <?= $paginaActual === 'reportes.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/index.php?route=admin/reportes&tipo=recetas">
                    <i class="bi bi-file-earmark-bar-graph"></i>Reportes
                </a>
                <a class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>"
                         href="<?= $baseUrl ?>/index.php?route=admin#perfil">
                    <i class="bi bi-person-circle"></i>Perfil
                </a>
            <?php elseif ($rol === 'chef'): ?>
                <a class="nav-link <?= $paginaActual === 'mis_recetas.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/chef/mis_recetas.php">
                    <i class="bi bi-journal-richtext"></i>Mis recetas
                </a>
                <a class="nav-link <?= $paginaActual === 'crear_receta.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/chef/crear_receta.php">
                    <i class="bi bi-plus-circle"></i>Nueva receta
                </a>
                <a class="nav-link <?= $paginaActual === 'ver_recetas.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/usuario/ver_recetas.php">
                    <i class="bi bi-book"></i>Explorar
                </a>
                <a class="nav-link <?= $paginaActual === 'favoritos.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/index.php?route=favoritos">
                    <i class="bi bi-heart-fill"></i>Recetas favoritas
                </a>
                <a class="nav-link <?= $paginaActual === 'mis_recetas.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/chef/mis_recetas.php#perfil">
                    <i class="bi bi-person-circle"></i>Perfil
                </a>
            <?php else: ?>
                <a class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/usuario/dashboard.php">
                    <i class="bi bi-house"></i>Inicio
                </a>
                <a class="nav-link <?= $paginaActual === 'ver_recetas.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/usuario/ver_recetas.php">
                    <i class="bi bi-book"></i>Recetas
                </a>
                <a class="nav-link <?= $paginaActual === 'favoritos.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/index.php?route=favoritos">
                    <i class="bi bi-heart-fill"></i>Recetas favoritas
                </a>
                <a class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>"
                   href="<?= $baseUrl ?>/view/usuario/dashboard.php#perfil">
                    <i class="bi bi-person-circle"></i>Perfil
                </a>
            <?php endif; ?>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Herramientas</div>
            <button type="button" class="nav-link nav-link-btn" onclick="document.getElementById('chatbotToggle').click()">
                <i class="bi bi-robot"></i>Chef Bot
            </button>
        </div>
    </nav>

    <div class="sidebar-footer <?= !isset($_SESSION['usuario_id']) ? 'auth-sidebar-footer' : '' ?>">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="user-card">
                <div class="user-avatar">
                    <?= strtoupper(substr($nombre, 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($nombre) ?></div>
                    <?php if ($email): ?><div class="user-email"><?= htmlspecialchars($email) ?></div><?php endif; ?>
                    <span class="badge-rol badge-<?= $rol ?: 'usuario' ?>"><?= ucfirst($rol ?: 'usuario') ?></span>
                </div>
            </div>
            <a class="btn btn-outline-primary btn-sm w-100 mt-3" href="<?= $baseUrl ?>/index.php?route=logout">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
            </a>
        <?php else: ?>
            <div class="auth-sidebar-actions">
                <a href="<?= $baseUrl ?>/index.php?route=login" class="btn btn-primary btn-sm w-100 mb-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
                </a>
                <a href="<?= $baseUrl ?>/index.php?route=register" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-person-plus me-1"></i>Registrarse
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>

<script>
(function () {
    const html = document.documentElement;
    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    const backdrop = document.getElementById('sidebarBackdrop');

    localStorage.removeItem('gd_theme');
    html.removeAttribute('data-theme');

    const open = () => document.body.classList.add('sidebar-open');
    const close = () => document.body.classList.remove('sidebar-open');

    toggle?.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    window.addEventListener('resize', () => { if (window.innerWidth >= 992) close(); });

})();
</script>
