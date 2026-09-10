/**
 * GastroDigital — app.js  v3.0
 * JS global que corre en todas las páginas.
 * Cargado por fooder.php después de Bootstrap.
 */

(function () {
    'use strict';

    // ── Leer BASE_URL desde el meta tag que inyecta header.php ────────────
    const BASE = (document.querySelector('meta[name="base-url"]')?.content ?? '').replace(/\/$/, '');

    // ════════════════════════════════════════════════════════════════════════
    //  1. Bootstrap helpers: alertas auto-close, tooltips, popovers
    // ════════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function () {

        // Cerrar alertas flash tras 4 s (sin tocar mensajes inline de formularios)
        document.querySelectorAll('.alert[class*="alert-"]').forEach(el => {
            if (el.closest('#reportarMsg, #comentarMsg, #valorarMsg, #perfilReporteMsg')) return;
            setTimeout(() => {
                try { bootstrap.Alert.getOrCreateInstance(el).close(); }
                catch (_) { el.remove(); }
            }, 4000);
        });

        // Tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));

        // Popovers
        document.querySelectorAll('[data-bs-toggle="popover"]')
            .forEach(el => new bootstrap.Popover(el));

        // Navbar: añadir sombra al hacer scroll
        const navbar = document.getElementById('mainNavbar');
        if (navbar) {
            window.addEventListener('scroll', function () {
                navbar.classList.toggle('scrolled', window.scrollY > 20);
            });
        }

        // Toggle de favoritos (UI inmediata)
        document.querySelectorAll('.btn-favorito').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const icon = this.querySelector('i');
                if (icon.classList.contains('bi-heart')) {
                    icon.classList.replace('bi-heart', 'bi-heart-fill');
                    this.classList.replace('btn-outline-danger', 'btn-danger');
                } else {
                    icon.classList.replace('bi-heart-fill', 'bi-heart');
                    this.classList.replace('btn-danger', 'btn-outline-danger');
                }
            });
        });

        // Confirmación data-confirm
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', e => {
                if (!confirm(el.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
            });
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  2. PREVIEW DE IMAGEN — GLOBAL
    //     Cualquier <input type="file" data-preview-target="#miImg"> o
    //     <input type="file" data-preview="miId"> muestra la imagen elegida.
    //     Si el selector no encuentra un elemento <img>, crea una miniatura
    //     automática justo debajo del input.
    // ════════════════════════════════════════════════════════════════════════
    document.addEventListener('change', function (e) {
        const input = e.target;
        if (input.type !== 'file') return;

        const targetSel = input.dataset.previewTarget || ('#' + input.dataset.preview);
        const file      = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = ev => {
            let preview = targetSel ? document.querySelector(targetSel) : null;

            // Si no hay selector o no se encuentra, crear miniatura al lado del input
            if (!preview) {
                const existente = input.parentElement.querySelector('.img-preview-auto');
                if (existente) existente.remove();

                preview = document.createElement('img');
                preview.className = 'img-preview-auto rounded mt-2';
                preview.style.cssText = 'max-width:180px;max-height:140px;object-fit:cover;border:2px solid var(--border,#ddd);display:block';
                input.parentElement.appendChild(preview);
            }

            preview.src           = ev.target.result;
            preview.style.display = 'block';

            // Animación sutil de entrada
            preview.style.opacity    = '0';
            preview.style.transition = 'opacity .25s ease';
            requestAnimationFrame(() => { preview.style.opacity = '1'; });
        };
        reader.readAsDataURL(file);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  3. BEFOREUNLOAD — aviso de cambios sin guardar
    //     Cualquier <form data-unsaved="true"> activa la advertencia al salir.
    //     El aviso se cancela si el formulario se envía normalmente.
    // ════════════════════════════════════════════════════════════════════════
    const formsGuardados = new WeakSet();   // formularios que ya se enviaron

    document.addEventListener('input', e => {
        const form = e.target.closest('form[data-unsaved]');
        if (form && !formsGuardados.has(form)) form.dataset.dirty = '1';
    });

    document.addEventListener('change', e => {
        const form = e.target.closest('form[data-unsaved]');
        if (form && !formsGuardados.has(form)) form.dataset.dirty = '1';
    });

    document.addEventListener('submit', e => {
        const form = e.target.closest('form');
        if (form) {
            formsGuardados.add(form);
            delete form.dataset.dirty;
        }
    });

    window.addEventListener('beforeunload', e => {
        const dirty = document.querySelector('form[data-unsaved][data-dirty="1"]');
        if (dirty) {
            e.preventDefault();
            e.returnValue = 'Tienes cambios sin guardar. ¿Quieres salir de todas formas?';
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //  4. NOTIFICACIONES EN VIVO — polling cada 30 s
    //     Solo corre si el usuario está logueado (meta[name="user-rol"]).
    //     Admin: badge rojo en el link de Moderación con reportes pendientes.
    //     Chef:  badge azul en Mis Recetas con comentarios nuevos.
    // ════════════════════════════════════════════════════════════════════════
    const userRol = document.querySelector('meta[name="user-rol"]')?.content ?? '';

    if (userRol && BASE) {
        let badgeModeracion = null;
        let badgeMisRecetas = null;

        function inyectarBadges() {
            if (userRol === 'admin') {
                const linkMod = document.querySelector('.sidebar-nav a[href*="moderacion"]');
                if (linkMod && !linkMod.querySelector('.notif-badge')) {
                    badgeModeracion = document.createElement('span');
                    badgeModeracion.className = 'notif-badge badge bg-danger ms-auto';
                    badgeModeracion.style.cssText = 'font-size:.65rem;padding:2px 6px;border-radius:10px;display:none';
                    linkMod.style.display = 'flex';
                    linkMod.style.justifyContent = 'space-between';
                    linkMod.appendChild(badgeModeracion);
                }
            }

            if (userRol === 'chef') {
                const linkRecetas = document.querySelector('.sidebar-nav a[href*="mis_recetas"], .sidebar-nav a[href*="mis-recetas"]');
                if (linkRecetas && !linkRecetas.querySelector('.notif-badge')) {
                    badgeMisRecetas = document.createElement('span');
                    badgeMisRecetas.className = 'notif-badge badge bg-primary ms-auto';
                    badgeMisRecetas.style.cssText = 'font-size:.65rem;padding:2px 6px;border-radius:10px;display:none';
                    linkRecetas.style.display = 'flex';
                    linkRecetas.style.justifyContent = 'space-between';
                    linkRecetas.appendChild(badgeMisRecetas);
                }
            }
        }

        function actualizarBadge(el, n) {
            if (!el) return;
            if (n > 0) {
                el.textContent = n > 99 ? '99+' : n;
                el.style.display = '';
                el.animate(
                    [{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }],
                    { duration: 300 }
                );
            } else {
                el.style.display = 'none';
            }
        }

        function pollNotificaciones() {
            fetch(BASE + '/api/notificaciones', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    inyectarBadges();
                    if (data.reportes_pendientes !== undefined) {
                        actualizarBadge(badgeModeracion, data.reportes_pendientes);
                        document.querySelectorAll('[data-notif="reportes"]').forEach(el => {
                            el.textContent   = data.reportes_pendientes || '';
                            el.style.display = data.reportes_pendientes > 0 ? '' : 'none';
                        });
                    }
                    if (data.comentarios_nuevos !== undefined) {
                        actualizarBadge(badgeMisRecetas, data.comentarios_nuevos);
                    }
                })
                .catch(() => {}); // silencioso — no mostrar errores de red al usuario
        }

        document.addEventListener('DOMContentLoaded', () => {
            inyectarBadges();
            pollNotificaciones();
            setInterval(pollNotificaciones, 30_000);
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  5. SIDEBAR — resaltar el link activo según la URL actual
    // ════════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        const path = location.pathname;
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(a => {
            if (a.href && a.href !== '#') {
                try {
                    const aPath = new URL(a.href).pathname;
                    if (path === aPath || (aPath !== '/' && path.startsWith(aPath))) {
                        a.classList.add('active');
                    }
                } catch (_) {}
            }
        });
    });

})();
