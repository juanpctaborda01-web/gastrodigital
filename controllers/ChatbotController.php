<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/chatbot.php';
require_once __DIR__ . '/../config/categorias.php';
require_once __DIR__ . '/../models/receta.php';

/**
 * ChatbotController — "Chef Bot", asistente gastronómico de GastroDigital.
 *
 * Estrategia: BASE DE DATOS PRIMERO, IA DESPUÉS.
 *
 * Flujo:
 *  1. Recibir mensaje del usuario.
 *  2. Identificar intención (buscar receta, ingredientes, pasos, etc.).
 *  3. Buscar en MySQL con score de relevancia — sin inventar ni forzar coincidencias.
 *  4. Si hay datos exactos (ingredientes/pasos), construirlos directamente desde MySQL.
 *  5. Ollama solo redacta la respuesta natural — NO puede modificar los datos.
 *
 * Motor de IA: Ollama local — http://localhost:11434/api/generate
 * Modelo:      llama3.2:3b  (ollama pull llama3.2:3b)
 */
class ChatbotController
{
    private Receta $recetaModel;
    private $pdo;

    // ── Tipos de intención ────────────────────────────────────────────────
    const INT_INGREDIENTES      = 'INGREDIENTES_RECETA';
    const INT_PASOS             = 'PASOS_RECETA';
    const INT_INFO_RECETA       = 'INFO_RECETA';
    const INT_BUSCAR_RECETA     = 'BUSCAR_RECETA';
    const INT_POR_INGREDIENTES  = 'BUSCAR_POR_INGREDIENTES';
    const INT_RECOMENDAR        = 'RECOMENDAR_RECETA';
    const INT_GENERAL           = 'INFORMACION_GENERAL';

