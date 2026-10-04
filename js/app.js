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
        if (sidebar?.classList.contains('is-open')) {
            btnToggle?.classList.add('is-open');
        } else {
            btnToggle?.classList.remove('is-open');
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
    document.querySelectorAll('.btn-fav').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                       || window.CSRF_TOKEN || '';
            if (!id) return;

            this.disabled = true;
            try {
                const res  = await fetch(window.BASE_URL + '/favorito', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `receta_id=${id}&csrf_token=${encodeURIComponent(csrf)}`
                });
                const data = await res.json();
                const icon = this.querySelector('i');
                if (data.activo) {
                    icon?.classList.replace('bi-heart', 'bi-heart-fill');
                    this.classList.replace('btn-outline-danger', 'btn-danger');
                } else {
                    icon?.classList.replace('bi-heart-fill', 'bi-heart');
                    this.classList.replace('btn-danger', 'btn-outline-danger');
                    // Si estamos en /favoritos, eliminar la card
                    document.getElementById('favorito-' + id)?.remove();
                    if (!document.querySelector('[id^="favorito-"]')) location.reload();
                }
            } catch (_) {
                alert('No se pudo actualizar. Intenta de nuevo.');
            }
            this.disabled = false;
        });
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
