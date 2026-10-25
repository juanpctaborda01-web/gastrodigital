<?php
/**
 * ajax/generar_imagen_paso.php  v2
 * ────────────────────────────────────────────────────────────────────
 * Endpoint AJAX exclusivo para chefs y admins.
 * Genera una fotografía realista del paso de receta usando
 * Pollinations.AI (modelo flux-realism — gratuito, sin clave API).
 *
 * Mejoras v2:
 *  - Prompt reformulado: describe la acción concreta + ingredientes,
 *    no el estilo genérico de ilustración.
 *  - Extrae ingredientes y verbos clave del texto del paso en español.
 *  - Modelo flux-realism en lugar de flux (más predecible para cocina).
 *  - Prompt negativo explícito via parámetro enhance=false.
 *
 * POST JSON:
 *   { "descripcion": "...", "receta": "...", "numero": 1,
 *     "ingredientes": ["arroz","azafrán",...], "csrf_token": "..." }
 *
 * Respuesta JSON:
 *   { "ok": true, "archivo": "paso-gen-xxx.jpg",
 *     "url": "BASE_URL/uploads/pasos/paso-gen-xxx.jpg",
 *     "prompt_debug": "..." }
 *   o { "ok": false, "error": "mensaje" }
 * ────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Solo POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ── Solo chefs o admins autenticados ─────────────────────────────────
if (!isLoggedIn() || (!hasRole(ROL_CHEF) && !hasRole(ROL_ADMIN))) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// ── Leer body JSON ────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw ?: '', true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cuerpo de petición inválido.']);
    exit;
}

// ── Validar CSRF ──────────────────────────────────────────────────────
$tokenPost   = (string)($body['csrf_token']     ?? '');
$tokenSesion = (string)($_SESSION['csrf_token'] ?? '');

if ($tokenPost === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $tokenPost)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Petición no autorizada.']);
    exit;
}

// ── Parámetros ────────────────────────────────────────────────────────
$descripcion  = trim((string)($body['descripcion']  ?? ''));
$receta       = trim((string)($body['receta']       ?? ''));
$numeroPaso   = (int)($body['numero']               ?? 1);
$ingredientes = is_array($body['ingredientes'] ?? null) ? $body['ingredientes'] : [];

if ($descripcion === '') {
    echo json_encode(['ok' => false, 'error' => 'La descripción del paso no puede estar vacía.']);
    exit;
}

// ── Construir prompt ──────────────────────────────────────────────────
$prompt = buildPromptV2($descripcion, $receta, $numeroPaso, $ingredientes);

// ── Llamar a Pollinations.AI ──────────────────────────────────────────
$imageData = generarConPollinations($prompt);

if ($imageData === null) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo generar la imagen. El servicio puede estar saturado; intenta en unos segundos.',
    ]);
    exit;
}

// ── Guardar en uploads/pasos/ ─────────────────────────────────────────
$destDir = defined('UPLOAD_PASOS_PATH') ? UPLOAD_PASOS_PATH : (UPLOAD_PATH . 'pasos/');
$destUrl = defined('UPLOAD_PASOS_URL')  ? UPLOAD_PASOS_URL  : (UPLOAD_URL  . 'pasos/');

if (!is_dir($destDir)) {
    @mkdir($destDir, 0775, true);
}

$nombre   = 'paso-gen-' . substr(bin2hex(random_bytes(8)), 0, 16) . '.jpg';
$rutaDest = $destDir . $nombre;

if (file_put_contents($rutaDest, $imageData) === false) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen generada.']);
    exit;
}

if (@getimagesize($rutaDest) === false) {
    @unlink($rutaDest);
    echo json_encode(['ok' => false, 'error' => 'La imagen generada no es válida. Intenta de nuevo.']);
    exit;
}

echo json_encode([
    'ok'           => true,
    'archivo'      => $nombre,
    'url'          => rtrim($destUrl, '/') . '/' . $nombre,
    'prompt_debug' => $prompt,   // útil para depuración en consola
]);

// ════════════════════════════════════════════════════════════════════
// LÓGICA DE PROMPT
// ════════════════════════════════════════════════════════════════════

/**
 * Construye un prompt concreto y relevante para el paso dado.
 *
 * Estrategia:
 *  1. Traduce los verbos de acción más comunes de cocina ES→EN.
 *  2. Extrae los ingredientes mencionados en el texto del paso.
 *  3. Describe la escena visualmente: qué se ve, en qué utensilio,
 *     qué ingredientes son visibles.
 *  4. No pide "ilustración" — pide una fotografía culinaria real,
 *     que es lo que Flux produce mejor.
 */
