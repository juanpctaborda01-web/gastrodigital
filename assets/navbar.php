<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl      = rtrim(BASE_URL, '/');
$rol          = $_SESSION['rol'] ?? '';
$nombre       = $_SESSION['usuario_nombre'] ?? '';
$email        = $_SESSION['usuario_email'] ?? '';
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!-- Botón flotante para abrir el sidebar cuando está cerrado / móvil -->
<button class="sidebar-open-btn" id="sidebarOpenBtn" type="button" aria-label="Abrir menú de navegación">
    <i class="bi bi-layout-sidebar-inset"></i>
    <span class="open-btn-tooltip">Menú</span>
</button>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="app-sidebar" id="appSidebar" aria-label="Navegación principal">

    <!-- Header & Marca -->
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?= $baseUrl ?>/" title="Ir al inicio de GastroDigital">
            <div class="sidebar-brand-logo">
                <img src="<?= $baseUrl ?>/img/GastroDigitalLogo2.png" alt="Logo de GastroDigital">
                <span class="logo-ambient-glow"></span>
            </div>
            <div class="sidebar-brand-text">
                <span class="brand-title">Gastro<span class="brand-accent">Digital</span></span>
                <span class="brand-subtitle"><span class="brand-dot"></span>Tu cocina digital</span>
            </div>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Ocultar menú">
            <i class="bi bi-chevron-left" id="sidebarToggleIcon"></i>
        </button>
    </div>

    <!-- Navegación -->
    <nav class="sidebar-nav" role="navigation">

        <?php if ($rol === ROL_ADMIN): ?>
            <div class="nav-section-label"><span>Panel admin</span></div>

            <a class="sb-link <?= $paginaActual === 'dashboard.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/admin">
                <span class="sb-icon sb-icon--dashboard"><i class="bi bi-speedometer2"></i></span>
                <span class="sb-text">Dashboard</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'usuarios.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/admin/usuarios">
                <span class="sb-icon sb-icon--users"><i class="bi bi-people-fill"></i></span>
                <span class="sb-text">Usuarios</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'categorias.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/admin/categorias">
                <span class="sb-icon sb-icon--categories"><i class="bi bi-grid-fill"></i></span>
                <span class="sb-text">Categorías</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'reportes.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/admin/reportes">
                <span class="sb-icon sb-icon--reports"><i class="bi bi-bar-chart-fill"></i></span>
                <span class="sb-text">Reportes</span>
            </a>

            <div class="sb-divider"></div>

            <a class="sb-link <?= $paginaActual === 'moderacion.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/admin/moderacion">
                <span class="sb-icon sb-icon--moderation"><i class="bi bi-shield-fill-exclamation"></i></span>
                <span class="sb-text">Moderación</span>
            </a>

        <?php elseif ($rol === ROL_CHEF): ?>
            <div class="nav-section-label"><span>Mi cocina</span></div>

            <a class="sb-link <?= $paginaActual === 'mis_recetas.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/mis-recetas">
                <span class="sb-icon sb-icon--recipes"><i class="bi bi-journal-richtext"></i></span>
                <span class="sb-text">Mis recetas</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'crear_receta.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/crear-receta">
                <span class="sb-icon sb-icon--new"><i class="bi bi-plus-circle-fill"></i></span>
                <span class="sb-text">Nueva receta</span>
                <span class="sb-badge-pill sb-badge-pill--green">Crear</span>
            </a>

            <div class="nav-section-label"><span>Explorar</span></div>

            <a class="sb-link <?= $paginaActual === 'ver_recetas.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/recetas">
                <span class="sb-icon sb-icon--explore"><i class="bi bi-compass-fill"></i></span>
                <span class="sb-text">Explorar recetas</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'favoritos.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/favoritos">
                <span class="sb-icon sb-icon--heart"><i class="bi bi-heart-fill"></i></span>
                <span class="sb-text">Favoritos</span>
            </a>
            <a class="sb-link" href="<?= $baseUrl ?>/categorias">
                <span class="sb-icon sb-icon--grid"><i class="bi bi-grid-fill"></i></span>
                <span class="sb-text">Categorías</span>
            </a>

        <?php elseif ($rol === ROL_USUARIO): ?>
            <div class="nav-section-label"><span>Inicio</span></div>

            <a class="sb-link <?= $paginaActual === 'dashboard.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/dashboard">
                <span class="sb-icon sb-icon--home"><i class="bi bi-house-fill"></i></span>
                <span class="sb-text">Mi inicio</span>
            </a>

            <div class="nav-section-label"><span>Explorar</span></div>

            <a class="sb-link <?= $paginaActual === 'ver_recetas.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/recetas">
                <span class="sb-icon sb-icon--explore"><i class="bi bi-compass-fill"></i></span>
                <span class="sb-text">Explorar recetas</span>
            </a>
            <a class="sb-link <?= $paginaActual === 'favoritos.php' ? 'is-active' : '' ?>" href="<?= $baseUrl ?>/favoritos">
                <span class="sb-icon sb-icon--heart"><i class="bi bi-heart-fill"></i></span>
                <span class="sb-text">Favoritos</span>
            </a>
            <a class="sb-link" href="<?= $baseUrl ?>/categorias">
                <span class="sb-icon sb-icon--grid"><i class="bi bi-grid-fill"></i></span>
                <span class="sb-text">Categorías</span>
            </a>

        <?php else: ?>
            <div class="nav-section-label"><span>Descubrir</span></div>

            <a class="sb-link" href="<?= $baseUrl ?>/recetas">
                <span class="sb-icon sb-icon--explore"><i class="bi bi-compass-fill"></i></span>
                <span class="sb-text">Ver recetas</span>
            </a>
        <?php endif; ?>

        <div class="sb-divider"></div>
        <div class="nav-section-label"><span>Herramientas</span></div>

        <button type="button" class="sb-link sb-btn sb-btn--bot" onclick="document.getElementById('chatbotToggle')?.click()" title="Consultar con el asistente culinario">
            <span class="sb-icon sb-icon--bot"><i class="bi bi-robot"></i></span>
            <span class="sb-text">Chef Bot</span>
            <span class="sb-chip sb-chip--ai"><i class="bi bi-stars"></i> IA</span>
        </button>

        <button type="button" class="sb-link sb-btn sb-btn--theme" id="darkToggleBtn" aria-label="Cambiar tema claro u oscuro">
            <span class="sb-icon sb-icon--theme"><i class="bi bi-moon-stars-fill" id="darkToggleIcon"></i></span>
            <span class="sb-text" id="darkToggleLabel">Modo oscuro</span>
            <span class="theme-switch-indicator" aria-hidden="true">
                <span class="theme-switch-thumb"></span>
            </span>
        </button>

    </nav>

    <!-- Footer del Sidebar -->
    <div class="sidebar-footer">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="<?= $baseUrl ?>/perfil" class="user-card-link" title="Ir a mi perfil">
                <div class="user-card">
                    <div class="user-avatar-wrap">
                        <?php if (function_exists('avatarSesion')): ?>
                            <?= avatarSesion(38) ?>
                        <?php else: ?>
                            <div class="user-avatar"><?= strtoupper(mb_substr($nombre, 0, 1)) ?></div>
                        <?php endif; ?>
                        <span class="avatar-status-dot" title="En línea"></span>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($nombre) ?></span>
                        <?php if ($email): ?>
                            <span class="user-email"><?= htmlspecialchars($email) ?></span>
                        <?php endif; ?>
                        <span class="badge-rol badge-<?= $rol ?: 'usuario' ?>">
                            <i class="bi bi-circle-fill"></i>
                            <?= ucfirst($rol ?: 'usuario') ?>
                        </span>
                    </div>
                    <i class="bi bi-chevron-right user-card-arrow"></i>
                </div>
            </a>
            <a class="sb-logout" href="<?= $baseUrl ?>/logout" title="Cerrar mi sesión actual">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        <?php else: ?>
            <div class="sb-auth-btns">
                <a href="<?= $baseUrl ?>/login" class="sb-auth-btn sb-auth-btn--primary">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Iniciar sesión</span>
                </a>
                <a href="<?= $baseUrl ?>/register" class="sb-auth-btn sb-auth-btn--outline">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Registrarse</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

