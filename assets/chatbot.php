<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/GastroDigital-11-2';
?>
<!-- ══════════════════════════════════════════
     CHATBOT FLOTANTE — GastroDigital
══════════════════════════════════════════ -->

<!-- Botón flotante para abrir/cerrar -->
<button id="chatbotToggle" class="chatbot-toggle" aria-label="Abrir asistente de recetas">
    <i class="bi bi-chat-dots-fill" id="chatbotIcon"></i>
    <span class="chatbot-badge" id="chatbotBadge" style="display:none">1</span>
</button>

<!-- Ventana del chat -->
<div class="chatbot-window" id="chatbotWindow" role="dialog" aria-label="Asistente de recetas" aria-hidden="true">

    <!-- Header -->
    <div class="chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <div class="chatbot-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="chatbot-name">Chef Bot</div>
                <div class="chatbot-status">
                    <span class="chatbot-dot"></span> En línea
                </div>
            </div>
        </div>
        <button class="chatbot-close" id="chatbotClose" aria-label="Cerrar chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Mensajes -->
    <div class="chatbot-messages" id="chatbotMessages">
        <!-- Mensaje de bienvenida inicial -->
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
        <button class="suggestion-chip" data-msg="Muéstrame todas las recetas">📚 Ver todas</button>
    </div>

    <!-- Input -->
    <div class="chatbot-input-area">
        <form id="chatbotForm" autocomplete="off">
            <div class="chatbot-input-wrap">
                <input type="text"
                       id="chatbotInput"
                       class="chatbot-input"
                       placeholder="Escribe tu pregunta..."
                       maxlength="200"
                       aria-label="Mensaje para el chatbot">
                <button type="submit" class="chatbot-send" aria-label="Enviar mensaje">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const BASE_URL   = '<?= $baseUrl ?>';
    const toggle     = document.getElementById('chatbotToggle');
    const window_    = document.getElementById('chatbotWindow');
    const closeBtn   = document.getElementById('chatbotClose');
    const form       = document.getElementById('chatbotForm');
    const input      = document.getElementById('chatbotInput');
    const messages   = document.getElementById('chatbotMessages');
    const badge      = document.getElementById('chatbotBadge');
    const icon       = document.getElementById('chatbotIcon');
    const suggestions = document.querySelectorAll('.suggestion-chip');

    let isOpen       = false;
    let pendingBadge = 0;

    // ── Abrir / cerrar ──────────────────────────────────────────────────────
    function openChat() {
        isOpen = true;
        window_.classList.add('open');
        window_.setAttribute('aria-hidden', 'false');
        icon.classList.replace('bi-chat-dots-fill', 'bi-x-lg');
        toggle.classList.add('active');
        badge.style.display = 'none';
        pendingBadge = 0;
        setTimeout(() => input.focus(), 300);
    }

    function closeChat() {
        isOpen = false;
        window_.classList.remove('open');
        window_.setAttribute('aria-hidden', 'true');
        icon.classList.replace('bi-x-lg', 'bi-chat-dots-fill');
        toggle.classList.remove('active');
    }

    toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
    closeBtn.addEventListener('click', closeChat);

    // ── Sugerencias rápidas ─────────────────────────────────────────────────
    suggestions.forEach(btn => {
        btn.addEventListener('click', function () {
            enviarMensaje(this.dataset.msg);
        });
    });

    // ── Enviar formulario ───────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const texto = input.value.trim();
        if (!texto) return;
        enviarMensaje(texto);
        input.value = '';
    });

    // ── Función principal de envío ──────────────────────────────────────────
    function enviarMensaje(texto) {
        agregarMensaje(texto, 'user');
        mostrarTyping();

        fetch(BASE_URL + '/index.php?route=chatbot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje: texto, csrf_token: '<?= csrfToken() ?>' })
        })
        .then(res => res.json())
        .then(data => {
            quitarTyping();
            const respuesta = data.respuesta || 'Lo siento, no pude procesar tu pregunta.';
            agregarMensaje(respuesta, 'bot');

            // Badge si el chat está cerrado
            if (!isOpen) {
                pendingBadge++;
                badge.textContent = pendingBadge;
                badge.style.display = 'flex';
            }
        })
        .catch(() => {
            quitarTyping();
            agregarMensaje('⚠️ Error de conexión. Verifica que el servidor esté activo.', 'bot');
        });
    }

    // ── Agregar burbuja de mensaje ──────────────────────────────────────────
    function agregarMensaje(texto, tipo) {
        const wrap = document.createElement('div');
        wrap.className = 'chat-msg ' + tipo;

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        // Convertir saltos de línea en <br> y respetar HTML básico del bot
        bubble.innerHTML = texto.replace(/\n/g, '<br>');

        const time = document.createElement('div');
        time.className = 'chat-time';
        time.textContent = horaActual();

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        messages.appendChild(wrap);
        scrollBottom();
    }

    // ── Indicador "escribiendo..." ──────────────────────────────────────────
    function mostrarTyping() {
        const div = document.createElement('div');
        div.className = 'chat-msg bot typing-wrap';
        div.id = 'typingIndicator';
        div.innerHTML = `
            <div class="chat-bubble typing">
                <span></span><span></span><span></span>
            </div>`;
        messages.appendChild(div);
        scrollBottom();
    }

    function quitarTyping() {
        const el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function horaActual() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' +
               now.getMinutes().toString().padStart(2, '0');
    }
})();
</script>
