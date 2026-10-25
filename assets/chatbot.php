<?php
/**
 * assets/chatbot.php
 * Chatbot flotante — Chefbot (GastroDigital)
 * Se incluye al final del <body> en header.php o footer.
 */
$baseUrl    = defined('BASE_URL') ? BASE_URL : '';
$csrfToken  = function_exists('csrfToken') ? csrfToken() : ($_SESSION['csrf_token'] ?? '');
$horaActual = date('H:i');
$chatStorageKey = 'gastrodigital_chat_' . (isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 'visitante');
?>
<!-- ══════════════════════════════════════════════════
     CHEFBOT — Asistente gastronómico de GastroDigital
══════════════════════════════════════════════════ -->

<!-- Botón flotante -->
<button id="chatbotToggle"
        class="chatbot-toggle"
        aria-label="Abrir Chefbot"
        title="Chefbot — Asistente gastronómico">

    <!-- Ícono PNG cuando está cerrado -->
    <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/chefbot.png"
         alt="Chefbot"
         id="chatbotIconImg"
         style="width:38px;height:38px;object-fit:contain;display:block;filter:brightness(0) invert(1)">
    <!-- Ícono X cuando está abierto (oculto por defecto) -->
    <i class="bi bi-x-lg" id="chatbotIconX" style="display:none;font-size:1.4rem"></i>

    <span class="chatbot-badge"
          id="chatbotBadge"
          aria-hidden="true"
          style="display:none">1</span>
</button>



<!-- Ventana del chat -->
<div class="chatbot-window"
     id="chatbotWindow"
     role="dialog"
     aria-modal="true"
     aria-label="Chefbot — Asistente gastronómico"
     aria-hidden="true">

    <!-- ── Header ── -->
    <div class="chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <div class="chatbot-avatar" aria-hidden="true">
                <img src="<?= BASE_URL ?>/img/Icon/ICON-Sobrantes/chefbot.png"
                     alt="Chefbot"
                     style="width:22px;height:22px;object-fit:contain;filter:brightness(0) invert(1)">
            </div>
            <div>
                <div class="chatbot-name">Chefbot</div>
                <div class="chatbot-status">
                    <span class="chatbot-dot" aria-hidden="true"></span>
                    <span id="chatbotStatusText">En línea</span>
                </div>
                <div class="chatbot-tagline">Tu asistente de cocina</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="chatbot-action-btn"
                    id="chatbotClearBtn"
                    title="Limpiar conversación"
                    aria-label="Limpiar conversación">
                <i class="bi bi-trash3"></i>
            </button>
            <button class="chatbot-close"
                    id="chatbotClose"
                    aria-label="Cerrar Chefbot">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- ── Área de mensajes ── -->
    <div class="chatbot-messages" id="chatbotMessages" role="log" aria-live="polite">
        <!-- Mensaje de bienvenida -->
        <div class="chat-msg bot" data-time="<?= $horaActual ?>">
            <div class="chat-bubble">
                👨‍🍳 ¡Hola! Soy <strong>Chefbot</strong>. Busco recetas oficiales con sus ingredientes y pasos, y también resuelvo dudas de cocina.
            </div>
            <div class="chat-time"><?= $horaActual ?></div>
        </div>
    </div>

    <!-- ── Sugerencias rápidas ── -->
    <div class="chatbot-suggestions" id="chatbotSuggestions" aria-label="Sugerencias rápidas">
        <button class="suggestion-chip" data-msg="Dame una receta oficial de GastroDigital">🍽️ Receta oficial</button>
        <button class="suggestion-chip" data-msg="Recetas rápidas de menos de 30 minutos">⏱️ Menos de 30 min</button>
        <button class="suggestion-chip" data-msg="Recetas fáciles para principiantes">🟢 Recetas fáciles</button>
        <button class="suggestion-chip" data-msg="¿Qué categorías de recetas hay?">🏷️ Categorías</button>
    </div>

    <!-- ── Input ── -->
    <div class="chatbot-input-area">
        <form id="chatbotForm" autocomplete="off" novalidate>
            <div class="chatbot-input-wrap" id="chatbotInputWrap">
                <textarea id="chatbotInput"
                          class="chatbot-input"
                          placeholder="Pregúntame sobre recetas..."
                          maxlength="500"
                          rows="1"
                          aria-label="Mensaje para Chefbot"
                          aria-describedby="chatbotCharCount"></textarea>
                <button type="submit"
                        class="chatbot-send"
                        id="chatbotSendBtn"
                        aria-label="Enviar mensaje">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div class="chatbot-input-meta">
                <span id="chatbotCharCount" class="char-count" aria-live="polite">0 / 500</span>
                <span class="chatbot-powered">Powered by <strong>Ollama</strong></span>
            </div>
        </form>
    </div>