function buildPromptV2(
    string $descripcion,
    string $receta,
    int    $numero,
    array  $ingredientesReceta
): string {
    // ── 1. Traducir verbo de acción principal ─────────────────────────
    $accion = extraerAccionES($descripcion);

    // ── 2. Ingredientes presentes en este paso ────────────────────────
    // Primero los que el chef indicó explícitamente para el paso;
    // si no hay, buscar coincidencias con la lista de la receta.
    $ingsDelPaso = extraerIngredientesMencionados($descripcion, $ingredientesReceta);
    $ingsStr     = implode(', ', array_slice($ingsDelPaso, 0, 5));

    // ── 3. Utensilio probable ─────────────────────────────────────────
    $utensilio = extraerUtensilio($descripcion);

    // ── 4. Nombre de la receta en contexto ───────────────────────────
    $recetaCtx = $receta !== '' ? "for {$receta}" : '';

    // ── 5. Armar prompt final ─────────────────────────────────────────
    // Formato: "Professional food photography of [accion] [ingredientes]
    //           [utensilio] [receta_contexto], close-up, ..."
    $parts = [];

    $parts[] = 'Professional food photography';

    if ($accion !== '') {
        $parts[] = "showing {$accion}";
    }

    if ($ingsStr !== '') {
        $parts[] = "with {$ingsStr}";
    }

    if ($utensilio !== '') {
        $parts[] = "in a {$utensilio}";
    }

    if ($recetaCtx !== '') {
        $parts[] = $recetaCtx;
    }

    $prompt = implode(', ', $parts);

    // Estilo fotográfico culinario concreto — sin palabras abstractas
    $prompt .= ', close-up shot, natural daylight, rustic kitchen counter, '
             . 'sharp focus, appetizing, 4K food photography, '
             . 'no text, no watermark, no people, no hands';

    return $prompt;
}

/**
 * Extrae el verbo de acción culinaria principal del paso (ES→EN).
 */
function extraerAccionES(string $texto): string
{
    $lower = mb_strtolower($texto, 'UTF-8');

    // Mapa de patrones ES → descripción visual EN
    $mapa = [
        // Cortar
        '/\b(corta|cortar|pica|picar|trocea|trocear|rebana|rebanar)\b/' =>
            'cutting and chopping ingredients on a wooden cutting board',
        // Sofreír / saltear
        '/\b(sofr[íi]|sofr[íi]e|sofre[íi]r|saltea|saltear|dora|dorar)\b/' =>
            'sautéing ingredients in a pan with golden color',
        // Hervir / cocer
        '/\b(hierve|hervir|coc[ei]na|cocinar|cuece|cocer|hierv)\b/' =>
            'boiling and simmering in a pot with steam',
        // Mezclar / integrar
        '/\b(mezcla|mezclar|integra|integrar|combina|combinar|revuelve|revolver)\b/' =>
            'mixing and combining ingredients in a bowl',
        // Amasar / estirar
        '/\b(amasa|amasar|estira|estirar|lamina|laminar)\b/' =>
            'kneading and shaping dough on a floured surface',
        // Freír
        '/\b(fr[íi]|fre[íi]r|fr[ií]e)\b/' =>
            'deep frying in hot oil with golden crust',
        // Hornear
        '/\b(hornea|hornear|pon.*horno|meter.*horno)\b/' =>
            'baking in the oven with golden top',
        // Marinar
        '/\b(marina|marinar|macera|macerar|aderez|aderezar)\b/' =>
            'marinating ingredients in a bowl with spices',
        // Batir
        '/\b(bate|batir|baten|montado|montar)\b/' =>
            'whisking and beating in a bowl',
        // Rallar / moler
        '/\b(ralla|rallar|muele|moler|tritura|triturar|licua|licuar)\b/' =>
            'grating and grinding ingredients',
        // Escurrir / colar
        '/\b(escurre|escurrir|cuela|colar|enjuaga|enjuagar)\b/' =>
            'draining and straining in a colander',
        // Servir / emplatar
        '/\b(sirve|servir|emplata|emplatar|dispone|disponer)\b/' =>
            'plating and serving the finished dish',
        // Añadir / agregar / verter
        '/\b(a[ñn]ade|a[ñn]adir|agrega|agregar|vierte|verter|incorpora|incorporar)\b/' =>
            'adding and pouring ingredients',
        // Calentar
        '/\b(calienta|calentar|precalienta|precalentar)\b/' =>
            'heating up in a pan or pot',
        // Dejar reposar
        '/\b(reposa|reposar|deja reposar|reserva|reservar)\b/' =>
            'resting and cooling down on a counter',
    ];

    foreach ($mapa as $pattern => $enDesc) {
        if (preg_match($pattern, $lower)) {
            return $enDesc;
        }
    }

    // Fallback: usar las primeras palabras como contexto
    $palabras = array_slice(explode(' ', $texto), 0, 6);
    return 'preparing ' . implode(' ', $palabras);
}