</aside>

<script>
(function () {
    'use strict';

    const STORAGE_KEY = 'gd_theme';
    const SIDEBAR_KEY = 'gd_sidebar';
    const html       = document.documentElement;
    const body       = document.body;
    const darkBtn    = document.getElementById('darkToggleBtn');
    const darkIcon   = document.getElementById('darkToggleIcon');
    const darkLbl    = document.getElementById('darkToggleLabel');
    const toggleBtn  = document.getElementById('sidebarToggle');
    const toggleIcon = document.getElementById('sidebarToggleIcon');
    const openBtn    = document.getElementById('sidebarOpenBtn');
    const backdrop   = document.getElementById('sidebarBackdrop');

    // ── Tema oscuro ──────────────────────────────────────────────────────────
    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        const isDark = theme === 'dark';
        if (darkIcon) {
            darkIcon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
        if (darkLbl) {
            darkLbl.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
        }
        if (darkBtn) {
            darkBtn.setAttribute('data-active-theme', theme);
        }
    }
    applyTheme(localStorage.getItem(STORAGE_KEY) || 'light');

    darkBtn?.addEventListener('click', () => {
        const nextTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
    });

    // ── Sidebar ──────────────────────────────────────────────────────────────
    function isMobile()    { return window.innerWidth < 992; }
    function isCollapsed() { return body.classList.contains('sidebar-collapsed'); }

    function updateToggleIcon(collapsed) {
        if (!toggleIcon) return;
        if (isMobile()) {
            toggleIcon.className = 'bi bi-x-lg';
        } else {
            toggleIcon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
        }
    }

    function openSidebar() {
        if (isMobile()) {
            body.classList.add('sidebar-open');
        } else {
            body.classList.remove('sidebar-collapsed');
            localStorage.setItem(SIDEBAR_KEY, 'open');
        }
        openBtn?.classList.remove('visible');
        updateToggleIcon(false);
    }

    function closeSidebar() {
        if (isMobile()) {
            body.classList.remove('sidebar-open');
        } else {
            body.classList.add('sidebar-collapsed');
            localStorage.setItem(SIDEBAR_KEY, 'collapsed');
        }
        openBtn?.classList.add('visible');
        updateToggleIcon(true);
    }

    // Estado inicial
    if (isMobile()) {
        openBtn?.classList.add('visible');
        updateToggleIcon(true);
    } else {
        const savedState = localStorage.getItem(SIDEBAR_KEY) || 'open';
        if (savedState === 'collapsed') {
            body.classList.add('sidebar-collapsed');
            openBtn?.classList.add('visible');
            updateToggleIcon(true);
        } else {
            updateToggleIcon(false);
        }
    }

    toggleBtn?.addEventListener('click', () => {
        if (isMobile()) {
            body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
        } else {
            isCollapsed() ? openSidebar() : closeSidebar();
        }
    });

    openBtn?.addEventListener('click', openSidebar);
    backdrop?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-open');
            const saved = localStorage.getItem(SIDEBAR_KEY) || 'open';
            if (saved === 'collapsed') {
                body.classList.add('sidebar-collapsed');
                openBtn?.classList.add('visible');
                updateToggleIcon(true);
            } else {
                body.classList.remove('sidebar-collapsed');
                openBtn?.classList.remove('visible');
                updateToggleIcon(false);
            }
        } else {
            if (body.classList.contains('sidebar-open')) {
                openBtn?.classList.remove('visible');
                updateToggleIcon(false);
            } else {
                openBtn?.classList.add('visible');
                updateToggleIcon(true);
            }
        }
    });
})();
</script>
