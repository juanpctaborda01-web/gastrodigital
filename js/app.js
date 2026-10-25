/**
 * GastroDigital — app.js  v10.0
 * Sidebar colapso/expansión en desktop, apertura en móvil, dark mode corregido
 */
document.addEventListener('DOMContentLoaded', function () {

    // ════════════════════════════════════════════════════════════════════
    //  1. SIDEBAR — colapso en desktop / apertura en móvil
    // ════════════════════════════════════════════════════════════════════
    const sidebar    = document.getElementById('sbSidebar');
    const backdrop   = document.getElementById('sbBackdrop');
    const btnToggle  = document.getElementById('sbToggle');
    const isMobile   = () => window.innerWidth < 1024;

    // Leer estado guardado en desktop
    const savedCollapsed = localStorage.getItem('sbCollapsed') === 'true';
    if (!isMobile() && savedCollapsed) {
        sidebar?.classList.add('is-collapsed');
        document.body.classList.add('sb-is-collapsed');
    }

    function collapseSidebar() {
        sidebar?.classList.add('is-collapsed');
        document.body.classList.add('sb-is-collapsed');
        localStorage.setItem('sbCollapsed', 'true');
    }
    function expandSidebar() {
        sidebar?.classList.remove('is-collapsed');
        document.body.classList.remove('sb-is-collapsed');
        localStorage.setItem('sbCollapsed', 'false');
    }
    function openMobile() {
        sidebar?.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile() {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    btnToggle?.addEventListener('click', () => {
        if (isMobile()) {
            // En móvil: abre/cierra overlay
            sidebar?.classList.contains('is-open') ? closeMobile() : openMobile();
        } else {
            // En desktop: colapsa/expande
            sidebar?.classList.contains('is-collapsed') ? expandSidebar() : collapseSidebar();
        }
    });

    backdrop?.addEventListener('click', closeMobile);

    // Cerrar al navegar en móvil
    document.querySelectorAll('.sb-item').forEach(l => {
        l.addEventListener('click', () => {
            if (isMobile() && !l.classList.contains('sb-item--btn')) closeMobile();
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (isMobile()) closeMobile();
            else expandSidebar();
        }
    });

    // Animación X en hamburguesa cuando está abierto (móvil)
    const observer = new MutationObserver(() => {
        const isOpen = sidebar?.classList.contains('is-open') ?? false;
        if (isOpen) {
            btnToggle?.classList.add('is-open');
            btnToggle?.setAttribute('aria-label', 'Cerrar menú');
            btnToggle?.setAttribute('aria-expanded', 'true');
        } else {
            btnToggle?.classList.remove('is-open');
            btnToggle?.setAttribute('aria-label', 'Abrir menú');
            btnToggle?.setAttribute('aria-expanded', 'false');
        }
    });
    if (sidebar) observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

    // ════════════════════════════════════════════════════════════════════
    //  2. DARK MODE — aplica al cargar desde localStorage
    // ════════════════════════════════════════════════════════════════════
    const darkToggle   = document.getElementById('darkModeToggle');
    const darkCheckbox = document.getElementById('darkCheckbox');

    function applyTheme(dark) {
        if (dark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            document.documentElement.classList.remove('dark');
        }
        if (darkCheckbox) darkCheckbox.checked = dark;
        localStorage.setItem('gdTheme', dark ? 'dark' : 'light');
    }

    // Aplicar al cargar (antes del paint ya lo aplica el <script> inline en header)
    const savedTheme = localStorage.getItem('gdTheme');
    applyTheme(savedTheme === 'dark');

    darkToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        applyTheme(!isDark);
    });

    darkCheckbox?.addEventListener('change', () => {
        applyTheme(darkCheckbox.checked);
    });

    // ════════════════════════════════════════════════════════════════════
    //  3. SCROLL A ANCLA EN URL
    // ════════════════════════════════════════════════════════════════════
    const hash = window.location.hash;
    if (hash) {
        const target = document.querySelector(hash);
        if (target) {
            setTimeout(() => {
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
                target.style.transition = 'background .4s';
                target.style.background = 'rgba(232,168,56,.08)';
                setTimeout(() => { target.style.background = ''; }, 1500);
            }, 250);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    //  4. AUTO-CERRAR ALERTAS (4 seg)
    // ════════════════════════════════════════════════════════════════════
    document.querySelectorAll('.alert').forEach(alerta => {
        setTimeout(() => {
            try { bootstrap.Alert.getOrCreateInstance(alerta).close(); } catch (_) {}
        }, 4000);
    });

    // ════════════════════════════════════════════════════════════════════
    //  5. TOOLTIPS BOOTSTRAP
    // ════════════════════════════════════════════════════════════════════
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // ════════════════════════════════════════════════════════════════════
    //  6. CONFIRMACIÓN ANTES DE ELIMINAR
    // ════════════════════════════════════════════════════════════════════
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });

    // ════════════════════════════════════════════════════════════════════
    //  7. PREVIEW AVATAR EN PERFIL
    // ════════════════════════════════════════════════════════════════════
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const preview = document.getElementById(this.dataset.preview);
            if (!preview || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    // ════════════════════════════════════════════════════════════════════
    //  8. TOGGLE FAVORITOS (corazón animado)
    // ════════════════════════════════════════════════════════════════════
    document.addEventListener('click', async event => {
        if (!(event.target instanceof Element)) return;
        const btn = event.target.closest('.btn-fav');
        if (!btn || btn.disabled) return;

        const id = btn.dataset.id;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!id || !csrf) {
            alert('No se pudo validar la sesión. Recarga la página e inténtalo de nuevo.');
            return;
        }

        btn.disabled = true;
        try {
            const res = await fetch(window.BASE_URL + '/favorito', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ receta_id: id, csrf_token: csrf })
            });
            const data = await res.json();
            if (!res.ok || typeof data.activo !== 'boolean') {
                throw new Error(data.error || 'No se pudo actualizar el favorito.');
            }

            btn.classList.toggle('btn-danger', data.activo);
            btn.classList.toggle('btn-outline-danger', !data.activo);
            btn.setAttribute('aria-pressed', String(data.activo));
            const icon = btn.querySelector('i');
            icon?.classList.toggle('bi-heart-fill', data.activo);
            icon?.classList.toggle('bi-heart', !data.activo);

            const label = btn.querySelector('[data-fav-label]');
            if (label) label.textContent = data.activo ? 'Guardada en favoritos' : 'Me gusta';
            btn.title = data.activo ? 'Quitar de favoritos' : 'Guardar en favoritos';
            btn.setAttribute('aria-label', btn.title);

            const favoriteCard = document.getElementById('favorito-' + id);
            if (!data.activo && favoriteCard) {
                favoriteCard.style.transition = 'opacity .3s, transform .3s';
                favoriteCard.style.opacity = '0';
                favoriteCard.style.transform = 'scale(.95)';
                setTimeout(() => {
                    favoriteCard.remove();
                    if (!document.querySelector('[id^="favorito-"]')) location.reload();
                }, 300);
            }
        } catch (error) {
            alert(error.message || 'No se pudo actualizar el favorito. Intenta de nuevo.');
        } finally {
            btn.disabled = false;
        }
    });

    // ════════════════════════════════════════════════════════════════════
    //  9. ANIMACIÓN DE ENTRADA PARA STAT-CARDS
    // ════════════════════════════════════════════════════════════════════
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('gd-stat-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.gd-stat-card, .gd-metric-card, .stat-card').forEach(card => {
        revealObserver.observe(card);
    });

    // ════════════════════════════════════════════════════════════════════
    //  10. CONTADOR ANIMADO EN NÚMEROS DE STATS
    // ════════════════════════════════════════════════════════════════════
    function animateCounter(el) {
        const target = parseInt(el.textContent.replace(/\D/g, ''), 10);
        if (!target || target > 9999) return;
        let current = 0;
        const step  = Math.max(1, Math.floor(target / 40));
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current.toLocaleString('es');
            if (current >= target) clearInterval(timer);
        }, 30);
    }

    const counterObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.stat-number, .gd-metric-number').forEach(animateCounter);
                counterObs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-card, .gd-metric-card').forEach(c => counterObs.observe(c));

});

// Exponer BASE_URL para fetch
window.BASE_URL = (function () {
    const m = document.querySelector('meta[name="base-url"]');
    return m ? m.content : '';
})();