</div><!-- /chatbot-window -->

<script>
(function () {
    'use strict';

    // ── Constantes ────────────────────────────────────────────
    const BASE_URL    = <?= json_encode($baseUrl) ?>;
    const CSRF_TOKEN  = <?= json_encode($csrfToken) ?>;
    const CHAT_STORAGE_KEY = <?= json_encode($chatStorageKey) ?>;
    const ENDPOINT    = BASE_URL + '/index.php?route=chatbot';
    const CHAT_ID     = obtenerChatId();

    // ── Referencias DOM ───────────────────────────────────────
    const toggle      = document.getElementById('chatbotToggle');
    const win         = document.getElementById('chatbotWindow');
    const closeBtn    = document.getElementById('chatbotClose');
    const clearBtn    = document.getElementById('chatbotClearBtn');
    const form        = document.getElementById('chatbotForm');
    const input       = document.getElementById('chatbotInput');
    const messages    = document.getElementById('chatbotMessages');
    const badge       = document.getElementById('chatbotBadge');
    const iconImg     = document.getElementById('chatbotIconImg');
    const iconX       = document.getElementById('chatbotIconX');
    const statusText  = document.getElementById('chatbotStatusText');
    const sendBtn     = document.getElementById('chatbotSendBtn');
    const charCount   = document.getElementById('chatbotCharCount');
    const suggestions = document.querySelectorAll('.suggestion-chip');

    let isOpen       = false;
    let isLoading    = false;
    let historialCargado = false;
    let pendingBadge = 0;

    // ── Abrir / cerrar ────────────────────────────────────────
    function openChat() {
        isOpen = true;
        win.classList.add('open');
        win.setAttribute('aria-hidden', 'false');
        toggle.classList.add('active');
        if (iconImg) iconImg.style.display = 'none';
        if (iconX)   iconX.style.display   = 'block';
        badge.style.display = 'none';
        pendingBadge = 0;
        if (!historialCargado) cargarHistorial();
        setTimeout(() => input.focus(), 300);
    }

    function closeChat() {
        isOpen = false;
        win.classList.remove('open');
        win.setAttribute('aria-hidden', 'true');
        toggle.classList.remove('active');
        if (iconImg) iconImg.style.display = 'block';
        if (iconX)   iconX.style.display   = 'none';
    }

    toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
    closeBtn.addEventListener('click', closeChat);

    // Cerrar con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && isOpen) closeChat();
    });

    // ── Limpiar conversación ──────────────────────────────────
    clearBtn.addEventListener('click', () => {
        if (isLoading) return;
        setLoading(true);
        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'limpiar', csrf_token: CSRF_TOKEN, chat_id: CHAT_ID }),
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(() => {
            messages.querySelectorAll('.chat-msg').forEach((node, index) => {
                if (index > 0) node.remove();
            });
        })
        .catch(err => {
            console.error('[Chefbot] No se pudo limpiar el historial:', err);
            agregarMensaje('No pude limpiar la conversación guardada. Inténtalo de nuevo.', 'bot', 'error');
        })
        .finally(() => setLoading(false));
    });

    // ── Sugerencias ───────────────────────────────────────────
    suggestions.forEach(btn => {
        btn.addEventListener('click', function () {
            if (isLoading) return;
            enviar(this.dataset.msg);
        });
    });

    // ── Auto-resize textarea ──────────────────────────────────
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        const len = this.value.length;
        charCount.textContent = len + ' / 500';
        charCount.classList.toggle('char-count--warn', len > 440);
    });

    // Enter envía, Shift+Enter hace salto de línea
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    // ── Enviar formulario ─────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const texto = input.value.trim();
        if (!texto || isLoading) return;
        enviar(texto);
        input.value = '';
        input.style.height = 'auto';
        charCount.textContent = '0 / 500';
        charCount.classList.remove('char-count--warn');
    });

    // ── Función principal de envío ────────────────────────────
    function enviar(texto) {
        agregarMensaje(texto, 'user');
        mostrarTyping();
        setLoading(true);

        fetch(ENDPOINT, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ mensaje: texto, csrf_token: CSRF_TOKEN, chat_id: CHAT_ID }),
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            quitarTyping();
            setLoading(false);

            const texto = data.respuesta || 'Lo siento, no pude procesar tu mensaje.';
            const tipo  = data.tipo || 'general';
            agregarMensaje(texto, 'bot', tipo);

            if (!isOpen) {
                pendingBadge++;
                badge.textContent = pendingBadge > 9 ? '9+' : pendingBadge;
                badge.style.display = 'flex';
            }
        })
        .catch(err => {
            quitarTyping();
            setLoading(false);
            console.error('[Chefbot]', err);
            agregarMensaje(
                '⚠️ Chefbot no está disponible en este momento. Verifica que Ollama esté ejecutándose.',
                'bot', 'error'
            );
        });
    }

    function cargarHistorial() {
        setLoading(true);
        statusText.textContent = 'Cargando conversación…';
        fetch(ENDPOINT + '&chat_id=' + encodeURIComponent(CHAT_ID), { method: 'GET' })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            (data.mensajes || []).forEach(turno => {
                agregarMensaje(turno.mensaje, 'user');
                agregarMensaje(turno.respuesta, 'bot');
            });
            historialCargado = true;
        })
        .catch(err => {
            console.error('[Chefbot] No se pudo cargar el historial:', err);
            agregarMensaje('No pude recuperar la conversación guardada.', 'bot', 'error');
        })
        .finally(() => {
            setLoading(false);
            statusText.textContent = 'En línea';
        });
    }

    // ── Agregar burbuja ───────────────────────────────────────
    function agregarMensaje(texto, rol, tipo) {
        const wrap = document.createElement('div');
        wrap.className = 'chat-msg ' + rol;

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';

        // Badge de tipo para mensajes del bot
        if (rol === 'bot' && tipo && tipo !== 'general' && tipo !== 'error') {
            const badge = document.createElement('span');
            badge.className = 'chat-tipo-badge chat-tipo-' + tipo;
            const etiquetas = {
                oficial: 'Receta oficial',
                catalogo: 'Del catálogo',
                no_encontrada: 'Sin coincidencias'
            };
            badge.textContent = etiquetas[tipo] || tipo;
            bubble.appendChild(badge);
            bubble.appendChild(document.createElement('br'));
        }

        // Renderizar texto con Markdown básico
        const content = document.createElement('span');
        content.innerHTML = renderMarkdown(texto);
        bubble.appendChild(content);

        const time = document.createElement('div');
        time.className = 'chat-time';
        time.textContent = horaActual();

        wrap.appendChild(bubble);
        wrap.appendChild(time);

        // Insertar antes del área de sugerencias si existe
        messages.appendChild(wrap);
        scrollBottom();
    }

    // ── Typing indicator ──────────────────────────────────────
    function mostrarTyping() {
        quitarTyping(); // evitar duplicados
        const div = document.createElement('div');
        div.className = 'chat-msg bot';
        div.id = 'typingIndicator';
        div.innerHTML = '<div class="chat-bubble typing"><span></span><span></span><span></span></div>';
        messages.appendChild(div);
        scrollBottom();
    }

    function quitarTyping() {
        const el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    // ── Estado de carga ───────────────────────────────────────
    function setLoading(state) {
        isLoading = state;
        sendBtn.disabled = state;
        input.disabled   = state;
        statusText.textContent = state ? 'Pensando...' : 'En línea';
    }

    // ── Markdown básico → HTML ────────────────────────────────
    // Soporta: **negrita**, *cursiva*, `código`, saltos de línea, listas con -/•
    function renderMarkdown(texto) {
        return texto
            // Escapar HTML primero para seguridad
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            // Negrita
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Cursiva
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            // Código inline
            .replace(/`(.+?)`/g, '<code>$1</code>')
            // Listas con - o •
            .replace(/^[\-•]\s+(.+)$/gm, '<li>$1</li>')
            // Envolver listas en <ul>
            .replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>')
            // Saltos de línea → <br>
            .replace(/\n/g, '<br>');
    }

    // ── Helpers ───────────────────────────────────────────────
    function scrollBottom() {
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight;
        });
    }

    function horaActual() {
        const n = new Date();
        return String(n.getHours()).padStart(2, '0') + ':' +
               String(n.getMinutes()).padStart(2, '0');
    }

    function obtenerChatId() {
        try {
            let id = localStorage.getItem(CHAT_STORAGE_KEY);
            if (!/^[a-f0-9]{64}$/.test(id || '')) {
                const bytes = new Uint8Array(32);
                crypto.getRandomValues(bytes);
                id = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
                localStorage.setItem(CHAT_STORAGE_KEY, id);
            }
            return id;
        } catch (error) {
            console.warn('[Chefbot] El navegador no permite guardar el identificador de conversación.', error);
            return Array.from(crypto.getRandomValues(new Uint8Array(32)),
                byte => byte.toString(16).padStart(2, '0')).join('');
        }
    }

})();
</script>