    public function __construct()
    {
        $this->recetaModel = new Receta();
        require_once __DIR__ . '/../config/conexion.php';
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/chatbot/token — entrega un token CSRF para el widget.
    // ─────────────────────────────────────────────────────────────────────────
    public function token(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['chatbot_csrf'])) {
            $_SESSION['chatbot_csrf'] = bin2hex(random_bytes(32));
        }
        echo json_encode(['csrf' => $_SESSION['chatbot_csrf']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/chatbot — recibe el mensaje y devuelve la respuesta.
    // ─────────────────────────────────────────────────────────────────────────
    public function responder(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido.']);
            return;
        }

        if (!$this->permitirEnvio()) {
            http_response_code(429);
            echo json_encode(['error' => 'Has enviado demasiados mensajes seguidos. Espera un momento.']);
            return;
        }

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $mensaje = trim((string)($body['mensaje'] ?? $_POST['mensaje'] ?? ''));
        $token   = (string)($body['csrf']    ?? $_POST['csrf']    ?? '');

        if (empty($_SESSION['chatbot_csrf']) || !hash_equals($_SESSION['chatbot_csrf'], $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sesión de chat inválida. Recarga la página e intenta de nuevo.']);
            return;
        }
        if ($mensaje === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Escribe un mensaje.']);
            return;
        }
        if (mb_strlen($mensaje) > 500) {
            http_response_code(400);
            echo json_encode(['error' => 'Tu mensaje es muy largo (máximo 500 caracteres).']);
            return;
        }

        $historial = $_SESSION['chatbot_historial'] ?? [];

        // ── 1. Identificar intención ─────────────────────────────────────────
        $intencion = $this->identificarIntencion($mensaje);

        // ── 2. Buscar receta relevante en la BD ──────────────────────────────
        $recetaEncontrada = null;
        $respuesta        = null;

        switch ($intencion) {

            // ── Pide ingredientes exactos ────────────────────────────────────
            case self::INT_INGREDIENTES:
                $recetaEncontrada = $this->buscarRecetaExacta($mensaje);
                if ($recetaEncontrada) {
                    // Construir lista de ingredientes directamente desde MySQL
                    $bloqueIngredientes = $this->formatearIngredientes($recetaEncontrada);
                    $promptOllama = $this->promptDatosFijos(
                        "El usuario pregunta por los ingredientes de \"{$recetaEncontrada['titulo']}\".",
                        $bloqueIngredientes,
                        "Presenta estos ingredientes de forma amable. " .
                        "No añadas ni quites ningún ingrediente. " .
                        "No cambies cantidades ni unidades."
                    );
                    $introduccion = $this->llamarOllama($promptOllama, $historial);
                    // Si Ollama falla, respondemos con datos puros
                    $respuesta = $introduccion
                        ? $introduccion . "\n\n" . $bloqueIngredientes
                        : "Aquí están los ingredientes de \"{$recetaEncontrada['titulo']}\":\n\n" . $bloqueIngredientes;
                } else {
                    $respuesta = $this->respuestaNoEncontrada($mensaje);
                }
                break;

            // ── Pide los pasos exactos ───────────────────────────────────────
            case self::INT_PASOS:
                $recetaEncontrada = $this->buscarRecetaExacta($mensaje);
                if ($recetaEncontrada) {
                    $bloquePasos = $this->formatearPasos($recetaEncontrada);
                    $promptOllama = $this->promptDatosFijos(
                        "El usuario quiere preparar \"{$recetaEncontrada['titulo']}\".",
                        $bloquePasos,
                        "Presenta estos pasos de forma amable. " .
                        "No modifiques, agregues ni elimines ningún paso. " .
                        "No cambies el orden."
                    );
                    $introduccion = $this->llamarOllama($promptOllama, $historial);
                    $respuesta = $introduccion
                        ? $introduccion . "\n\n" . $bloquePasos
                        : "Pasos para preparar \"{$recetaEncontrada['titulo']}\":\n\n" . $bloquePasos;
                } else {
                    $respuesta = $this->respuestaNoEncontrada($mensaje);
                }
                break;

            // ── Pide información general de una receta específica ────────────
            case self::INT_INFO_RECETA:
            case self::INT_BUSCAR_RECETA:
                $recetaEncontrada = $this->buscarRecetaExacta($mensaje);
                if ($recetaEncontrada) {
                    $bloqueCompleto = $this->formatearRecetaCompleta($recetaEncontrada);
                    $promptOllama = $this->promptDatosFijos(
                        "El usuario pregunta sobre \"{$recetaEncontrada['titulo']}\".",
                        $bloqueCompleto,
                        "Resume la información de esta receta de forma amable y natural. " .
                        "No modifiques ningún dato. No inventes información adicional."
                    );
                    $respuesta = $this->llamarOllama($promptOllama, $historial)
                        ?? "Aquí tienes la información de \"{$recetaEncontrada['titulo']}\":\n\n" . $bloqueCompleto;
                } else {
                    $respuesta = $this->respuestaNoEncontrada($mensaje);
                }
                break;

            // ── Busca recetas que tengan ciertos ingredientes ────────────────
            case self::INT_POR_INGREDIENTES:
                $ingredientesBuscados = $this->extraerIngredientesDeMensaje($mensaje);
                $recetasConIngredientes = $this->buscarPorIngredientes($ingredientesBuscados);
                if (!empty($recetasConIngredientes)) {
                    $bloqueResultados = $this->formatearListaRecetas($recetasConIngredientes);
                    $promptOllama = $this->promptDatosFijos(
                        "El usuario tiene estos ingredientes: " . implode(', ', $ingredientesBuscados) . ".",
                        $bloqueResultados,
                        "Recomienda estas recetas de forma amable. " .
                        "Solo menciona las recetas del listado. No inventes recetas adicionales."
                    );
                    $respuesta = $this->llamarOllama($promptOllama, $historial)
                        ?? "Con esos ingredientes encontré estas recetas en GastroDigital:\n\n" . $bloqueResultados;
                } else {
                    $respuesta = "No encontré recetas en GastroDigital que usen esos ingredientes específicos. "
                               . "Puedes explorar nuestro catálogo en " . BASE_URL . "/recetas";
                }
                break;

            // ── Pide recomendaciones por categoría/dificultad/tiempo ─────────
            case self::INT_RECOMENDAR:
                $recetasRecomendadas = $this->buscarParaRecomendacion($mensaje);
                if (!empty($recetasRecomendadas)) {
                    $bloqueRec = $this->formatearListaRecetas($recetasRecomendadas);
                    $promptOllama = $this->promptDatosFijos(
                        "El usuario pide recomendaciones.",
                        $bloqueRec,
                        "Recomienda estas recetas de forma amable y entusiasta. " .
                        "Solo menciona las del listado. No inventes otras."
                    );
                    $respuesta = $this->llamarOllama($promptOllama, $historial)
                        ?? "Aquí van mis recomendaciones de GastroDigital:\n\n" . $bloqueRec;
                } else {
                    $respuesta = "No encontré recetas que coincidan con tu búsqueda en GastroDigital. "
                               . "Prueba explorar el catálogo en " . BASE_URL . "/recetas";
                }
                break;

            // ── Pregunta general de cocina (técnica, sustituto, etc.) ────────
            default: // INT_GENERAL
                $promptGeneral = <<<PROMPT
Eres "Chef Bot", el asistente gastronómico de GastroDigital.
Responde en español de forma amable y concisa (máximo 4 líneas).
Solo responde preguntas generales de cocina (técnicas, consejos, conversión de medidas).
NO inventes recetas de GastroDigital ni afirmes que existen recetas que no has consultado.
Si la pregunta requiere buscar una receta específica, indica que el usuario puede explorar el catálogo en: {BASE_URL}/recetas

Usuario: {$mensaje}
Asistente:
PROMPT;
                // Para preguntas generales no pasamos historial de recetas
                $respuesta = $this->llamarOllama($promptGeneral, $historial)
                    ?? 'Lo siento, no pude procesar tu pregunta. Por favor intenta de nuevo.';
                break;
        }

        // ── 3. Guardar historial y devolver ──────────────────────────────────
        $historial[] = ['role' => 'user',      'content' => $mensaje];
        $historial[] = ['role' => 'assistant', 'content' => $respuesta];
        $_SESSION['chatbot_historial'] = array_slice($historial, -12);

        echo json_encode(['respuesta' => $respuesta]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Límite: máx. 12 mensajes por minuto por sesión.
    // ─────────────────────────────────────────────────────────────────────────
    private function permitirEnvio(): bool
    {
        $ahora  = time();
        $ventana = 60;
        $limite  = 12;

        $_SESSION['chatbot_rate'] = array_filter(
            $_SESSION['chatbot_rate'] ?? [],
            fn($t) => $t > $ahora - $ventana
        );

        if (count($_SESSION['chatbot_rate']) >= $limite) {
            return false;
        }
        $_SESSION['chatbot_rate'][] = $ahora;
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  IDENTIFICAR INTENCIÓN
    // ─────────────────────────────────────────────────────────────────────────
    private function identificarIntencion(string $mensaje): string
    {
        $t = mb_strtolower($mensaje);

        // Ingredientes
        foreach (['ingrediente','ingredientes','necesito','lleva','llevan',
                  'contiene','contienen','usa','usan'] as $s) {
            if (mb_strpos($t, $s) !== false) return self::INT_INGREDIENTES;
        }
        // Pasos / preparación
        foreach (['cómo preparo','como preparo','cómo se prepara','como se prepara',
                  'cómo se hace','como se hace','pasos','paso a paso','preparar',
                  'preparación','preparacion','instrucciones','receta de'] as $s) {
            if (mb_strpos($t, $s) !== false) return self::INT_PASOS;
        }
        // Tiene ingredientes disponibles
        foreach (['tengo','usando','utilizo','disponibles','a base de','hecha con'] as $s) {
            if (mb_strpos($t, $s) !== false) return self::INT_POR_INGREDIENTES;
        }
        // Quiere recomendación
        foreach (['recomienda','recomiéndame','sugiéreme','sugerencia','opciones',
                  'cuál me','qué hay de','hay alguna','muéstrame','populares','destacadas'] as $s) {
            if (mb_strpos($t, $s) !== false) return self::INT_RECOMENDAR;
        }
        // Información de receta concreta
        foreach (['qué es','que es','tiempo','duración','duracion','cuánto tarda',
                  'cuanto tarda','dificultad','porciones','calorías','calorias'] as $s) {
            if (mb_strpos($t, $s) !== false) return self::INT_INFO_RECETA;
        }
        // Palabras suficientes de comida → buscar receta
        $stopwords = ['que','para','como','una','del','las','los','hay','quiero',
                      'dame','me','de','en','es','si','no','un','la','le','el','con'];
        $kw = array_diff(
            preg_split('/[^\p{L}0-9]+/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            $stopwords
        );
        if (count($kw) >= 2) return self::INT_BUSCAR_RECETA;

        return self::INT_GENERAL;
    }
    private function buscarRecetaExacta(string $mensaje): ?array
    {
        $texto     = mb_strtolower($mensaje);
        $stopwords = ['que','para','como','puedo','tengo','hacer','receta','recetas',
                      'con','una','unas','del','las','los','hay','quiero','dame',
                      'dime','preparar','cocinar','ingredientes','pasos','paso',
                      'cómo','como','me','de','en','es','no','un','la','el','se'];
        $palabras  = preg_split('/[^\p{L}0-9]+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $clave     = array_values(array_filter(
            array_unique($palabras),
            fn($p) => !in_array($p, $stopwords, true) && mb_strlen($p) >= 3
        ));

        if (empty($clave)) return null;

        // Construir score dinámico por cada palabra clave
        $scoreParts = [];
        $paramsWhere = [];
        foreach ($clave as $p) {
            $like = '%' . $p . '%';
            $scoreParts[] = "(CASE WHEN LOWER(r.titulo) LIKE ? THEN 10 ELSE 0 END)";
            $paramsWhere[] = $like;
            $scoreParts[] = "(CASE WHEN EXISTS(SELECT 1 FROM ingredientes i2 WHERE i2.receta_id=r.id AND LOWER(i2.nombre) LIKE ?) THEN 5 ELSE 0 END)";
            $paramsWhere[] = $like;
            $scoreParts[] = "(CASE WHEN LOWER(r.descripcion) LIKE ? THEN 2 ELSE 0 END)";
            $paramsWhere[] = $like;
            $scoreParts[] = "(CASE WHEN LOWER(r.categoria) LIKE ? THEN 3 ELSE 0 END)";
            $paramsWhere[] = $like;
        }
        $scoreExpr   = implode(' + ', $scoreParts);
        $allParams   = array_merge($paramsWhere, $paramsWhere);

        $stmt = $this->pdo->prepare("
            SELECT r.id, r.titulo, r.descripcion, r.tiempo_preparacion,
                   r.porciones, r.dificultad, r.categoria, r.calorias,
                   u.nombre AS chef_nombre,
                   ({$scoreExpr}) AS score
            FROM recetas r
            LEFT JOIN usuarios u ON u.id = r.chef_id
            WHERE r.publicada = 1 AND ({$scoreExpr}) > 0
            ORDER BY score DESC
            LIMIT 1
        ");
        $stmt->execute($allParams);
        $fila = $stmt->fetch();

        // Umbral mínimo: score >= 10 (equivale a 1 coincidencia en el título)
        if (!$fila || (int)$fila['score'] < 10) return null;

        // Cargar ingredientes
        $s2 = $this->pdo->prepare(
            "SELECT nombre, cantidad, unidad FROM ingredientes WHERE receta_id = ? ORDER BY orden, id"
        );
        $s2->execute([$fila['id']]);
        $fila['ingredientes'] = $s2->fetchAll();

        // Cargar pasos
        $s3 = $this->pdo->prepare(
            "SELECT numero, descripcion FROM pasos WHERE receta_id = ? ORDER BY numero"
        );
        $s3->execute([$fila['id']]);
        $fila['pasos'] = $s3->fetchAll();

        return $fila;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EXTRAER INGREDIENTES DEL MENSAJE
    // ─────────────────────────────────────────────────────────────────────────
    private function extraerIngredientesDeMensaje(string $mensaje): array
    {
        $texto     = mb_strtolower($mensaje);
        $stopwords = ['tengo','que','para','como','puedo','hacer','con','una','del',
                      'las','los','hay','quiero','preparar','cocinar','y','o',
                      'me','de','en','es','no','un','la','le','el','se','al'];
        $palabras  = preg_split('/[^\p{L}0-9]+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter(
            array_unique($palabras),
            fn($p) => !in_array($p, $stopwords, true) && mb_strlen($p) >= 3
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BUSCAR POR INGREDIENTES — recetas que contengan los ingredientes dados
    // ─────────────────────────────────────────────────────────────────────────
    private function buscarPorIngredientes(array $ingredientes): array
    {
        if (empty($ingredientes)) return [];
        $likes    = array_map(fn($i) => '%' . $i . '%', $ingredientes);
        $orConds  = implode(' OR ', array_fill(0, count($ingredientes), 'LOWER(i.nombre) LIKE ?'));
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.titulo, r.categoria, r.tiempo_preparacion,
                   r.dificultad, COUNT(DISTINCT i.id) AS coincidencias
            FROM recetas r
            JOIN ingredientes i ON i.receta_id = r.id
            WHERE r.publicada = 1 AND ({$orConds})
            GROUP BY r.id
            HAVING coincidencias >= 2
            ORDER BY coincidencias DESC
            LIMIT 5
        ");
        $stmt->execute($likes);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BUSCAR PARA RECOMENDACIÓN — categoría, dificultad o tiempo
    //  Sin respaldo: devuelve [] si no hay filtro concreto
    // ─────────────────────────────────────────────────────────────────────────
    private function buscarParaRecomendacion(string $mensaje): array
    {
        $t       = mb_strtolower($mensaje);
        $where   = ['r.publicada = 1'];
        $params  = [];
        $filtro  = false;

        foreach (CATEGORIAS as $cat) {
            if (mb_strpos($t, mb_strtolower($cat['nombre'])) !== false) {
                $where[]   = 'r.categoria LIKE ?';
                $params[]  = '%' . $cat['nombre'] . '%';
                $filtro    = true;
                break;
            }
        }
        $difs = ['fácil'=>'Fácil','facil'=>'Fácil','media'=>'Media','difícil'=>'Difícil','dificil'=>'Difícil'];
        foreach ($difs as $key => $val) {
            if (!$filtro && mb_strpos($t, $key) !== false) {
                $where[]  = 'r.dificultad = ?';
                $params[] = $val;
                $filtro   = true;
                break;
            }
        }
        if (preg_match('/(\d{1,3})\s*(min|minutos)/u', $t, $m)) {
            $where[]  = 'r.tiempo_preparacion <= ?';
            $params[] = (int)$m[1];
            $filtro   = true;
        }

        if (!$filtro) {
            // Solo para "populares" / "destacadas"
            if (mb_strpos($t,'popular')!==false || mb_strpos($t,'destaca')!==false
             || mb_strpos($t,'vista')!==false) {
                return $this->recetaModel->masVistas(4);
            }
            return [];
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.titulo, r.categoria, r.tiempo_preparacion, r.dificultad
            FROM recetas r {$whereSQL}
            ORDER BY r.visitas DESC LIMIT 5
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FORMATEADORES — construyen texto directamente desde datos de MySQL
    // ─────────────────────────────────────────────────────────────────────────
    private function formatearIngredientes(array $receta): string
    {
        if (empty($receta['ingredientes'])) {
            return "Esta receta no tiene ingredientes registrados en la base de datos.";
        }
        $lineas = ["**Ingredientes de \"{$receta['titulo']}\":**"];
        foreach ($receta['ingredientes'] as $ing) {
            $cant    = trim(($ing['cantidad'] ?? '') . ' ' . ($ing['unidad'] ?? ''));
            $lineas[] = '• ' . htmlspecialchars($ing['nombre'])
                       . ($cant ? ' — ' . htmlspecialchars($cant) : '');
        }
        $lineas[] = "\nVer receta: " . BASE_URL . "/recetas?id=" . $receta['id'];
        return implode("\n", $lineas);
    }

    private function formatearPasos(array $receta): string
    {
        if (empty($receta['pasos'])) {
            return "Esta receta no tiene pasos registrados en la base de datos.";
        }
        $lineas = ["**Preparación de \"{$receta['titulo']}\" "
                  . "({$receta['tiempo_preparacion']} min · {$receta['dificultad']}):**"];
        foreach ($receta['pasos'] as $paso) {
            $lineas[] = $paso['numero'] . '. ' . htmlspecialchars($paso['descripcion']);
        }
        $lineas[] = "\nVer receta: " . BASE_URL . "/recetas?id=" . $receta['id'];
        return implode("\n", $lineas);
    }

    private function formatearRecetaCompleta(array $receta): string
    {
        $lineas = [
            "**{$receta['titulo']}**",
            "Categoría: " . htmlspecialchars($receta['categoria'] ?? 'N/D'),
            "Tiempo: {$receta['tiempo_preparacion']} min | Dificultad: {$receta['dificultad']}"
              . " | Porciones: " . ($receta['porciones'] ?? 'N/D'),
        ];
        if (!empty($receta['calorias'])) {
            $lineas[] = "Calorías: {$receta['calorias']} kcal por porción";
        }
        if (!empty($receta['descripcion'])) {
            $lineas[] = "\n" . htmlspecialchars($receta['descripcion']);
        }
        $lineas[] = "\nVer receta: " . BASE_URL . "/recetas?id=" . $receta['id'];
        return implode("\n", $lineas);
    }

    private function formatearListaRecetas(array $recetas): string
    {
        $lineas = [];
        foreach ($recetas as $r) {
            $lineas[] = sprintf(
                '• **%s** | %s | %s min | %s | Ver: %s/recetas?id=%d',
                htmlspecialchars($r['titulo']),
                htmlspecialchars($r['categoria'] ?? 'Sin categoría'),
                $r['tiempo_preparacion'] ?? '?',
                htmlspecialchars($r['dificultad'] ?? ''),
                BASE_URL, $r['id']
            );
        }
        return implode("\n", $lineas);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PROMPT DATOS FIJOS — instruye a Ollama a NO modificar los datos de la BD
    // ─────────────────────────────────────────────────────────────────────────
    private function promptDatosFijos(string $contextoUsuario, string $datosBD, string $extra): string
    {
        return "Eres \"Chef Bot\", el asistente gastronómico de GastroDigital. Responde en español.\n\n"
             . "REGLAS OBLIGATORIAS:\n"
             . "1. No inventes ingredientes.\n"
             . "2. No elimines ingredientes.\n"
             . "3. No cambies cantidades ni unidades.\n"
             . "4. No cambies pasos ni su orden.\n"
             . "5. No cambies el nombre de la receta.\n"
             . "6. No mezcles información de otras recetas.\n"
             . "7. " . $extra . "\n"
             . "8. Sé amable y conciso. Máximo 2 líneas de introducción.\n\n"
             . "CONTEXTO: " . $contextoUsuario . "\n\n"
             . "DATOS_BD:\n" . $datosBD . "\nFIN_DATOS_BD\n\nAsistente:";
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  RESPUESTA NO ENCONTRADA
    // ─────────────────────────────────────────────────────────────────────────
    private function respuestaNoEncontrada(string $mensaje): string
    {
        $terminos = $this->extraerIngredientesDeMensaje($mensaje);
        $buscado  = !empty($terminos)
            ? '"' . implode(', ', array_slice($terminos, 0, 3)) . '"'
            : 'lo que buscas';
        return "No encontré una receta de {$buscado} en GastroDigital. "
             . "Nuestro catálogo puede no tener esa receta todavía.\n"
             . "Puedes explorar todas las recetas aquí: " . BASE_URL . "/recetas";
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LLAMAR A OLLAMA — firma nueva: recibe el prompt ya construido
    // ─────────────────────────────────────────────────────────────────────────
    private function llamarOllama(string $prompt, array $historial = []): ?string
    {
        if (!function_exists('curl_init')) {
            error_log('ChatbotController: cURL no está disponible.');
            return null;
        }

        // Adjuntar los últimos 3 turnos de historial como contexto
        $conv = '';
        foreach (array_slice($historial, -6) as $turno) {
            $conv .= $turno['role'] === 'user'
                ? "\nUsuario: " . ($turno['content'] ?? '')
                : "\nAsistente: " . ($turno['content'] ?? '');
        }
        $promptFinal = $conv ? $prompt . "\n[Historial previo:]{$conv}\n\nAsistente:" : $prompt;

        $payload = json_encode([
            'model'   => OLLAMA_MODEL,
            'prompt'  => $promptFinal,
            'stream'  => false,
            'options' => [
                'temperature'    => 0.3,   // bajo: respuestas fieles a los datos
                'num_predict'    => 350,
                'top_p'          => 0.85,
                'repeat_penalty' => 1.2,
            ],
        ]);

        $ch = curl_init(OLLAMA_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => OLLAMA_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            error_log("Chef Bot — cURL error: {$err}");
            return null;
        }
        if ($code !== 200) {
            error_log("Chef Bot — HTTP {$code}: {$raw}");
            return null;
        }

        $texto = trim(json_decode($raw, true)['response'] ?? '');
        return $texto !== '' ? $texto : null;
    }
}

// ── Punto de entrada cuando el archivo se llama directamente ─────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');
    $controller = new ChatbotController();
    $accion = $_GET['accion'] ?? 'responder';
    match ($accion) {
        'token'  => $controller->token(),
        default  => $controller->responder(),
    };
}
