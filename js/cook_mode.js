/**
 * cook_mode.js — Modo Cocinar GastroDigital
 *
 * Responsabilidades:
 *  - Carrusel de pasos con estado de completado.
 *  - Checklist de ingredientes con persistencia en sessionStorage.
 *  - Temporizador por paso (iniciar / pausar / reanudar / reiniciar / notificación).
 *  - Progreso visual (barra global + puntos de navegación).
 *  - Sincronización del estado de ingredientes entre la pantalla inicial y el sidebar.
 *  - Pantalla de finalización.
 *  - Gestos swipe horizontal en móvil.
 *
 * No depende de librerías externas. Usa únicamente el DOM y sessionStorage.
 */

(function () {
    'use strict';

    // ── 1. DATOS Y CONFIGURACIÓN ──────────────────────────────────────────────

    /** @type {{ recetaId: number, baseUrl: string, pasos: Array, ingredientes: Array }} */
    const DATA = window.GD_COOK;

    if (!DATA || !Array.isArray(DATA.pasos)) {
        console.error('[CookMode] GD_COOK no está definido.');
        return;
    }

    const PASOS        = DATA.pasos;        // [{numero, descripcion, imagen, duracion}]
    const INGREDIENTES = DATA.ingredientes; // [{id, nombre, cantidad, unidad, opcional}]
    const RECETA_ID    = DATA.recetaId;
    const TOTAL_PASOS  = PASOS.length;

    // Claves de almacenamiento (scoped por receta)
    const STORAGE_KEY_ING   = `gd_cook_ing_${RECETA_ID}`;
    const STORAGE_KEY_PASOS = `gd_cook_pasos_${RECETA_ID}`;

    // ── 2. ESTADO DE LA APLICACIÓN ────────────────────────────────────────────

    const state = {
        /** Índice actual (0-based) */
        pasoActual: 0,
        /** Set de índices de pasos completados */
        pasosCompletados: new Set(),
        /** Set de índices de ingredientes marcados */
        ingMarcados: new Set(),
        /** Pantalla activa: 'ingredientes' | 'pasos' | 'fin' */
        pantalla: 'ingredientes',
    };

    const pasoImageCache = new Map();

    function precargarPasoImagen(url) {
        if (!url || pasoImageCache.has(url)) return;

        const img = new Image();
        img.decoding = 'async';
        img.loading = 'eager';
        img.fetchPriority = 'high';
        img.src = url;
        pasoImageCache.set(url, true);
    }

    // ── 3. TEMPORIZADOR ───────────────────────────────────────────────────────

    // ── 4. PERSISTENCIA (sessionStorage) ─────────────────────────────────────

    function cargarEstado() {
        try {
            const ingStr   = sessionStorage.getItem(STORAGE_KEY_ING);
            const pasosStr = sessionStorage.getItem(STORAGE_KEY_PASOS);
            if (ingStr)   JSON.parse(ingStr).forEach(i => state.ingMarcados.add(i));
            if (pasosStr) JSON.parse(pasosStr).forEach(i => state.pasosCompletados.add(i));
        } catch (_) { /* sessionStorage no disponible o dato corrupto */ }
    }

    function persistirIngredientes() {
        try {
            sessionStorage.setItem(STORAGE_KEY_ING, JSON.stringify([...state.ingMarcados]));
        } catch (_) {}
    }

    function persistirPasos() {
        try {
            sessionStorage.setItem(STORAGE_KEY_PASOS, JSON.stringify([...state.pasosCompletados]));
        } catch (_) {}
    }

    // ── 5. REFERENCIAS AL DOM ─────────────────────────────────────────────────

    // Pantallas
    const $screenIng   = document.getElementById('screenIngredientes');
    const $screenPasos = document.getElementById('screenPasos');
    const $screenFin   = document.getElementById('screenFin');

    // Cabecera
    const $headerCounter  = document.getElementById('headerStepCounter');
    const $progressFill   = document.getElementById('globalProgressFill');
    const $progressBar    = document.getElementById('globalProgressBar');
    const $btnSalir       = document.getElementById('btnSalirModo');

    // Pantalla ingredientes
    const $ingList        = document.getElementById('ingList');
    const $ingCheckedCount = document.getElementById('ingCheckedCount');
    const $ingMiniBar     = document.getElementById('ingMiniBar');
    const $btnIniciar     = document.getElementById('btnIniciarCocina');

    // Pantalla pasos
    const $pasoNumLabel   = document.getElementById('pasoNumLabel');
    const $pasoNumeroBadge = document.getElementById('pasoNumeroBadge');
    const $pasoDesc       = document.getElementById('pasoDesc');
    const $pasoImgWrap    = document.getElementById('pasoImgWrap');
    const $pasoImg        = document.getElementById('pasoImg');
    const $pasoTip        = document.getElementById('pasoTip');
    const $btnAnterior    = document.getElementById('btnAnterior');
    const $btnSiguiente   = document.getElementById('btnSiguiente');
    const $lblSiguiente   = document.getElementById('lblSiguiente');
    const $stepDots       = document.getElementById('stepDots');
    const $btnMarcar      = document.getElementById('btnMarcarCompletado');
    const $iconCheck      = document.getElementById('iconCheck');
    const $labelCheck     = document.getElementById('labelCheck');

    // Mini-ingredientes móvil
    const $btnMiniIng     = document.getElementById('btnMiniIngredientes');
    const $miniIngPanel   = document.getElementById('miniIngPanel');
    const $btnCerrarMini  = document.getElementById('btnCerrarMiniIng');

    // Sidebar desktop
    const $sidebarCounter = document.getElementById('sidebarIngCounter');
    const $sidebarList    = document.getElementById('sidebarIngList');

    // Pantalla final
    const $finIngCount    = document.getElementById('finIngCount');
    const $btnRevisar     = document.getElementById('btnRevisarPasos');

    // ── 6. RENDERIZADO ────────────────────────────────────────────────────────

    /** Actualiza la barra de progreso global y el contador. */
    function renderProgreso() {
        let porcentaje = 0;
        if (state.pantalla === 'ingredientes') {
            // Progreso basado en ingredientes marcados
            const total = INGREDIENTES.length;
            porcentaje = total > 0
                ? Math.round((state.ingMarcados.size / total) * 30) // max 30% en esta fase
                : 0;
            $headerCounter && ($headerCounter.textContent = 'Preparación');
        } else if (state.pantalla === 'pasos') {
            const completados = state.pasosCompletados.size;
            porcentaje = TOTAL_PASOS > 0
                ? 30 + Math.round((completados / TOTAL_PASOS) * 70)
                : 30;
            $headerCounter && ($headerCounter.textContent =
                `Paso ${state.pasoActual + 1} de ${TOTAL_PASOS}`);
        } else if (state.pantalla === 'fin') {
            porcentaje = 100;
            $headerCounter && ($headerCounter.textContent = '¡Completado!');
        }

        if ($progressFill) $progressFill.style.width = porcentaje + '%';
        if ($progressBar)  $progressBar.setAttribute('aria-valuenow', porcentaje);
    }

    /** Actualiza los puntos de navegación (step dots). */
    function renderDots() {
        if (!$stepDots) return;
        const dots = $stepDots.querySelectorAll('.cm-step-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-current', i === state.pasoActual);
            dot.classList.toggle('is-done',    state.pasosCompletados.has(i) && i !== state.pasoActual);
            dot.setAttribute('aria-label', `Ir al paso ${i + 1}${state.pasosCompletados.has(i) ? ' (completado)' : ''}`);
            dot.setAttribute('aria-selected', i === state.pasoActual ? 'true' : 'false');
        });
    }

    /** Construye los puntos de navegación (solo la primera vez). */
    function construirDots() {
        if (!$stepDots) return;
        $stepDots.innerHTML = '';
        PASOS.forEach((_, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cm-step-dot';
            btn.setAttribute('role', 'tab');
            btn.setAttribute('data-index', i);
            btn.setAttribute('aria-label', `Ir al paso ${i + 1}`);
            btn.addEventListener('click', () => irAPaso(i));
            $stepDots.appendChild(btn);
        });
    }

    /** Renderiza el paso en el índice indicado. */
    function renderPaso(index) {
        if (index < 0 || index >= TOTAL_PASOS) return;
        const paso = PASOS[index];

        // Número y texto
        if ($pasoNumeroBadge) $pasoNumeroBadge.textContent = paso.numero;
        if ($pasoNumLabel)    $pasoNumLabel.textContent    = `Paso ${index + 1} de ${TOTAL_PASOS}`;
        if ($pasoDesc)        $pasoDesc.textContent        = paso.descripcion;

        // Imagen
        if ($pasoImgWrap && $pasoImg) {
            if (paso.imagen) {
                $pasoImg.src = paso.imagen;
                $pasoImg.alt = `Imagen del paso ${paso.numero}`;
                $pasoImg.loading = 'eager';
                $pasoImg.decoding = 'async';
                $pasoImg.fetchPriority = 'high';
                $pasoImgWrap.hidden = false;
                const siguiente = PASOS[index + 1]?.imagen;
                if (siguiente) precargarPasoImagen(siguiente);
            } else {
                $pasoImgWrap.hidden = true;
                $pasoImg.src = '';
                $pasoImg.alt = '';
            }
        }

        // Consejo contextual
        if ($pasoTip) {
            const consejo = generarConsejoPaso(index, paso);
            if (consejo) {
                $pasoTip.innerHTML = `<i class="bi bi-lightbulb-fill" aria-hidden="true"></i> ${consejo}`;
                $pasoTip.hidden = false;
            } else {
                $pasoTip.hidden = true;
            }
        }

        // Botón marcar completado
        actualizarBtnMarcar(index);

        // Botones de navegación
        if ($btnAnterior) {
            $btnAnterior.disabled = index === 0;
        }
        if ($btnSiguiente && $lblSiguiente) {
            const esUltimo = index === TOTAL_PASOS - 1;
            $lblSiguiente.textContent = esUltimo ? 'Finalizar receta' : 'Siguiente';
            $btnSiguiente.setAttribute('aria-label', esUltimo ? 'Finalizar receta' : 'Siguiente paso');
        }

        // Dots
        renderDots();
        renderProgreso();
    }

    /** Actualiza el aspecto del botón de marcar completado. */
    function actualizarBtnMarcar(index) {
        if (!$btnMarcar) return;
        const completado = state.pasosCompletados.has(index);
        $btnMarcar.classList.toggle('is-checked', completado);
        $btnMarcar.setAttribute('aria-pressed', completado ? 'true' : 'false');

        if ($iconCheck) {
            $iconCheck.className = completado
                ? 'bi bi-check-circle-fill'
                : 'bi bi-check-circle';
        }
        if ($labelCheck) {
            $labelCheck.textContent = completado ? 'Paso completado ✓' : 'Marcar como completado';
        }
    }

    /** Genera un consejo contextual para el paso actual. */
    function generarConsejoPaso(index, paso) {
        if (index === 0) {
            return 'Lee esta instrucción completa antes de empezar.';
        }
        if (index === TOTAL_PASOS - 1) {
            return 'Último paso. Revisa que todo esté bien antes de finalizar.';
        }
        const pendientes = TOTAL_PASOS - state.pasosCompletados.size;
        if (pendientes <= 2 && index > 0) {
            return `Te quedan <strong>${pendientes}</strong> paso${pendientes !== 1 ? 's' : ''} por completar.`;
        }
        return null;
    }

    /** Actualiza el contador de ingredientes en la pantalla inicial. */
    function renderContadorIng() {
        const total     = INGREDIENTES.length;
        const marcados  = state.ingMarcados.size;
        const pct       = total > 0 ? Math.round((marcados / total) * 100) : 0;

        if ($ingCheckedCount) $ingCheckedCount.textContent = marcados;
        if ($ingMiniBar)      $ingMiniBar.style.width      = pct + '%';

        // Sidebar desktop
        if ($sidebarCounter) $sidebarCounter.textContent = `${marcados}/${total}`;

        renderProgreso();
    }

    /** Sincroniza el estado visual de los checkboxes de ingredientes en todas las listas. */
    function sincronizarIngredientes() {
        // Pantalla inicial
        if ($ingList) {
            $ingList.querySelectorAll('.cm-ing-item').forEach(item => {
                const idx = parseInt(item.dataset.ingIndex, 10);
                const cb  = item.querySelector('.cm-ing-checkbox');
                const marcado = state.ingMarcados.has(idx);
                item.classList.toggle('is-checked', marcado);
                if (cb) cb.checked = marcado;
            });
        }

        // Sidebar desktop
        if ($sidebarList) {
            $sidebarList.querySelectorAll('.cm-sidebar-item').forEach(item => {
                const idx = parseInt(item.dataset.ingIndex, 10);
                const marcado = state.ingMarcados.has(idx);
                item.classList.toggle('is-checked', marcado);
                const icon = item.querySelector('i');
                if (icon) {
                    icon.className = marcado ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                }
            });
        }

        // Panel móvil
        const $miniList = document.getElementById('miniIngList');
        if ($miniList) {
            $miniList.querySelectorAll('.cm-mini-ing-item').forEach(item => {
                const idx = parseInt(item.dataset.ingIndex, 10);
                const marcado = state.ingMarcados.has(idx);
                item.classList.toggle('is-checked', marcado);
                const icon = item.querySelector('.cm-mini-ing-icon');
                if (icon) {
                    icon.className = marcado
                        ? 'bi bi-check-circle-fill cm-mini-ing-icon'
                        : 'bi bi-circle cm-mini-ing-icon';
                }
            });
        }

        renderContadorIng();
    }

    // ── 7. CAMBIOS DE PANTALLA ────────────────────────────────────────────────

    /** Muestra la pantalla indicada y oculta las demás. */
    function mostrarPantalla(nombre) {
        state.pantalla = nombre;

        const pantallas = {
            ingredientes: $screenIng,
            pasos:        $screenPasos,
            fin:          $screenFin,
        };

        Object.entries(pantallas).forEach(([key, el]) => {
            if (!el) return;
            const activa = key === nombre;
            el.classList.toggle('cm-screen--active', activa);
            el.hidden = !activa;

            // Accesibilidad: gestión de display via CSS vs hidden
            if (activa) {
                el.removeAttribute('hidden');
                el.classList.add('cm-screen--active');
                el.style.display = '';
            } else {
                el.setAttribute('hidden', '');
                el.classList.remove('cm-screen--active');
                el.style.display = '';
            }
        });

        renderProgreso();
    }

    // ── 8. NAVEGACIÓN DE PASOS ────────────────────────────────────────────────

    /** Navega al paso en el índice dado. */
    function irAPaso(index) {
        if (index < 0 || index >= TOTAL_PASOS) return;
        state.pasoActual = index;
        renderPaso(index);
        // Scroll al top de la tarjeta en móvil
        if ($screenPasos) {
            $screenPasos.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /** Avanza al siguiente paso o abre la pantalla final. */
    function siguiente() {
        if (state.pasoActual < TOTAL_PASOS - 1) {
            irAPaso(state.pasoActual + 1);
        } else {
            // Último paso: ir a pantalla final
            abrirPantallaFin();
        }
    }

    /** Retrocede al paso anterior. */
    function anterior() {
        if (state.pasoActual > 0) {
            irAPaso(state.pasoActual - 1);
        }
    }

    /** Abre la pantalla de finalización. */
    function abrirPantallaFin() {
        if ($finIngCount) $finIngCount.textContent = state.ingMarcados.size;
        mostrarPantalla('fin');
    }

    // ── 9. GESTIÓN DE EVENTOS ─────────────────────────────────────────────────

    /** Registra todos los event listeners. */
    function registrarEventos() {

        // ── Botón iniciar cocina
        if ($btnIniciar) {
            $btnIniciar.addEventListener('click', () => {
                mostrarPantalla('pasos');
                construirDots();
                renderPaso(state.pasoActual);
            });
        }

        // ── Navegación pasos
        if ($btnAnterior) $btnAnterior.addEventListener('click', anterior);
        if ($btnSiguiente) $btnSiguiente.addEventListener('click', siguiente);

        // ── Marcar paso completado
        if ($btnMarcar) {
            $btnMarcar.addEventListener('click', () => {
                const idx = state.pasoActual;
                if (state.pasosCompletados.has(idx)) {
                    state.pasosCompletados.delete(idx);
                } else {
                    state.pasosCompletados.add(idx);
                }
                persistirPasos();
                actualizarBtnMarcar(idx);
                renderDots();
                renderProgreso();
            });
        }

        // ── Checkboxes de ingredientes (pantalla inicial)
        if ($ingList) {
            $ingList.addEventListener('change', e => {
                const cb = e.target.closest('.cm-ing-checkbox');
                if (!cb) return;
                const item = cb.closest('.cm-ing-item');
                if (!item) return;
                const idx = parseInt(item.dataset.ingIndex, 10);
                if (cb.checked) {
                    state.ingMarcados.add(idx);
                } else {
                    state.ingMarcados.delete(idx);
                }
                persistirIngredientes();
                sincronizarIngredientes();
            });
        }

        // ── Panel ingredientes móvil (abrir)
        if ($btnMiniIng) {
            $btnMiniIng.addEventListener('click', () => {
                const abierto = !$miniIngPanel.hidden;
                $miniIngPanel.hidden = abierto;
                $btnMiniIng.setAttribute('aria-expanded', (!abierto).toString());
                if (!abierto) {
                    // Foco al botón de cerrar
                    $btnCerrarMini && $btnCerrarMini.focus();
                }
            });
        }

        // ── Panel ingredientes móvil (cerrar)
        if ($btnCerrarMini) {
            $btnCerrarMini.addEventListener('click', () => {
                $miniIngPanel.hidden = true;
                $btnMiniIng && $btnMiniIng.setAttribute('aria-expanded', 'false');
                $btnMiniIng && $btnMiniIng.focus();
            });
        }

        // ── Revisar pasos desde pantalla final
        if ($btnRevisar) {
            $btnRevisar.addEventListener('click', () => {
                mostrarPantalla('pasos');
                irAPaso(0);
            });
        }

        // ── Salir del modo: confirmación si hay progreso
        if ($btnSalir) {
            $btnSalir.addEventListener('click', e => {
                const hayProgreso = state.pasosCompletados.size > 0
                    || state.ingMarcados.size > 0;
                if (state.pantalla === 'fin') return; // Si terminó, salir libremente
                if (hayProgreso) {
                    e.preventDefault();
                    const href = $btnSalir.href;
                    // Confirmación accesible sin alert intrusivo
                    mostrarConfirmacionSalir(href);
                }
            });
        }

        // ── Navegación por teclado (flechas) en pantalla de pasos
        document.addEventListener('keydown', e => {
            if (state.pantalla !== 'pasos') return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === 'ArrowRight') { e.preventDefault(); siguiente(); }
            if (e.key === 'ArrowLeft')  { e.preventDefault(); anterior();  }
        });

        // ── Gestos swipe horizontal (móvil)
        registrarSwipe();
    }

    /** Muestra una confirmación inline (sin alert) para salir del modo. */
    function mostrarConfirmacionSalir(href) {
        // Crear modal de confirmación inline accesible
        let modal = document.getElementById('cmSalirModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'cmSalirModal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'cmSalirTitle');
            modal.innerHTML = `
                <div class="cm-confirm-backdrop"></div>
                <div class="cm-confirm-box">
                    <h3 id="cmSalirTitle" class="cm-confirm-title">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        ¿Salir del Modo Cocinar?
                    </h3>
                    <p class="cm-confirm-body">
                        Si sales ahora, el progreso de esta sesión se perderá cuando cierres la ventana.
                    </p>
                    <div class="cm-confirm-actions">
                        <button type="button" class="cm-btn cm-btn--outline" id="cmSalirCancelar">
                            Continuar cocinando
                        </button>
                        <a href="${escapeHtml(href)}" class="cm-btn cm-btn--primary" id="cmSalirConfirmar">
                            Salir
                        </a>
                    </div>
                </div>`;
            document.body.appendChild(modal);

            const estilos = `
                #cmSalirModal { position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center; }
                .cm-confirm-backdrop { position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(2px); }
                .cm-confirm-box { position:relative;background:#fff;border-radius:18px;padding:2rem;max-width:420px;width:90%;box-shadow:0 16px 48px rgba(0,0,0,.25);display:flex;flex-direction:column;gap:1rem; }
                .cm-confirm-title { font-size:1.15rem;font-weight:800;color:var(--text);display:flex;align-items:center;gap:.5rem; }
                .cm-confirm-title i { color:var(--accent); }
                .cm-confirm-body { font-size:.92rem;color:var(--text-m);line-height:1.6;margin:0; }
                .cm-confirm-actions { display:flex;gap:.75rem;justify-content:flex-end;flex-wrap:wrap; }
            `;
            const styleEl = document.createElement('style');
            styleEl.textContent = estilos;
            document.head.appendChild(styleEl);
        }

        modal.style.display = 'flex';
        const btnCancelar = document.getElementById('cmSalirCancelar');
        if (btnCancelar) {
            btnCancelar.focus();
            btnCancelar.onclick = () => { modal.style.display = 'none'; };
        }
        const backdrop = modal.querySelector('.cm-confirm-backdrop');
        if (backdrop) {
            backdrop.onclick = () => { modal.style.display = 'none'; };
        }
    }

    /** Registra detección de swipe horizontal para cambiar de paso. */
    function registrarSwipe() {
        let startX = null;
        let startY = null;
        const UMBRAL_PX  = 60;
        const UMBRAL_RATIO = 1.5; // más horizontal que vertical

        const el = $screenPasos;
        if (!el) return;

        el.addEventListener('touchstart', e => {
            const t = e.touches[0];
            startX = t.clientX;
            startY = t.clientY;
        }, { passive: true });

        el.addEventListener('touchend', e => {
            if (startX === null) return;
            const t    = e.changedTouches[0];
            const dx   = t.clientX - startX;
            const dy   = t.clientY - startY;
            startX = null;
            startY = null;

            if (Math.abs(dx) < UMBRAL_PX) return;
            if (Math.abs(dy) !== 0 && Math.abs(dx) / Math.abs(dy) < UMBRAL_RATIO) return;

            if (dx < 0) siguiente(); // swipe izquierda → siguiente
            else         anterior();  // swipe derecha  → anterior
        }, { passive: true });
    }

    /** Escapa HTML para uso en atributos */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // ── 10. INICIALIZACIÓN ────────────────────────────────────────────────────

    function init() {
        // Cargar estado guardado
        cargarEstado();

        // Configurar estado inicial de las pantallas
        // La pantalla de ingredientes está activa por defecto (desde PHP)
        // Pero aseguramos coherencia con el estado JS
        mostrarPantalla('ingredientes');
        sincronizarIngredientes();
        renderProgreso();

        // Registrar todos los eventos
        registrarEventos();

        // Solicitar permiso de notificaciones (no bloqueante)
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }

        // Limpiar al salir de la página
        window.addEventListener('beforeunload', () => {});
    }

    // Arrancar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* ══════════════════════════════════════════════════════════════════════════
   CHEFBOT INLINE — Módulo del asistente de cocina integrado en el Modo Cocinar
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // Esperar a que GD_COOK_BOT esté disponible (se define en cook_mode.php)
    const BOT = window.GD_COOK_BOT;
    if (!BOT) return;

    // ── Referencias DOM ───────────────────────────────────────────────────
    const $panel       = document.getElementById('cmChefbotPanel');
    const $fab         = document.getElementById('cmChefbotFab');
    const $fabClose    = $fab   ? $fab.querySelector('.cm-chefbot-fab-close') : null;
    const $fabImg      = $fab   ? $fab.querySelector('img') : null;
    const $fabBadge    = document.getElementById('cmChefbotBadge');
    const $messages    = document.getElementById('cmChefbotMessages');
    const $form        = document.getElementById('cmChefbotForm');
    const $input       = document.getElementById('cmChefbotInput');
    const $sendBtn     = document.getElementById('cmChefbotSendBtn');
    const $statusText  = document.getElementById('cmChefbotStatusText');
    const $clearBtn    = document.getElementById('cmChefbotClearBtn');
    const $closeBtn    = document.getElementById('cmChefbotCloseBtn');
    const $btnMiniBot  = document.getElementById('btnMiniChefbot');
    const $chips       = document.querySelectorAll('.cm-cb-chip');

    if (!$panel || !$messages || !$form) return;

    // ── Estado ────────────────────────────────────────────────────────────
    let panelAbierto = false;
    let cargando     = false;
    let pendientes   = 0;

    // Identificador de sesión de chat (scoped a esta receta)
    const chatId = obtenerChatId(BOT.storageKey);

    // ── Backdrop (móvil) ──────────────────────────────────────────────────
    let $backdrop = document.getElementById('cmChefbotBackdrop');
    if (!$backdrop) {
        $backdrop = document.createElement('div');
        $backdrop.id = 'cmChefbotBackdrop';
        $backdrop.className = 'cm-chefbot-backdrop';
        $backdrop.setAttribute('aria-hidden', 'true');
        document.body.appendChild($backdrop);
    }
    $backdrop.addEventListener('click', cerrarPanel);

    // ── Abrir / cerrar ────────────────────────────────────────────────────

    function abrirPanel() {
        panelAbierto = true;
        $panel.removeAttribute('hidden');
        $panel.setAttribute('aria-hidden', 'false');

        // FAB
        if ($fab) {
            $fab.setAttribute('aria-expanded', 'true');
            if ($fabImg)   $fabImg.hidden   = true;
            if ($fabClose) $fabClose.hidden = false;
        }
        if ($btnMiniBot) $btnMiniBot.setAttribute('aria-expanded', 'true');

        // Backdrop en móvil
        const esMobil = window.innerWidth <= 900;
        if (esMobil) {
            $backdrop.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        }

        // Resetear badge
        pendientes = 0;
        if ($fabBadge) $fabBadge.hidden = true;

        // Foco al input
        setTimeout(() => $input && $input.focus(), 200);
    }

    function cerrarPanel() {
        panelAbierto = false;
        $panel.setAttribute('hidden', '');
        $panel.setAttribute('aria-hidden', 'true');

        if ($fab) {
            $fab.setAttribute('aria-expanded', 'false');
            if ($fabImg)   $fabImg.hidden   = false;
            if ($fabClose) $fabClose.hidden  = true;
        }
        if ($btnMiniBot) $btnMiniBot.setAttribute('aria-expanded', 'false');

        $backdrop.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    function togglePanel() {
        panelAbierto ? cerrarPanel() : abrirPanel();
    }

    if ($fab)       $fab.addEventListener('click', togglePanel);
    if ($btnMiniBot) $btnMiniBot.addEventListener('click', togglePanel);
    if ($closeBtn)  $closeBtn.addEventListener('click', cerrarPanel);

    // ── Limpiar conversación ──────────────────────────────────────────────
    if ($clearBtn) {
        $clearBtn.addEventListener('click', () => {
            if (cargando) return;
            setLoading(true);
            fetch(BOT.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'limpiar',
                    csrf_token: BOT.csrfToken,
                    chat_id: chatId,
                }),
            })
            .then(r => r.ok ? r.json() : Promise.reject('HTTP ' + r.status))
            .then(() => {
                // Mantener solo el primer mensaje (bienvenida)
                const msgs = $messages.querySelectorAll('.cm-cb-msg');
                msgs.forEach((el, i) => { if (i > 0) el.remove(); });
            })
            .catch(err => {
                console.error('[ChefBot Cook]', err);
            })
            .finally(() => setLoading(false));
        });
    }

    // ── Chips de sugerencias ──────────────────────────────────────────────
    $chips.forEach(chip => {
        chip.addEventListener('click', function () {
            if (cargando) return;
            enviar(this.dataset.msg);
        });
    });

    // ── Auto-resize textarea ──────────────────────────────────────────────
    if ($input) {
        $input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
        $input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $form.requestSubmit();
            }
        });
    }

    // ── Envío del formulario ──────────────────────────────────────────────
    $form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!$input) return;
        const texto = $input.value.trim();
        if (!texto || cargando) return;
        enviar(texto);
        $input.value = '';
        $input.style.height = 'auto';
    });

    // ── Función de envío al endpoint ──────────────────────────────────────

    function enviar(texto) {
        agregarMensaje(texto, 'user');
        mostrarTyping();
        setLoading(true);

        // Construir el mensaje con contexto de la receta actual si no lo tiene
        const contextoPrefix = texto.toLowerCase().includes(BOT.recetaNombre.toLowerCase())
            ? ''
            : '';
        // Nota: el backend ya sabe del contexto si el user menciona la receta.
        // Para asegurar relevancia, enviamos el texto tal cual — las sugerencias
        // ya incluyen el nombre de la receta. Si el usuario escribe libremente,
        // anteponemos contexto solo si la pregunta es corta y genérica.
        const mensajeEnvio = esConsultaGenerica(texto)
            ? `Sobre la receta "${BOT.recetaNombre}": ${texto}`
            : texto;

        fetch(BOT.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mensaje:    mensajeEnvio,
                csrf_token: BOT.csrfToken,
                chat_id:    chatId,
            }),
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            quitarTyping();
            setLoading(false);
            const respuesta = data.respuesta || 'Lo siento, no pude procesar tu consulta.';
            const tipo      = data.tipo || 'general';
            agregarMensaje(respuesta, 'bot', tipo);

            // Badge si el panel está cerrado
            if (!panelAbierto) {
                pendientes++;
                if ($fabBadge) {
                    $fabBadge.textContent = pendientes > 9 ? '9+' : pendientes;
                    $fabBadge.hidden = false;
                }
            }
        })
        .catch(err => {
            quitarTyping();
            setLoading(false);
            console.error('[ChefBot Cook]', err);
            agregarMensaje(
                '⚠️ No pude conectarme al asistente. Verifica que el servidor esté activo.',
                'bot', 'error'
            );
        });
    }

    // ── Renderizado de mensajes ───────────────────────────────────────────

    function agregarMensaje(texto, rol, tipo) {
        const wrap   = document.createElement('div');
        wrap.className = `cm-cb-msg cm-cb-msg--${rol}`;

        const bubble = document.createElement('div');
        bubble.className = 'cm-cb-bubble';

        // Badge de tipo para mensajes del bot
        if (rol === 'bot' && tipo && tipo !== 'general' && tipo !== 'error') {
            const badge = document.createElement('div');
            const etiquetas = { oficial: '✅ Receta oficial', catalogo: '📋 Del catálogo', no_encontrada: '🔍 Sin coincidencias' };
            badge.className = `cm-cb-tipo-badge cm-cb-tipo-${tipo}`;
            badge.textContent = etiquetas[tipo] || tipo;
            bubble.appendChild(badge);
        }

        const content = document.createElement('span');
        content.innerHTML = renderMarkdown(texto);
        bubble.appendChild(content);

        const time = document.createElement('div');
        time.className = 'cm-cb-time';
        time.textContent = horaActual();

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        $messages.appendChild(wrap);
        scrollBottom();
    }

    function mostrarTyping() {
        quitarTyping();
        const div = document.createElement('div');
        div.className = 'cm-cb-msg cm-cb-msg--bot cm-cb-typing';
        div.id = 'cmCbTyping';
        div.innerHTML = `<div class="cm-cb-bubble">
            <span class="cm-cb-typing-dot"></span>
            <span class="cm-cb-typing-dot"></span>
            <span class="cm-cb-typing-dot"></span>
        </div>`;
        $messages.appendChild(div);
        scrollBottom();
    }

    function quitarTyping() {
        const el = document.getElementById('cmCbTyping');
        if (el) el.remove();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    function setLoading(estado) {
        cargando = estado;
        if ($sendBtn) $sendBtn.disabled = estado;
        if ($input)   $input.disabled   = estado;
        if ($statusText) {
            $statusText.textContent = estado ? 'Pensando…' : 'Asistente de cocina';
        }
    }

    function scrollBottom() {
        requestAnimationFrame(() => {
            $messages.scrollTop = $messages.scrollHeight;
        });
    }

    function horaActual() {
        const n = new Date();
        return String(n.getHours()).padStart(2, '0') + ':' +
               String(n.getMinutes()).padStart(2, '0');
    }

    /** Determina si la consulta es genérica (sin mención a la receta) y es corta */
    function esConsultaGenerica(texto) {
        const lower = texto.toLowerCase();
        const nombreLower = BOT.recetaNombre.toLowerCase();
        return texto.length < 80
            && !lower.includes(nombreLower)
            && !lower.includes('receta')
            && !lower.includes('cómo')
            && !lower.includes('como');
    }

    /** Renderiza Markdown básico a HTML de forma segura */
    function renderMarkdown(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/^[\-•]\s+(.+)$/gm, '<li>$1</li>')
            .replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>')
            .replace(/\n/g, '<br>');
    }

    /** Obtiene o genera el chat ID para esta sesión de receta */
    function obtenerChatId(storageKey) {
        try {
            let id = sessionStorage.getItem(storageKey + '_chatid');
            if (!/^[a-f0-9]{64}$/.test(id || '')) {
                const bytes = new Uint8Array(32);
                crypto.getRandomValues(bytes);
                id = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
                sessionStorage.setItem(storageKey + '_chatid', id);
            }
            return id;
        } catch (_) {
            const bytes = new Uint8Array(32);
            crypto.getRandomValues(bytes);
            return Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
        }
    }

    // ── El panel del ChefBot es siempre un drawer (desktop y móvil) ─────
    // Se abre/cierra con el FAB o el botón de la barra de pasos.
    // No se fuerza abierto en ningún tamaño de pantalla.

    // ── Limpiar al salir ──────────────────────────────────────────────────
    window.addEventListener('beforeunload', () => {
        $backdrop.classList.remove('is-visible');
        document.body.style.overflow = '';
    });

})();
