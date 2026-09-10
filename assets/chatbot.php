<?php
$baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : 'http://localhost/GastroDigital 2.0', '/');
?>
<!-- ══════════════════════════════════════════
     CHATBOT FLOTANTE — Chef Bot
══════════════════════════════════════════ -->

<!-- Botón flotante -->
<button id="chatbotToggle" class="chatbot-toggle" aria-label="Abrir asistente de recetas">
    <i class="bi bi-chat-dots-fill" id="chatbotIcon"></i>
    <span class="chatbot-badge" id="chatbotBadge" style="display:none">1</span>
</button>

<!-- Ventana del chat -->
<div class="chatbot-window" id="chatbotWindow" role="dialog"
     aria-label="Asistente de recetas" aria-hidden="true">

    <!-- Header -->
    <div class="chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <div class="chatbot-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="chatbot-name">Chef Bot</div>
                <div class="chatbot-status">
                    <span class="chatbot-dot"></span>
                    <span id="chatbotStatusText">Conectando…</span>
                </div>
            </div>
        </div>
        <button class="chatbot-close" id="chatbotClose" aria-label="Cerrar chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Mensajes -->
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chat-msg bot">
            <div class="chat-bubble">
                👨‍🍳 ¡Hola! Soy <strong>Chef Bot</strong>, tu asistente de recetas.<br><br>
                Puedo ayudarte a encontrar recetas por nombre, ingrediente, tiempo o dificultad.<br><br>
                Puedo responder preguntas sobre recetas, ingredientes, preparación y platos típicos de distintos países. Escribe <em>"ayuda"</em> para ver todo lo que puedo hacer.
            </div>
            <div class="chat-time"><?= date('H:i') ?></div>
        </div>
    </div>

    <!-- Sugerencias rápidas -->
    <div class="chatbot-suggestions" id="chatbotSuggestions">
        <button class="suggestion-chip" data-msg="Recetas rápidas">⏱️ Recetas rápidas</button>
        <button class="suggestion-chip" data-msg="Recetas fáciles">🟢 Recetas fáciles</button>
        <button class="suggestion-chip" data-msg="¿Qué categorías hay?">🏷️ Categorías</button>
        <button class="suggestion-chip" data-msg="Muéstrame las más populares">🔥 Populares</button>
    </div>

    <!-- Input (deshabilitado hasta obtener token CSRF) -->
    <div class="chatbot-input-area">
        <form id="chatbotForm" autocomplete="off">
            <div class="chatbot-input-wrap">
                <input type="text"
                       id="chatbotInput"
                       class="chatbot-input"
                       placeholder="Escribe tu pregunta..."
                       maxlength="200"
                       aria-label="Mensaje para Chef Bot"
                       disabled>
                <button type="submit" class="chatbot-send" aria-label="Enviar mensaje" disabled>
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    const BASE     = '<?= $baseUrl ?>';
    const toggle   = document.getElementById('chatbotToggle');
    const win      = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const form     = document.getElementById('chatbotForm');
    const input    = document.getElementById('chatbotInput');
    const sendBtn  = form.querySelector('button[type="submit"]');
    const messages = document.getElementById('chatbotMessages');
    const badge    = document.getElementById('chatbotBadge');
    const icon     = document.getElementById('chatbotIcon');
    const statusTxt = document.getElementById('chatbotStatusText');

    let isOpen       = false;
    let pendingBadge = 0;
    let csrfToken    = null;   // token CSRF propio del chatbot

    // ── 1. Obtener token CSRF del chatbot al cargar ─────────────────────────
    fetch(BASE + '/api/chatbot/token', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            csrfToken = data.csrf ?? null;
            if (csrfToken) {
                input.disabled   = false;
                sendBtn.disabled = false;
                statusTxt.textContent = 'En línea';
            } else {
                statusTxt.textContent = 'No disponible';
            }
        })
        .catch(() => {
            statusTxt.textContent = 'Sin conexión';
        });

    // ── 2. Abrir / cerrar ───────────────────────────────────────────────────
    function openChat() {
        isOpen = true;
        win.classList.add('open');
        win.setAttribute('aria-hidden', 'false');
        icon.classList.replace('bi-chat-dots-fill', 'bi-x-lg');
        toggle.classList.add('active');
        badge.style.display = 'none';
        pendingBadge = 0;
        setTimeout(() => { if (!input.disabled) input.focus(); }, 300);
    }

    function closeChat() {
        isOpen = false;
        win.classList.remove('open');
        win.setAttribute('aria-hidden', 'true');
        icon.classList.replace('bi-x-lg', 'bi-chat-dots-fill');
        toggle.classList.remove('active');
    }

    toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
    closeBtn.addEventListener('click', closeChat);
    // Cerrar con Escape
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && isOpen) closeChat(); });

    // ── 3. Sugerencias rápidas ──────────────────────────────────────────────
    document.querySelectorAll('.suggestion-chip').forEach(btn => {
        btn.addEventListener('click', function () {
            enviarMensaje(this.dataset.msg);
        });
    });

    // ── 4. Enviar formulario ────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const texto = input.value.trim();
        if (!texto || !csrfToken) return;
        input.value = '';
        enviarMensaje(texto);
    });

    // ── 5. Función principal de envío ───────────────────────────────────────
    function enviarMensaje(texto) {
        if (!csrfToken) {
            agregarMensaje('⚠️ El chat no está disponible. Recarga la página e intenta de nuevo.', 'bot');
            return;
        }

        agregarMensaje(texto, 'user');
        bloquearInput(true);
        mostrarTyping();

        fetch(BASE + '/api/chatbot', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje: texto, csrf: csrfToken })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(d => { throw new Error(d.error || 'Error ' + res.status); });
            }
            return res.json();
        })
        .then(data => {
            quitarTyping();
            const respuesta = data.respuesta || 'Lo siento, no pude procesar tu pregunta.';
            agregarMensaje(respuesta, 'bot');

            if (!isOpen) {
                pendingBadge++;
                badge.textContent   = pendingBadge;
                badge.style.display = 'flex';
            }
        })
        .catch(err => {
            quitarTyping();
            agregarMensaje('⚠️ ' + (err.message || 'Error de conexión. Verifica que el servidor esté activo.'), 'bot');
        })
        .finally(() => bloquearInput(false));
    }

    // ── 6. Helpers de UI ────────────────────────────────────────────────────
    function agregarMensaje(texto, tipo) {
        const wrap   = document.createElement('div');
        wrap.className = 'chat-msg ' + tipo;

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        // Sanitizar HTML para prevenir XSS, luego convertir saltos y URLs
        bubble.innerHTML = texto
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>')
            // Convertir URLs planas en enlaces clicables
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');

        const time = document.createElement('div');
        time.className   = 'chat-time';
        time.textContent = horaActual();

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        messages.appendChild(wrap);
        scrollBottom();
    }

    function mostrarTyping() {
        const div = document.createElement('div');
        div.className = 'chat-msg bot typing-wrap';
        div.id = 'typingIndicator';
        div.innerHTML = `<div class="chat-bubble typing"><span></span><span></span><span></span></div>`;
        messages.appendChild(div);
        scrollBottom();
    }

    function quitarTyping() {
        document.getElementById('typingIndicator')?.remove();
    }

    function bloquearInput(bloquear) {
        input.disabled   = bloquear;
        sendBtn.disabled = bloquear;
    }

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function horaActual() {
        const n = new Date();
        return String(n.getHours()).padStart(2, '0') + ':' + String(n.getMinutes()).padStart(2, '0');
    }
})();
</script>
