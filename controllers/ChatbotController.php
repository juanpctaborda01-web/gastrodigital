<?php
/**
 * ChatbotController.php
 * Endpoint AJAX que recibe un mensaje y devuelve una respuesta JSON.
 * No requiere APIs externas — trabaja con la BD local.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../models/ingrediente.php';

class ChatbotController {
    public function respuesta(): void {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['respuesta' => 'Método no permitido.']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $tokenPost = $body['csrf_token'] ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (!$tokenPost || !$tokenSesion || !hash_equals($tokenSesion, $tokenPost)) {
            http_response_code(403);
            echo json_encode(['respuesta' => 'Petición no válida.']);
            return;
        }

        $mensaje = trim($body['mensaje'] ?? '');
        if ($mensaje === '') {
            echo json_encode(['respuesta' => 'Por favor escribe algo para ayudarte.']);
            return;
        }

        $bot = new ChatbotRecetas();
        echo json_encode(['respuesta' => $bot->responder($mensaje)]);
    }
}

// Se conserva la ejecución directa para instalaciones que aún usan esta URL.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'ChatbotController.php') {
    (new ChatbotController())->respuesta();
    exit();
}


// ─────────────────────────────────────────────────────────────────────────────

class ChatbotRecetas {

    private Receta      $recetaModel;
    private Ingrediente $ingredienteModel;

    // Palabras clave → intención
    private array $intenciones = [
        'buscar'      => ['busca', 'buscar', 'encuentra', 'quiero', 'dame', 'muéstrame', 'mostrar', 'ver', 'receta de', 'receta para', 'cómo hago', 'cómo se hace', 'cómo preparo'],
        'ingrediente' => ['ingrediente', 'ingredientes', 'tengo', 'con', 'usando', 'utilizo', 'uso'],
        'tiempo'      => ['rápido', 'rápida', 'rapido', 'rapida', 'minutos', 'min', 'tiempo', 'poco tiempo', 'pronto', 'veloz'],
        'dificultad'  => ['fácil', 'facil', 'sencillo', 'sencilla', 'difícil', 'dificil', 'complicado', 'principiante', 'experto', 'avanzado'],
        'categoria'   => ['italiana', 'mexicana', 'japonesa', 'china', 'francesa', 'española', 'peruana', 'americana', 'postre', 'postres', 'ensalada', 'ensaladas', 'sopa', 'sopas', 'desayuno', 'almuerzo', 'cena'],
        'pais'        => ['colombia', 'colombiana', 'argentina', 'argentina', 'perú', 'peru', 'españa', 'espana', 'méxico', 'mexico', 'brasil', 'francia', 'turquía', 'turquia', 'india', 'vietnam', 'tailandia', 'italia', 'japón', 'japon', 'estados unidos'],
        'listar'      => ['todas', 'lista', 'listado', 'catálogo', 'catalogo', 'disponibles', 'hay'],
        'categorias'  => ['categorías', 'categorias', 'tipos', 'tipo de receta', 'qué tipos'],
        'saludo'      => ['hola', 'buenas', 'buenos días', 'buenas tardes', 'buenas noches', 'hey', 'hi', 'hello'],
        'ayuda'       => ['ayuda', 'ayudar', 'help', 'qué puedes', 'que puedes', 'qué haces', 'que haces', 'opciones', 'comandos'],
        'despedida'   => ['adiós', 'adios', 'bye', 'chao', 'hasta luego', 'nos vemos', 'gracias'],
    ];

    private array $temasGastronomicos = [
        'receta', 'recetas', 'cocina', 'cocinar', 'cocinero', 'chef', 'plato', 'comida',
        'aliment', 'ingrediente', 'ingredientes', 'salsa', 'sopas', 'sopa', 'postre',
        'desayuno', 'almuerzo', 'cena', 'horno', 'hornear', 'freír', 'freir', 'asar',
        'hervir', 'sartén', 'sarten', 'cuchillo', 'masa', 'arroz', 'pasta', 'pollo',
        'carne', 'pescado', 'marisco', 'verdura', 'fruta', 'pan', 'queso', 'huevo',
        'rápida', 'rapida', 'fácil', 'facil', 'dificil', 'difícil', 'minutos', 'minuto',
        'italian', 'mexican', 'japones', 'japonés', 'español', 'espanol', 'peruan',
        'argentin', 'franc', 'india', 'tailand', 'ingredientes'
    ];

    private array $temasNoGastronomicos = [
        'clima', 'tiempo meteorológico', 'fútbol', 'futbol', 'deporte', 'política',
        'politica', 'elecciones', 'religión', 'religion', 'programación', 'programacion',
        'código', 'codigo', 'matemática', 'matematica', 'historia', 'geografía',
        'geografia', 'música', 'musica', 'película', 'peliculas', 'videojuego',
        'noticias', 'acciones', 'criptomoneda', 'tarea escolar', 'traduce', 'viaje'
    ];

    private const MENSAJE_FUERA_DE_TEMA = 'Este chatbot no está diseñado para eso. Solo puedo ayudarte con información de gastronomía, recetas, ingredientes y cocina.';

    public function __construct() {
        $this->recetaModel      = new Receta();
        $this->ingredienteModel = new Ingrediente();
    }

    public function responder(string $mensaje): string {
        $lower = mb_strtolower($mensaje, 'UTF-8');

        if (!$this->esGastronomico($lower)) {
            return self::MENSAJE_FUERA_DE_TEMA;
        }

        // ── Saludo ──
        if ($this->coincide($lower, $this->intenciones['saludo'])) {
            return $this->saludo();
        }

        // ── Despedida ──
        if ($this->coincide($lower, $this->intenciones['despedida'])) {
            return "¡Hasta pronto! Espero haberte ayudado. 🍽️ Vuelve cuando quieras cocinar algo rico.";
        }

        // ── Ayuda ──
        if ($this->coincide($lower, $this->intenciones['ayuda'])) {
            return $this->ayuda();
        }

        // ── Categorías disponibles ──
        if ($this->coincide($lower, $this->intenciones['categorias'])) {
            return $this->listarCategorias();
        }

        // ── Platos típicos por país ──
        foreach ($this->intenciones['pais'] as $pais) {
            if (str_contains($lower, $pais)) {
                return $this->porPais($pais);
            }
        }

        // ── Listar todas ──
        if ($this->coincide($lower, $this->intenciones['listar']) && !$this->coincide($lower, $this->intenciones['ingrediente'])) {
            return $this->listarTodas();
        }

        // ── Por tiempo ──
        if ($this->coincide($lower, $this->intenciones['tiempo'])) {
            return $this->porTiempo($lower);
        }

        // ── Por dificultad ──
        if ($this->coincide($lower, $this->intenciones['dificultad'])) {
            return $this->porDificultad($lower);
        }

        // ── Por categoría ──
        foreach ($this->intenciones['categoria'] as $cat) {
            if (str_contains($lower, $cat)) {
                return $this->porCategoria($cat);
            }
        }

        // ── Por ingrediente ──
        if ($this->coincide($lower, $this->intenciones['ingrediente'])) {
            return $this->porIngrediente($lower);
        }

        // ── Búsqueda general (fallback) ──
        return $this->buscarGeneral($mensaje);
    }

    // ── Respuestas ────────────────────────────────────────────────────────────

    private function saludo(): string {
        $hora = (int) date('H');
        if ($hora < 12)     $momento = 'Buenos días';
        elseif ($hora < 18) $momento = 'Buenas tardes';
        else                $momento = 'Buenas noches';

        return "$momento! 👨‍🍳 Soy el asistente de GastroDigital.\n\n"
             . "Puedo ayudarte a:\n"
             . "• 🔍 Buscar recetas por nombre\n"
             . "• 🥕 Encontrar recetas con ingredientes que tienes\n"
             . "• ⏱️ Recetas rápidas (por tiempo)\n"
             . "• 📊 Filtrar por dificultad\n"
             . "• 🌍 Explorar por categoría\n\n"
             . "¿Qué te gustaría cocinar hoy?";
    }

    private function ayuda(): string {
        return "🤖 <strong>¿Qué puedo hacer?</strong>\n\n"
             . "Puedes preguntarme cosas como:\n\n"
             . "• <em>\"Busca recetas de pasta\"</em>\n"
             . "• <em>\"Tengo pollo y tomate, ¿qué hago?\"</em>\n"
             . "• <em>\"Recetas rápidas de menos de 30 minutos\"</em>\n"
             . "• <em>\"Recetas fáciles para principiantes\"</em>\n"
             . "• <em>\"Recetas italianas\"</em>\n"
             . "• <em>\"¿Qué categorías hay?\"</em>\n"
             . "• <em>\"Muéstrame todas las recetas\"</em>";
    }

    private function listarCategorias(): string {
        $cats = $this->recetaModel->categorias();
        if (empty($cats)) {
            return "Aún no hay categorías registradas en la base de datos. 📂";
        }
        $lista = implode(', ', array_map(fn($c) => "• $c", $cats));
        return "🏷️ <strong>Categorías disponibles:</strong>\n\n" . implode("\n", array_map(fn($c) => "• $c", $cats));
    }

    private function listarTodas(): string {
        $recetas = $this->recetaModel->listarTodas();
        if (empty($recetas)) {
            return "Aún no hay recetas en la base de datos. ¡Los chefs están trabajando en ello! 👨‍🍳";
        }
        $resp = "📚 <strong>Recetas disponibles:</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['categoria']} ({$r['tiempo_preparacion']} min)\n";
        }
        return $resp;
    }

    private function porTiempo(string $lower): string {
        // Extraer número de minutos si lo menciona
        preg_match('/(\d+)\s*min/', $lower, $matches);
        $minutos = isset($matches[1]) ? (int)$matches[1] : 30;

        $recetas = $this->recetaModel->porTiempo($minutos);
        if (empty($recetas)) {
            return "No encontré recetas de menos de $minutos minutos. 😕 Prueba con un tiempo mayor.";
        }
        $resp = "⏱️ <strong>Recetas en menos de $minutos minutos:</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['tiempo_preparacion']} min · {$r['dificultad']}\n";
        }
        return $resp;
    }

    private function porDificultad(string $lower): string {
        if (str_contains($lower, 'fácil') || str_contains($lower, 'facil') ||
            str_contains($lower, 'sencill') || str_contains($lower, 'principiante')) {
            $nivel = 'fácil';
        } elseif (str_contains($lower, 'difícil') || str_contains($lower, 'dificil') ||
                  str_contains($lower, 'avanzado') || str_contains($lower, 'experto')) {
            $nivel = 'difícil';
        } else {
            $nivel = 'media';
        }

        $recetas = $this->recetaModel->porDificultad($nivel);
        if (empty($recetas)) {
            return "No encontré recetas de dificultad <strong>$nivel</strong>. 😕";
        }
        $emoji = $nivel === 'fácil' ? '🟢' : ($nivel === 'media' ? '🟡' : '🔴');
        $resp  = "$emoji <strong>Recetas de dificultad $nivel:</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['categoria']} ({$r['tiempo_preparacion']} min)\n";
        }
        return $resp;
    }

    private function porCategoria(string $categoria): string {
        $recetas = $this->recetaModel->porCategoria($categoria);
        if (empty($recetas)) {
            return "No encontré recetas de la categoría <strong>$categoria</strong>. 😕\n\nEscribe <em>\"¿qué categorías hay?\"</em> para ver las disponibles.";
        }
        $resp = "🌍 <strong>Recetas " . ucfirst($categoria) . ":</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['tiempo_preparacion']} min · {$r['dificultad']}\n";
        }
        return $resp;
    }

    private function porPais(string $paisConsultado): string {
        $mapa = [
            'colombiana' => 'Colombia', 'colombia' => 'Colombia',
            'argentina' => 'Argentina', 'perú' => 'Perú', 'peru' => 'Perú',
            'españa' => 'España', 'espana' => 'España', 'méxico' => 'México', 'mexico' => 'México',
            'turquía' => 'Turquía', 'turquia' => 'Turquía', 'japón' => 'Japón', 'japon' => 'Japón',
            'italia' => 'Italia', 'brasil' => 'Brasil', 'francia' => 'Francia', 'india' => 'India',
            'vietnam' => 'Vietnam', 'tailandia' => 'Tailandia', 'estados unidos' => 'Estados Unidos',
        ];
        $pais = $mapa[$paisConsultado] ?? ucfirst($paisConsultado);
        $recetas = $this->recetaModel->porPais($pais);

        $tipicos = [
            'Colombia' => 'La bandeja paisa es uno de los platos más representativos de Colombia. También son muy conocidos el ajiaco y el sancocho.',
            'Argentina' => 'El asado es uno de los platos más representativos de Argentina. También destacan las empanadas criollas.',
            'Perú' => 'El ceviche es uno de los platos más representativos de Perú. También destacan el lomo saltado y el ají de gallina.',
            'México' => 'Los tacos son uno de los platos más representativos de México. También destacan el pozole y los tamales.',
        ];
        $respuesta = '🌍 <strong>Gastronomía de ' . htmlspecialchars($pais, ENT_QUOTES, 'UTF-8') . ':</strong>\n\n';
        $respuesta .= ($tipicos[$pais] ?? 'Su gastronomía tiene platos tradicionales muy variados y llenos de sabor.') . "\n\n";

        if (empty($recetas)) {
            if ($pais === 'Colombia') {
                return $respuesta
                    . '<strong>Bandeja paisa (4 porciones)</strong>\n'
                    . '<strong>Ingredientes:</strong> frijoles rojos, arroz blanco, carne molida, chicharrón, chorizo, huevo, plátano maduro, aguacate y arepa.\n\n'
                    . '<strong>Preparación:</strong> cocina los frijoles hasta que estén tiernos; prepara el arroz; dora la carne, el chicharrón y el chorizo; fríe el huevo y el plátano; sirve todo junto con aguacate y arepa.\n\n'
                    . 'También puedo explicarte paso a paso cualquiera de sus ingredientes o recomendarte otra receta colombiana.';
            }
            return $respuesta . 'Todavía no tengo una receta de ese país cargada en el catálogo, pero puedo ayudarte a preparar un plato si me dices cuál te interesa.';
        }

        $respuesta .= '<strong>Recetas disponibles en GastroDigital:</strong>\n';
        foreach ($recetas as $receta) {
            $respuesta .= "• <strong>{$receta['titulo']}</strong> — {$receta['tiempo_preparacion']} min\n";
        }
        return $respuesta;
    }

    private function porIngrediente(string $lower): string {
        // Extraer el ingrediente: lo que viene después de "tengo", "con", "usando", etc.
        $ingrediente = $lower;
        foreach (['tengo', 'con ', 'usando ', 'utilizo ', 'uso '] as $kw) {
            if (str_contains($lower, $kw)) {
                $pos = strpos($lower, $kw);
                $ingrediente = trim(substr($lower, $pos + strlen($kw)));
                break;
            }
        }
        // Limpiar palabras comunes
        $ingrediente = preg_replace('/\b(qué|que|hago|puedo|hacer|receta|recetas|cocinar|preparar)\b/', '', $ingrediente);
        $ingrediente = trim($ingrediente);

        if (strlen($ingrediente) < 2) {
            return "¿Con qué ingrediente quieres cocinar? Dime algo como: <em>\"Tengo pollo y arroz\"</em> 🍗";
        }

        $recetas = $this->ingredienteModel->recetasConIngrediente($ingrediente);
        if (empty($recetas)) {
            // Intentar búsqueda general con el término
            $recetas = $this->recetaModel->buscar($ingrediente);
        }

        if (empty($recetas)) {
            return "No encontré recetas con <strong>$ingrediente</strong>. 😕\n\nPrueba con otro ingrediente o escribe el nombre de una receta.";
        }

        $resp = "🥕 <strong>Recetas con $ingrediente:</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['categoria']} ({$r['tiempo_preparacion']} min)\n";
        }
        return $resp;
    }

    private function buscarGeneral(string $mensaje): string {
        $termino = mb_strtolower(trim($mensaje), 'UTF-8');
        $termino = preg_replace('/\b(cómo|como|preparo|preparar|hago|hacer|puedo|quiero|dame|una|un|la|el|receta|recetas|de|para|cocinar)\b/u', ' ', $termino);
        $termino = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $termino);
        $termino = trim(preg_replace('/\s+/', ' ', $termino));
        $terminoBusqueda = $termino !== '' ? $termino : $mensaje;
        $recetas = $this->recetaModel->buscar($terminoBusqueda);
        if (empty($recetas)) {
            $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
            return "No encontré resultados para <strong>\"$mensajeSeguro\"</strong>. 🤔\n\n"
                 . "Intenta con:\n"
                 . "• El nombre de un plato (ej: <em>\"pasta\"</em>)\n"
                 . "• Un ingrediente (ej: <em>\"tengo pollo\"</em>)\n"
                 . "• Una categoría (ej: <em>\"recetas mexicanas\"</em>)\n"
                 . "• Escribe <em>\"ayuda\"</em> para ver todas las opciones";
        }

        $terminoSeguro = htmlspecialchars($terminoBusqueda, ENT_QUOTES, 'UTF-8');
        $resp = "🔍 <strong>Resultados para \"$terminoSeguro\":</strong>\n\n";
        foreach ($recetas as $r) {
            $resp .= "• <strong>{$r['titulo']}</strong> — {$r['categoria']} · {$r['tiempo_preparacion']} min · {$r['dificultad']}\n";
        }
        return $resp;
    }

    // ── Utilidades ────────────────────────────────────────────────────────────

    private function coincide(string $texto, array $palabras): bool {
        foreach ($palabras as $p) {
            if (str_contains($texto, $p)) return true;
        }
        return false;
    }

    private function esGastronomico(string $texto): bool {
        if ($this->coincide($texto, $this->temasNoGastronomicos)) {
            return false;
        }

        if ($this->coincide($texto, $this->intenciones['saludo']) ||
            $this->coincide($texto, $this->intenciones['despedida']) ||
            $this->coincide($texto, $this->intenciones['ayuda'])) {
            return true;
        }

        return $this->coincide($texto, $this->temasGastronomicos);
    }
}
