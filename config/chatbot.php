<?php
/**
 * config/chatbot.php — Configuración del Chef Bot con Ollama.
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  REQUISITOS                                                      │
 * │  1. Ollama instalado en Windows:  https://ollama.com/download    │
 * │  2. Modelo descargado:            ollama pull llama3.2:3b        │
 * │  3. Servidor activo:              ollama serve                   │
 * │     (o inicia Ollama desde el menú de inicio — corre en          │
 * │      segundo plano automáticamente en Windows)                   │
 * │                                                                  │
 * │  VERIFICAR QUE FUNCIONA                                          │
 * │  Abre PowerShell y ejecuta:                                      │
 * │    Invoke-RestMethod -Uri http://localhost:11434/api/generate `  │
 * │      -Method Post -ContentType 'application/json' `              │
 * │      -Body '{"model":"llama3.2:3b","prompt":"Hola","stream":false}'│
 * │                                                                  │
 * │  Si ves una respuesta JSON con "response", Ollama está activo.   │
 * └──────────────────────────────────────────────────────────────────┘
 */

// ── Ollama ────────────────────────────────────────────────────────────────────

// URL base de Ollama (no cambiar salvo que uses un puerto diferente)
define('OLLAMA_URL',   'http://localhost:11434/api/generate');

// Modelo a usar — debe estar descargado con: ollama pull llama3.2:3b
define('OLLAMA_MODEL', 'llama3.2:3b');

// Timeout en segundos para la respuesta de Ollama.
// llama3.2:3b es rápido, pero en hardware lento puede necesitar más tiempo.
define('OLLAMA_TIMEOUT', 60);

// ── Compatibilidad (mantiene la constante antigua por si algo la referencia) ──
// No se usa para las llamadas; Ollama no requiere API key.
if (!defined('ANTHROPIC_API_KEY')) {
    define('ANTHROPIC_API_KEY', '');
}