/**
 * Extrae ingredientes del texto del paso que coincidan con la lista
 * de la receta, o detecta ingredientes comunes directamente.
 */
function extraerIngredientesMencionados(string $texto, array $ingredientesReceta): array
{
    $lower     = mb_strtolower($texto, 'UTF-8');
    $resultado = [];

    // Buscar coincidencias con los ingredientes reales de la receta
    foreach ($ingredientesReceta as $ing) {
        $ingLower = mb_strtolower(trim((string)$ing), 'UTF-8');
        // Tomar la primera palabra del ingrediente para comparar
        $primeraPalabra = strtok($ingLower, ' ,');
        if ($primeraPalabra && mb_strlen($primeraPalabra) > 3
            && mb_strpos($lower, $primeraPalabra) !== false) {
            $resultado[] = $ing;
        }
    }

    // Si no hay coincidencias, buscar ingredientes comunes en el texto
    if (empty($resultado)) {
        $comunesMapa = [
            'aceite'    => 'olive oil',     'cebolla'   => 'onion',
            'ajo'       => 'garlic',        'tomate'    => 'tomato',
            'sal'       => 'salt',          'pimienta'  => 'black pepper',
            'harina'    => 'flour',         'huevo'     => 'egg',
            'leche'     => 'milk',          'mantequilla' => 'butter',
            'pollo'     => 'chicken',       'carne'     => 'meat',
            'arroz'     => 'rice',          'pasta'     => 'pasta',
            'agua'      => 'water',         'caldo'     => 'broth',
            'azúcar'    => 'sugar',         'limón'     => 'lemon',
            'queso'     => 'cheese',        'crema'     => 'cream',
            'verduras'  => 'vegetables',    'zanahoria' => 'carrot',
            'pimiento'  => 'bell pepper',   'espinaca'  => 'spinach',
            'champiñón' => 'mushroom',      'filete'    => 'fillet',
            'cebollín'  => 'spring onion',  'cilantro'  => 'cilantro',
            'perejil'   => 'parsley',       'jengibre'  => 'ginger',
            'soja'      => 'soy sauce',     'vinagre'   => 'vinegar',
        ];

        foreach ($comunesMapa as $es => $en) {
            if (mb_strpos($lower, $es) !== false) {
                $resultado[] = $en;
            }
        }
    }

    return array_unique($resultado);
}

/**
 * Detecta el utensilio más probable a partir del texto del paso.
 */
function extraerUtensilio(string $texto): string
{
    $lower = mb_strtolower($texto, 'UTF-8');

    $utensilios = [
        'sartén|sarten'         => 'frying pan',
        'olla'                  => 'cooking pot',
        'wok'                   => 'wok',
        'paella'                => 'paella pan',
        'tazón|tazon|bol\b'     => 'mixing bowl',
        'molde'                 => 'baking mold',
        'horno'                 => 'oven',
        'tabla'                 => 'cutting board',
        'colador'               => 'colander',
        'bandeja'               => 'baking tray',
        'plancha'               => 'griddle',
        'cazuela'               => 'casserole',
        'fuente'                => 'baking dish',
        'mortero'               => 'mortar and pestle',
    ];

    foreach ($utensilios as $pattern => $en) {
        if (preg_match("/\\b({$pattern})\\b/u", $lower)) {
            return $en;
        }
    }

    return '';
}

// ════════════════════════════════════════════════════════════════════
// LLAMADA A POLLINATIONS.AI
// ════════════════════════════════════════════════════════════════════

/**
 * Llama a Pollinations.AI con el modelo flux-realism.
 * flux-realism produce escenas fotográficas predecibles,
 * mejor para cocina que flux genérico.
 */
function generarConPollinations(string $prompt): ?string
{
    $url = 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt)
         . '?' . http_build_query([
             'width'   => 800,
             'height'  => 600,
             'model'   => 'flux-realism',   // más realista y predecible
             'nologo'  => 'true',
             'nofeed'  => 'true',
             'enhance' => 'false',           // desactivar mejoras automáticas
             'seed'    => random_int(1, 999999),
         ]);

    $ctx = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'header'          => "User-Agent: GastroDigital/3.0\r\nAccept: image/*\r\n",
            'timeout'         => 60,
            'follow_location' => 1,
            'max_redirects'   => 5,
        ],
    ]);

    $data = @file_get_contents($url, false, $ctx);

    if ($data === false || strlen($data) < 5000) {
        return null;
    }

    return $data;
}
