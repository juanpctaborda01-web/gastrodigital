<?php
/**
 * ChatbotModel.php
 * ─────────────────────────────────────────────────────────────
 * Responsabilidades:
 *   1. Consultar MySQL para obtener recetas/ingredientes/pasos REALES.
 *   2. Detectar la intención del mensaje (oficial vs. general).
 *   3. Construir el contexto delimitado que se envía a Ollama.
 *   4. Comunicarse con Ollama vía cURL (sin duplicar PDO).
 *   5. Guardar y reutilizar el historial de cada conversación.
 *
 * Regla fundamental: la BD es la única fuente de verdad para
 * recetas oficiales. Ollama NUNCA inventa ni presenta como oficiales
 * recetas que no provengan de MySQL.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';

class ChatbotModel
{
    // ── Configuración de Ollama ────────────────────────────────
    private const OLLAMA_URL     = 'http://localhost:11434/api/generate';
    private const MODELO_PRIMARY = 'chefbot:latest';
    private const MODELO_FALLBACK = 'qwen2.5:7b';
    private const TIMEOUT_OLLAMA  = 120; // segundos

    // ── Sistema de intención ───────────────────────────────────
    // Palabras que indican que el usuario quiere datos OFICIALES de GastroDigital
    private const TRIGGERS_OFICIAL = [
        'gastrodigital', 'oficial', 'de gastrodigital', 'en gastrodigital',
        'receta oficial', 'recetas oficiales', 'tienes receta', 'existe la receta',
        'hay receta', 'está disponible', 'está en el catálogo', 'catálogo',
        'recetas disponibles', 'qué recetas', 'que recetas', 'lista de recetas',
        'muéstrame recetas', 'mostrame recetas', 'dame recetas', 'ver recetas',
        'todas las recetas', 'ingredientes de', 'ingredientes del plato',
        'pasos de', 'cómo se prepara', 'como se prepara', 'cómo hacer',
        'como hacer', 'receta de ', 'receta del ', 'receta para ',
        'tiene ingredientes', 'qué lleva', 'que lleva', 'recetas de chef',
        'recetas por categoría', 'recetas por categoria', 'recetas fáciles',
        'recetas rapidas', 'recetas rápidas', 'recetas difíciles',
    ];

    // Palabras que indican consulta GENERAL (el modelo puede responder libremente)
    private const TRIGGERS_GENERAL = [
        'qué es', 'que es', 'qué significa', 'diferencia entre', 'cómo se llama',
        'técnica', 'tecnica', 'consejo', 'tip', 'truco', 'qué puedo hacer',
        'que puedo hacer', 'con qué combina', 'nutrición', 'nutricion',
        'historia de', 'origen de', 'propiedades',
    ];

    private \PDO $pdo;
    private ?string $sessionKey = null;

    public function __construct()
    {
        // Reutiliza el singleton — nunca abre una segunda conexión
        $this->pdo = Conexion::getInstancia()->getPDO();
    }

    private function esSolicitudVariante(string $mensaje): bool
    {
        return (bool) preg_match('/\b(cambi\w*|modific\w*|sustituy\w*|reemplaz\w*|quitar\w*|agreg\w*)\b/u', $mensaje);
    }

    private function respuestaSolicitudVariante(string $titulo): string
    {
        return "La receta oficial **{$titulo}** se mantiene tal como está registrada en GastroDigital. Puedo ayudarte a pensar una variante culinaria, pero sería una sugerencia no oficial y no reemplazaría la receta del catálogo.";
    }

    // ══════════════════════════════════════════════════════════
    //  PUNTO DE ENTRADA PRINCIPAL
    // ══════════════════════════════════════════════════════════

    /**
     * Procesa el mensaje y devuelve la respuesta de Chefbot.
     *
     * @param  string $mensaje  Texto del usuario
     * @param  int|null $userId ID del usuario logueado (o null si es visitante)
     * @return array  ['respuesta' => string, 'tipo' => 'oficial'|'general'|'error']
     */
    public function procesar(string $mensaje, ?int $userId = null, ?string $sessionKey = null): array
    {
        $mensaje = trim($mensaje);

        if ($mensaje === '') {
            return ['respuesta' => 'Por favor escríbeme algo para poder ayudarte. 👨‍🍳', 'tipo' => 'error'];
        }

        $sessionKey = $this->resolverSessionKey($sessionKey);
        $historial = $this->obtenerHistorial($userId, $sessionKey);
        $lower = mb_strtolower($mensaje, 'UTF-8');
        $recetaMencionada = $this->buscarTituloRecetaEnMensaje($lower);

        if ($this->pideCategorias($lower)) {
            $respuesta = $this->formatearCategorias($this->listarCategorias());
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => 'catalogo'];
        }

        $categoria = $this->extraerCategoria($lower);
        if ($categoria !== null && $this->mencionaRecetasPorCategoria($lower)) {
            $recetas = $this->recetasPorCategoria($categoria);
            $respuesta = $this->formatearListaRecetas($recetas);
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => $recetas === [] ? 'no_encontrada' : 'catalogo'];
        }
        if ($this->mencionaRecetasPorCategoria($lower)
            && str_contains($lower, 'categor')
            && $categoria === null) {
            $respuesta = 'No reconocí esa categoría en la base de datos de GastroDigital. '
                . 'Pídeme la lista de categorías para ver las opciones registradas.';
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => 'no_encontrada'];
        }

        if ($recetaMencionada === null && $this->esSolicitudRecetaEspecifica($lower)) {
            $respuesta = $this->respuestaRecetaNoEncontrada();
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => 'no_encontrada'];
        }

        if ($this->esSolicitudRecomendacion($lower) && $recetaMencionada === null) {
            $respuesta = $this->recomendarRecetas($mensaje, $historial);
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => 'catalogo'];
        }

        if ($this->pideLista($lower) && $recetaMencionada === null) {
            $respuesta = $this->formatearListaRecetas($this->listarRecetas());
            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => 'catalogo'];
        }

        // ── Saludo directo (sin consultar Ollama) ─────────────
        if ($this->esSaludo($lower)) {
            $resp = $this->saludoDirecto();
            $this->guardarSesion($userId, $sessionKey, $mensaje, $resp);
            return ['respuesta' => $resp, 'tipo' => 'general'];
        }

        // ── Detectar intención ────────────────────────────────
        $intencion = $this->detectarIntencion($lower);
        if ($recetaMencionada !== null) {
            $intencion = 'oficial';
        }
        if ($intencion === 'general'
            && $this->esSeguimientoReceta($lower)
            && $this->nombreRecetaDelHistorial($historial) !== null) {
            $intencion = 'oficial';
        }

        // ── Construir contexto según intención ────────────────
        $contexto = '';
        $tipoFinal = 'general';

        if ($intencion === 'oficial') {
            if ($recetaMencionada !== null && $this->esSolicitudVariante($lower)) {
                $respuesta = $this->respuestaSolicitudVariante($recetaMencionada);
                $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
                return ['respuesta' => $respuesta, 'tipo' => 'oficial'];
            }

            $datos = $this->buscarDatosOficiales($lower, $mensaje, $historial);
            if ($datos !== null) {
                $tipoFinal = 'catalogo';
                $respuesta = $this->formatearDatosOficiales($datos);
            } else {
                $tipoFinal = 'no_encontrada';
                $respuesta = $this->respuestaRecetaNoEncontrada();
            }

            $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);
            return ['respuesta' => $respuesta, 'tipo' => $tipoFinal];
        }

        // ── Llamar a Ollama ───────────────────────────────────
        $respuesta = $this->consultarOllama($mensaje, $contexto, $intencion, $historial);
        $respuesta = "Nota: esta respuesta es conocimiento general generado por el modelo; "
            . "no consulté fuentes externas ni la verifiqué como receta oficial de GastroDigital.\n\n"
            . $respuesta;

        $this->guardarSesion($userId, $sessionKey, $mensaje, $respuesta);

        return ['respuesta' => $respuesta, 'tipo' => $tipoFinal];
    }

    // ══════════════════════════════════════════════════════════
    //  DETECCIÓN DE INTENCIÓN
    // ══════════════════════════════════════════════════════════

    private function detectarIntencion(string $lower): string
    {
        foreach (self::TRIGGERS_OFICIAL as $trigger) {
            if (str_contains($lower, $trigger)) {
                return 'oficial';
            }
        }
        return 'general';
    }

    private function esSaludo(string $lower): bool
    {
        $saludos = ['hola', 'buenas', 'buenos días', 'buenos dias', 'buenas tardes',
                    'buenas noches', 'hey', 'hi ', 'hello', 'buen día', 'buen dia',
                    'saludos', '¿quién eres', 'quien eres', '¿qué eres', 'que eres'];
        foreach ($saludos as $s) {
            if (str_contains($lower, $s)) return true;
        }
        return false;
    }

    // ══════════════════════════════════════════════════════════
    //  CONSULTAS A MYSQL — FUENTE DE VERDAD
    // ══════════════════════════════════════════════════════════

    /**
     * Decide qué consultar según el mensaje y devuelve los datos.
     * Retorna null si no se encuentra nada.
     */
    private function buscarDatosOficiales(string $lower, string $original, array $historial = []): ?array
    {
        // 1. ¿Pide ingredientes/pasos de una receta específica?
        $nombreReceta = $this->extraerNombreReceta($lower)
            ?? $this->buscarTituloRecetaEnMensaje($lower)
            ?? ($this->esSeguimientoReceta($lower) ? $this->nombreRecetaDelHistorial($historial) : null);
        if ($nombreReceta !== null) {
            $receta = $this->obtenerRecetaCompleta($nombreReceta);
            if ($receta) return ['tipo' => 'receta_completa', 'datos' => $receta];
        }

        // 2. ¿Pide lista general o recetas disponibles?
        if ($this->pideLista($lower)) {
            $recetas = $this->listarRecetas();
            if (!empty($recetas)) return ['tipo' => 'lista_recetas', 'datos' => $recetas];
        }

        // 3. ¿Pide por categoría?
        $categoria = $this->extraerCategoria($lower);
        if ($categoria !== null) {
            $recetas = $this->recetasPorCategoria($categoria);
            if (!empty($recetas)) return ['tipo' => 'por_categoria', 'datos' => $recetas, 'filtro' => $categoria];
        }

        // 4. ¿Pide por dificultad?
        $dificultad = $this->extraerDificultad($lower);
        if ($dificultad !== null) {
            $recetas = $this->recetasPorDificultad($dificultad);
            if (!empty($recetas)) return ['tipo' => 'por_dificultad', 'datos' => $recetas, 'filtro' => $dificultad];
        }

        // 5. Búsqueda libre por título
        if ($nombreReceta !== null) {
            $recetas = $this->buscarPorTitulo($nombreReceta);
            if (!empty($recetas)) return ['tipo' => 'busqueda', 'datos' => $recetas, 'filtro' => $nombreReceta];
        }

        // 6. Búsqueda libre con el mensaje completo limpio
        $termino = $this->limpiarTermino($lower);
        if (strlen($termino) >= 3) {
            $recetas = $this->buscarPorTitulo($termino);
            if (!empty($recetas)) return ['tipo' => 'busqueda', 'datos' => $recetas, 'filtro' => $termino];
        }

        return null;
    }

    // ── Obtener receta completa con ingredientes y pasos ──────

    public function obtenerRecetaCompleta(string $termino): ?array
    {
        // Detectar columnas reales (tolerante a dos schemas)
        $pkReceta    = $this->pkReal('recetas');
        $fkCat       = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $fkChef      = $this->colEntre('recetas', ['chef_id', 'id_chef']);
        $pkCat       = $this->pkReal('categorias');
        $pkUsuario   = $this->pkReal('usuarios');
        $pkIng       = $this->pkReal('ingredientes');
        $fkIngReceta = $this->colEntre('ingredientes', ['receta_id', 'id_receta']);
        $pkPaso      = $this->pkReal('pasos');
        $fkPasoRec   = $this->colEntre('pasos', ['receta_id', 'id_receta']);
        $wherePublicada = $this->filtroRecetasPublicadas();

        $stmt = $this->pdo->prepare("
            SELECT r.$pkReceta AS id,
                   r.titulo, r.descripcion, r.tiempo_preparacion, r.porciones,
                   r.dificultad, r.imagen, r.calorias,
                   c.nombre AS categoria,
                   u.nombre AS chef
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCat   = r.$fkCat
            LEFT JOIN usuarios   u ON u.$pkUsuario = r.$fkChef
            WHERE $wherePublicada AND (r.titulo LIKE ? OR r.titulo LIKE ?)
            ORDER BY CHAR_LENGTH(r.titulo) ASC
            LIMIT 1
        ");
        $stmt->execute(['%' . $termino . '%', $termino . '%']);
        $receta = $stmt->fetch();

        if (!$receta) return null;

        // Ingredientes
        $ordenIngrediente = $this->colExiste('ingredientes', 'orden') ? 'orden' : $pkIng;
        $stmtIng = $this->pdo->prepare("
            SELECT nombre, cantidad, unidad, opcional
            FROM ingredientes
            WHERE $fkIngReceta = ?
            ORDER BY $ordenIngrediente ASC, $pkIng ASC
        ");
        $stmtIng->execute([$receta['id']]);
        $receta['ingredientes'] = $stmtIng->fetchAll();

        // Pasos
        $numeroPaso = $this->colEntre('pasos', ['numero', 'numero_paso']);
        $stmtPaso = $this->pdo->prepare("
            SELECT $numeroPaso AS numero, descripcion
            FROM pasos
            WHERE $fkPasoRec = ?
            ORDER BY $numeroPaso ASC
        ");
        $stmtPaso->execute([$receta['id']]);
        $receta['pasos'] = $stmtPaso->fetchAll();

        return $receta;
    }

    // ── Listar recetas ────────────────────────────────────────

    public function listarRecetas(?int $limite = null): array
    {
        $pkReceta  = $this->pkReal('recetas');
        $fkCat     = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $pkCat     = $this->pkReal('categorias');
        $wherePublicada = $this->filtroRecetasPublicadas();
        $orden = $this->colExiste('recetas', 'destacada') ? 'r.destacada DESC, ' : '';
        if ($this->colExiste('recetas', 'visitas')) {
            $orden .= 'r.visitas DESC, ';
        }

        $sql = "
            SELECT r.$pkReceta AS id, r.titulo, r.tiempo_preparacion, r.dificultad,
                   c.nombre AS categoria
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCat = r.$fkCat
            WHERE $wherePublicada
            ORDER BY $orden r.$pkReceta ASC
        ";
        if ($limite !== null) {
            $sql .= ' LIMIT ?';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($limite === null ? [] : [$limite]);
        return $stmt->fetchAll();
    }

    // ── Recetas por categoría ─────────────────────────────────

    public function recetasPorCategoria(string $categoria): array
    {
        $pkReceta = $this->pkReal('recetas');
        $fkCat    = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $pkCat    = $this->pkReal('categorias');
        $wherePublicada = $this->filtroRecetasPublicadas();

        $stmt = $this->pdo->prepare("
            SELECT r.$pkReceta AS id, r.titulo, r.tiempo_preparacion, r.dificultad,
                   c.nombre AS categoria
            FROM recetas r
            JOIN categorias c ON c.$pkCat = r.$fkCat
            WHERE $wherePublicada AND c.nombre LIKE ?
            ORDER BY r.titulo ASC
        ");
        $stmt->execute(['%' . $categoria . '%']);
        return $stmt->fetchAll();
    }

    // ── Recetas por dificultad ────────────────────────────────

    public function recetasPorDificultad(string $dificultad): array
    {
        $pkReceta = $this->pkReal('recetas');
        $fkCat    = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $pkCat    = $this->pkReal('categorias');
        $wherePublicada = $this->filtroRecetasPublicadas();

        $stmt = $this->pdo->prepare("
            SELECT r.$pkReceta AS id, r.titulo, r.tiempo_preparacion, r.dificultad,
                   c.nombre AS categoria
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCat = r.$fkCat
            WHERE $wherePublicada AND r.dificultad LIKE ?
            ORDER BY r.titulo ASC
        ");
        $stmt->execute(['%' . $dificultad . '%']);
        return $stmt->fetchAll();
    }

    // ── Búsqueda libre por título ─────────────────────────────

    public function buscarPorTitulo(string $termino): array
    {
        $pkReceta = $this->pkReal('recetas');
        $fkCat    = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $pkCat    = $this->pkReal('categorias');
        $wherePublicada = $this->filtroRecetasPublicadas();

        $stmt = $this->pdo->prepare("
            SELECT r.$pkReceta AS id, r.titulo, r.tiempo_preparacion, r.dificultad,
                   c.nombre AS categoria
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCat = r.$fkCat
            WHERE $wherePublicada AND r.titulo LIKE ?
            ORDER BY r.titulo ASC
        ");
        $stmt->execute(['%' . $termino . '%']);
        return $stmt->fetchAll();
    }

    // ── Listar categorías ─────────────────────────────────────

    public function listarCategorias(): array
    {
        return $this->pdo->query(
            "SELECT DISTINCT TRIM(nombre) FROM categorias WHERE TRIM(nombre) <> '' ORDER BY TRIM(nombre) ASC"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function pideCategorias(string $mensaje): bool
    {
        return (bool) preg_match(
            '/\b(categor[ií]as|categor[ií]a)\b/u',
            $mensaje
        ) && (bool) preg_match(
            '/\b(qu[eé]|hay|tienes|lista|listar|mu[eé]strame|mostrar|cu[aá]les|cuales|existen|disponibles)\b/u',
            $mensaje
        );
    }

    private function mencionaRecetasPorCategoria(string $mensaje): bool
    {
        return (bool) preg_match('/\b(recetas?|platos?)\b/u', $mensaje);
    }

    private function formatearCategorias(array $categorias): string
    {
        if ($categorias === []) {
            return 'No hay categorías registradas en la base de datos de GastroDigital.';
        }
        return "Estas son las categorías registradas en GastroDigital:\n- "
            . implode("\n- ", $categorias);
    }

    private function esSolicitudRecomendacion(string $mensaje): bool
    {
        return (bool) preg_match(
            '/\b(recomiend\w*|sugier\w*|sugerencia\w*|ideas?\s+(?:para|de)|qué puedo (?:cocinar|preparar|hacer)|que puedo (?:cocinar|preparar|hacer)|qué hago de comer|que hago de comer|qué preparo|que preparo|qué cocino|que cocino|dame algo (?:para|de) (?:comer|cenar|almorzar|desayunar)|algo (?:para|de) (?:cenar|almorzar|desayunar)|receta con|tengo .{2,}|(?:dame|hazme|prep[aá]rame|crea|arma|dime|mu[eé]strame|ens[eé]ñame)\s+(?:(?:una?|alguna|otra|algunas|otras|la|las|los)\s+)?recetas?|quiero (?:una?|alguna) receta|busco (?:una?|alguna) receta|necesito (?:una?|alguna) receta|alguna receta|recetas? (?:para|con))\b/u',
            $mensaje
        ) || str_contains($mensaje, 'dame una receta oficial')
          || str_contains($mensaje, 'una receta oficial')
          || str_contains($mensaje, 'recetas oficiales');
    }

    private function esSolicitudRecetaEspecifica(string $mensaje): bool
    {
        $esLista = $this->pideLista($mensaje);
        return !$esLista && preg_match(
            '/\b(?:receta\s+(?:oficial\s+)?(?:de|del)|(?:dame|quiero|busco|p[aá]same)\s+la receta\s+(?:de|del|para))\s+(?!gastrodigital\b)[^?!.]{2,}/u',
            $mensaje
        ) === 1;
    }

    private function respuestaRecetaNoEncontrada(): string
    {
        return 'No encontré esa receta entre las recetas publicadas del catálogo de GastroDigital. '
            . 'No tengo búsqueda web conectada y no consulté fuentes externas, así que no puedo citar '
            . 'ni verificar información fuera de la base de datos. Si quieres, puedo ofrecerte una '
            . 'sugerencia de cocina basada en conocimiento general del modelo, claramente no oficial.';
    }

    private function buscarTituloRecetaEnMensaje(string $mensaje): ?string
    {
        $mensajeNormalizado = $this->normalizarTexto($mensaje);
        $wherePublicada = $this->filtroRecetasPublicadas();
        $stmt = $this->pdo->query("SELECT titulo FROM recetas r WHERE $wherePublicada");
        $titulos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        usort($titulos, static fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($titulos as $titulo) {
            $tituloNormalizado = $this->normalizarTexto((string) $titulo);
            if ($tituloNormalizado !== '' && str_contains($mensajeNormalizado, $tituloNormalizado)) {
                return (string) $titulo;
            }
        }
        return null;
    }

    private function formatearListaRecetas(array $recetas): string
    {
        if ($recetas === []) {
            return 'No encontré recetas registradas en la base de datos de GastroDigital.';
        }

        $respuesta = "Estas son recetas registradas en GastroDigital:\n";
        foreach ($recetas as $receta) {
            $respuesta .= "\n- **{$receta['titulo']}**";
            if (!empty($receta['categoria'])) {
                $respuesta .= ' (' . $receta['categoria'] . ')';
            }
            if (!empty($receta['tiempo_preparacion'])) {
                $respuesta .= ' — ' . $receta['tiempo_preparacion'] . ' min';
            }
            if (!empty($receta['dificultad'])) {
                $respuesta .= ', dificultad ' . $receta['dificultad'];
            }
        }

        return $respuesta;
    }

    private function formatearDatosOficiales(array $resultado): string
    {
        if ($resultado['tipo'] === 'receta_completa') {
            $receta = $resultado['datos'];
            $respuesta = "**{$receta['titulo']}** — receta del catálogo de GastroDigital.\n";
            if (!empty($receta['descripcion'])) {
                $respuesta .= "\n{$receta['descripcion']}\n";
            }
            if (!empty($receta['categoria'])) {
                $respuesta .= "\nCategoría: {$receta['categoria']}";
            }
            if (!empty($receta['tiempo_preparacion'])) {
                $respuesta .= "\nTiempo: {$receta['tiempo_preparacion']} minutos";
            }
            if (!empty($receta['porciones'])) {
                $respuesta .= "\nPorciones: {$receta['porciones']}";
            }
            if (!empty($receta['dificultad'])) {
                $respuesta .= "\nDificultad: {$receta['dificultad']}";
            }

            $respuesta .= "\n\nIngredientes:";
            if ($receta['ingredientes'] === []) {
                $respuesta .= "\nNo hay ingredientes registrados para esta receta.";
            } else {
                foreach ($receta['ingredientes'] as $ingrediente) {
                    $cantidad = trim(($ingrediente['cantidad'] ?? '') . ' ' . ($ingrediente['unidad'] ?? ''));
                    $respuesta .= "\n- " . ($cantidad !== '' ? $cantidad . ' ' : '')
                        . $ingrediente['nombre']
                        . (!empty($ingrediente['opcional']) ? ' (opcional)' : '');
                }
            }

            $respuesta .= "\n\nPreparación:";
            if ($receta['pasos'] === []) {
                $respuesta .= "\nNo hay pasos registrados para esta receta.";
            } else {
                foreach ($receta['pasos'] as $paso) {
                    $respuesta .= "\n{$paso['numero']}. {$paso['descripcion']}";
                }
            }
            return $respuesta;
        }

        $filas = $resultado['datos'];
        if ($filas === []) {
            return $this->respuestaRecetaNoEncontrada();
        }

        return $this->formatearListaRecetas($filas);
    }

    private function esSeguimientoReceta(string $mensaje): bool
    {
        return (bool) preg_match(
            '/\b(y|tambien|también|esa|ese|esta|este|la receta|esa receta|esa preparacion|esa preparación|sus ingredientes|cuanto tarda|cuánto tarda|cuanto tiempo|cuánto tiempo|ingredientes|preparacion|preparación|pasos)\b/u',
            $mensaje
        );
    }

    private function nombreRecetaDelHistorial(array $historial): ?string
    {
        if ($historial === []) {
            return null;
        }

        $wherePublicada = $this->filtroRecetasPublicadas();
        $stmt = $this->pdo->query("SELECT titulo FROM recetas r WHERE $wherePublicada");
        $titulos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        usort($titulos, static fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach (array_reverse($historial) as $turno) {
            $texto = $this->normalizarTexto($turno['mensaje'] . ' ' . $turno['respuesta']);
            foreach ($titulos as $titulo) {
                if (str_contains($texto, $this->normalizarTexto((string) $titulo))) {
                    return (string) $titulo;
                }
            }
        }
        return null;
    }

    private function recomendarRecetas(string $mensaje, array $historial): string
    {
        $pkReceta = $this->pkReal('recetas');
        $fkCategoria = $this->colEntre('recetas', ['categoria_id', 'id_categoria']);
        $pkCategoria = $this->pkReal('categorias');
        $fkIngrediente = $this->colEntre('ingredientes', ['receta_id', 'id_receta']);
        $wherePublicada = $this->filtroRecetasPublicadas();
        $orden = $this->colExiste('recetas', 'destacada') ? 'r.destacada DESC, ' : '';
        if ($this->colExiste('recetas', 'visitas')) {
            $orden .= 'r.visitas DESC, ';
        }

        $stmt = $this->pdo->query("
            SELECT r.$pkReceta AS id, r.titulo, r.descripcion, r.tiempo_preparacion,
                   r.dificultad, c.nombre AS categoria, i.nombre AS ingrediente
            FROM recetas r
            LEFT JOIN categorias c ON c.$pkCategoria = r.$fkCategoria
            LEFT JOIN ingredientes i ON i.$fkIngrediente = r.$pkReceta
            WHERE $wherePublicada
            ORDER BY $orden r.$pkReceta ASC
        ");
        $recetas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            if (!isset($recetas[$id])) {
                $recetas[$id] = [
                    'titulo' => $fila['titulo'],
                    'tiempo' => $fila['tiempo_preparacion'],
                    'dificultad' => $fila['dificultad'],
                    'categoria' => $fila['categoria'],
                    'ingredientes' => [],
                    'coinciden' => [],
                ];
            }
            if ($fila['ingrediente'] !== null) {
                $recetas[$id]['ingredientes'][] = $fila['ingrediente'];
            }
        }

        if ($recetas === []) {
            return 'Todavía no hay recetas registradas en la base de datos de GastroDigital.';
        }

        $mensajeNormalizado = $this->normalizarTexto($mensaje);
        $ingredientesMencionados = [];
        foreach ($recetas as $receta) {
            foreach ($receta['ingredientes'] as $ingrediente) {
                $ingredienteNormalizado = $this->normalizarTexto($ingrediente);
                if ($ingredienteNormalizado !== ''
                    && $this->coincideIngrediente($mensajeNormalizado, $ingredienteNormalizado)) {
                    $ingredientesMencionados[$ingredienteNormalizado] = $ingrediente;
                }
            }
        }

        $yaSugeridas = [];
        foreach ($historial as $turno) {
            foreach ($recetas as $id => $receta) {
                if (str_contains(
                    $this->normalizarTexto((string) $turno['respuesta']),
                    $this->normalizarTexto((string) $receta['titulo'])
                )) {
                    $yaSugeridas[$id] = true;
                }
            }
        }

        $candidatas = [];
        foreach ($recetas as $id => $receta) {
            if (isset($yaSugeridas[$id])) {
                continue;
            }
            foreach ($receta['ingredientes'] as $ingrediente) {
                $clave = $this->normalizarTexto($ingrediente);
                if (isset($ingredientesMencionados[$clave])) {
                    $receta['coinciden'][] = $ingredientesMencionados[$clave];
                }
            }
            $receta['coinciden'] = array_values(array_unique($receta['coinciden']));
            $candidatas[] = $receta;
        }

        if ($candidatas === []) {
            return 'Ya te sugerí todas las recetas disponibles en esta conversación. Si quieres, puedo volver a mostrarte alguna que ya vimos.';
        }

        if ($ingredientesMencionados !== []) {
            usort($candidatas, static function (array $a, array $b): int {
                return count($b['coinciden']) <=> count($a['coinciden']);
            });
            $candidatas = array_values(array_filter(
                $candidatas,
                static fn(array $receta): bool => $receta['coinciden'] !== []
            ));

            if ($candidatas === []) {
                return 'No encontré una receta del catálogo que use los ingredientes que mencionaste. Dime otros ingredientes o pídeme una sugerencia del catálogo.';
            }
        }

        $recetaElegida = $candidatas[0];
        $recetaCompleta = $this->obtenerRecetaCompleta($recetaElegida['titulo']);
        if ($recetaCompleta === null) {
            throw new \RuntimeException('No se pudo recuperar la receta seleccionada desde la base de datos.');
        }

        $respuesta = $ingredientesMencionados !== []
            ? 'Encontré una receta oficial de GastroDigital que coincide con algunos de tus ingredientes:'
            : 'Esta es mi recomendación del catálogo oficial de GastroDigital:';
        $respuesta .= "\n\n" . $this->formatearDatosOficiales([
            'tipo' => 'receta_completa',
            'datos' => $recetaCompleta,
        ]);

        if ($recetaElegida['coinciden'] !== []) {
            $respuesta .= "\n\nCoincide con lo que tienes: "
                . implode(', ', $recetaElegida['coinciden']) . '.';
        }

        return $respuesta;
    }

    private function coincideIngrediente(string $mensaje, string $ingrediente): bool
    {
        if (str_contains($mensaje, $ingrediente)) {
            return true;
        }

        $partes = preg_split('/\s+y\s+|\s+o\s+|,\s*|\/+/', $ingrediente) ?: [];
        $palabrasIgnoradas = [
            'fresco', 'fresca', 'molido', 'molida', 'rallado', 'rallada',
            'para', 'con', 'sin', 'por', 'del', 'las', 'los', 'una', 'unos',
            'unas', 'gusto', 'opcional',
        ];
        foreach ($partes as $parte) {
            $palabras = preg_split('/\s+/', trim($parte)) ?: [];
            foreach ($palabras as $palabra) {
                if (strlen($palabra) < 4 || in_array($palabra, $palabrasIgnoradas, true)) {
                    continue;
                }
                if (preg_match('/(?<![a-z0-9])' . preg_quote($palabra, '/') . '(?![a-z0-9])/', $mensaje)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        if (function_exists('iconv')) {
            $sinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($sinAcentos !== false) {
                $texto = $sinAcentos;
            }
        }
        return $texto;
    }

    // ══════════════════════════════════════════════════════════
    //  CONSTRUCCIÓN DE CONTEXTO PARA OLLAMA
    // ══════════════════════════════════════════════════════════

    private function construirContextoOficial(array $resultado): string
    {
        $tipo  = $resultado['tipo'];
        $datos = $resultado['datos'];

        switch ($tipo) {

            case 'receta_completa':
                $r = $datos;
                $ctx  = "=== INFORMACIÓN OFICIAL DE GASTRODIGITAL ===\n\n";
                $ctx .= "Título: {$r['titulo']}\n";
                $ctx .= "Categoría: " . ($r['categoria'] ?? 'Sin categoría') . "\n";
                $ctx .= "Dificultad: {$r['dificultad']}\n";
                $ctx .= "Tiempo de preparación: {$r['tiempo_preparacion']} minutos\n";
                $ctx .= "Porciones: {$r['porciones']}\n";
                if (!empty($r['calorias'])) $ctx .= "Calorías por porción: {$r['calorias']} kcal\n";
                if (!empty($r['chef'])) $ctx .= "Chef: {$r['chef']}\n";
                if (!empty($r['descripcion'])) $ctx .= "Descripción: {$r['descripcion']}\n";

                // Ingredientes
                if (!empty($r['ingredientes'])) {
                    $ctx .= "\nIngredientes:\n";
                    foreach ($r['ingredientes'] as $ing) {
                        $linea = '- ';
                        if (!empty($ing['cantidad'])) $linea .= $ing['cantidad'] . ' ';
                        if (!empty($ing['unidad']))   $linea .= $ing['unidad']   . ' ';
                        $linea .= $ing['nombre'];
                        if (!empty($ing['opcional']) && $ing['opcional']) $linea .= ' (opcional)';
                        $ctx .= $linea . "\n";
                    }
                } else {
                    $ctx .= "\nIngredientes: No registrados en la base de datos.\n";
                }

                // Pasos
                if (!empty($r['pasos'])) {
                    $ctx .= "\nPreparación:\n";
                    foreach ($r['pasos'] as $p) {
                        $ctx .= "{$p['numero']}. {$p['descripcion']}\n";
                    }
                } else {
                    $ctx .= "\nPreparación: Pasos no registrados en la base de datos.\n";
                }

                $ctx .= "\n=== FIN DE INFORMACIÓN OFICIAL ===\n\n";
                $ctx .= "INSTRUCCIONES IMPORTANTES:\n";
                $ctx .= "- Usa ÚNICAMENTE los datos proporcionados arriba para describir esta receta.\n";
                $ctx .= "- NO agregues ingredientes que no aparezcan en la lista.\n";
                $ctx .= "- NO inventes pasos adicionales.\n";
                $ctx .= "- NO cambies cantidades ni unidades.\n";
                $ctx .= "- Si el usuario pregunta algo que no está en los datos, indícalo claramente.\n";
                $ctx .= "- Nunca presentes información creada por ti como información oficial de GastroDigital.\n";
                $ctx .= "- Puedes sugerir variantes o consejos, pero marcándolos claramente como sugerencias personales tuyas, NO como parte de la receta oficial.\n";
                return $ctx;

            case 'lista_recetas':
                $ctx  = "=== RECETAS OFICIALES DISPONIBLES EN GASTRODIGITAL ===\n\n";
                foreach ($datos as $r) {
                    $cat = isset($r['categoria']) ? " [{$r['categoria']}]" : '';
                    $ctx .= "- {$r['titulo']}{$cat} — {$r['tiempo_preparacion']} min — {$r['dificultad']}\n";
                }
                $ctx .= "\n=== FIN DE LISTA OFICIAL ===\n\n";
                $ctx .= "INSTRUCCIONES: Presenta estas recetas al usuario. Solo son oficiales las que aparecen en esta lista. No inventes ni menciones recetas adicionales como si fueran oficiales de GastroDigital.\n";
                return $ctx;

            case 'por_categoria':
            case 'por_dificultad':
            case 'busqueda':
                $filtro = $resultado['filtro'] ?? '';
                $ctx  = "=== RECETAS ENCONTRADAS EN GASTRODIGITAL";
                if ($filtro) $ctx .= " (filtro: $filtro)";
                $ctx .= " ===\n\n";
                foreach ($datos as $r) {
                    $cat = isset($r['categoria']) ? " [{$r['categoria']}]" : '';
                    $ctx .= "- {$r['titulo']}{$cat} — {$r['tiempo_preparacion']} min — {$r['dificultad']}\n";
                }
                $ctx .= "\n=== FIN DE RESULTADOS ===\n\n";
                $ctx .= "INSTRUCCIONES: Presenta solo estas recetas como resultados de GastroDigital. No inventes recetas adicionales.\n";
                return $ctx;
        }

        return '';
    }

    private function contextoSinResultados(string $mensaje): string
    {
        return "=== CONSULTA A GASTRODIGITAL SIN RESULTADOS ===\n\n"
             . "Se buscó en la base de datos oficial de GastroDigital y NO se encontraron recetas "
             . "relacionadas con: \"$mensaje\"\n\n"
             . "=== FIN ===\n\n"
             . "INSTRUCCIONES: Informa al usuario que no encontraste esa receta en el catálogo oficial "
             . "de GastroDigital. NO inventes una receta y NO la presentes como si fuera oficial. "
             . "Puedes ofrecer ayuda gastronómica general si el usuario lo desea, pero siempre "
             . "aclarando que es conocimiento general, no una receta oficial de GastroDigital.\n";
    }

    // ══════════════════════════════════════════════════════════
    //  COMUNICACIÓN CON OLLAMA
    // ══════════════════════════════════════════════════════════

    private function consultarOllama(
        string $mensaje,
        string $contexto,
        string $intencion,
        array $historial = []
    ): string
    {
        // Prompt del sistema: define quién es Chefbot
        $systemPrompt = $this->buildSystemPrompt();

        // Prompt completo que se envía al modelo
        $promptCompleto = '';
        if ($contexto !== '') {
            $promptCompleto = $contexto . "\n";
        }
        if ($historial !== []) {
            $promptCompleto .= "=== CONVERSACIÓN ANTERIOR (solo contexto; los datos oficiales se consultan en la base de datos) ===\n";
            foreach (array_slice($historial, -8) as $turno) {
                $promptCompleto .= "Usuario: {$turno['mensaje']}\nChefbot: {$turno['respuesta']}\n";
            }
            $promptCompleto .= "=== FIN DE CONVERSACIÓN ANTERIOR ===\n\n";
        }
        $promptCompleto .= "Usuario: $mensaje\n\nChefbot:";

        $payload = [
            'model'  => self::MODELO_PRIMARY,
            'prompt' => $promptCompleto,
            'system' => $systemPrompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.4,   // baja temperatura → respuestas más precisas y fieles
                'top_p'       => 0.9,
                'num_predict' => 600,   // límite de tokens en respuesta
            ],
        ];

        $respuesta = $this->llamarOllama($payload);

        // Si falla con el modelo primario, intentar con el de respaldo
        if ($respuesta === null) {
            $payload['model'] = self::MODELO_FALLBACK;
            $respuesta = $this->llamarOllama($payload);
        }

        if ($respuesta === null) {
            error_log('[Chefbot] Ollama no disponible al procesar: ' . $mensaje);
            return 'Chefbot no está disponible en este momento. Verifica que Ollama esté ejecutándose.';
        }

        return $respuesta;
    }

    /**
     * Realiza la petición HTTP a Ollama y devuelve el texto de la respuesta.
     * Retorna null si hay error de conexión, timeout o respuesta inválida.
     */
    private function llamarOllama(array $payload): ?string
    {
        $ch = curl_init(self::OLLAMA_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => self::TIMEOUT_OLLAMA,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $raw === false) {
            error_log('[Chefbot] cURL error: ' . $err);
            return null;
        }

        if ($code !== 200) {
            error_log('[Chefbot] Ollama HTTP ' . $code . ': ' . substr($raw, 0, 200));
            return null;
        }

        $data = json_decode($raw, true);

        if (!isset($data['response'])) {
            error_log('[Chefbot] Respuesta inesperada de Ollama: ' . substr($raw, 0, 200));
            return null;
        }

        $texto = trim($data['response']);
        return $texto !== '' ? $texto : null;
    }

    // ── System prompt ─────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Eres Chefbot, el asistente gastronómico oficial de GastroDigital, una plataforma de recetas de cocina.

REGLAS FUNDAMENTALES:
1. Tu nombre es Chefbot. Siempre que te pregunten quién eres, responde que eres Chefbot, el asistente gastronómico de GastroDigital.
2. Cuando recibes un bloque de información marcado con "=== INFORMACIÓN OFICIAL DE GASTRODIGITAL ===", esa es la única fuente que debes usar para responder sobre recetas de GastroDigital. No agregues, elimines ni modifiques nada de esa información.
   Todas las recetas que el sistema encuentre en la base de datos son oficiales; el historial sirve para entender referencias, no para sustituir una consulta actual a la base de datos.
3. Nunca presentes una receta que hayas generado tú mismo como si fuera una receta oficial de GastroDigital.
4. Si el usuario pide una receta oficial y no hay datos en el contexto, debes decir claramente que no encontraste esa receta en el catálogo de GastroDigital.
5. Para preguntas de gastronomía en general (técnicas, definiciones, sugerencias) puedes usar tu conocimiento sin restricciones, pero siempre aclarando que es conocimiento general y no una receta del catálogo oficial.
6. Responde siempre en español, de forma amable, clara y concisa.
7. Usa emojis ocasionalmente para hacer la conversación más amena, pero no en exceso.
8. Si te hacen preguntas que no son de gastronomía, indica amablemente que solo puedes ayudar con temas culinarios.
9. En una sugerencia, el sistema selecciona recetas existentes y evita repetir las ya sugeridas en esta conversación. No propongas otra receta como oficial si no aparece en los datos recibidos.
PROMPT;
    }

    // ══════════════════════════════════════════════════════════
    //  EXTRACTORES DE TÉRMINOS DEL MENSAJE
    // ══════════════════════════════════════════════════════════

    /**
     * Extrae el nombre de una receta mencionada en el mensaje.
     * Ejemplo: "¿qué ingredientes tiene el arroz con pollo?" → "arroz con pollo"
     */
    private function extraerNombreReceta(string $lower): ?string
    {
        $patrones = [
            '/(?:receta de |receta del |receta para |ingredientes de (?:la |el |los |las )?|pasos de (?:la |el )?|cómo (?:se )?(?:prepara|hace|cocina) (?:la |el |los |las )?|dame (?:la |el )?receta de (?:la |el )?)(.{3,60}?)(?:\?|$|\.|,)/u',
            '/(?:qué lleva |que lleva |tiene ingredientes )(?:la |el |los |las )?(.{3,60}?)(?:\?|$|\.|,)/u',
            '/(?:información (?:de|sobre) (?:la |el )?)(.{3,60}?)(?:\?|$|\.|,)/u',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $lower, $m)) {
                $nombre = trim($m[1]);
                $nombre = preg_replace('/\b(de|el|la|los|las|un|una|del)\b/', '', $nombre);
                $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
                if (strlen($nombre) >= 3) return $nombre;
            }
        }

        return null;
    }

    private function pideLista(string $lower): bool
    {
        $pistas = ['todas las recetas', 'lista de recetas', 'recetas disponibles',
                   'qué recetas hay', 'que recetas hay', 'mostrar recetas',
                   'ver recetas', 'dame recetas', 'recetas de gastrodigital',
                   'catálogo', 'catalogo', 'recetas oficiales'];
        foreach ($pistas as $p) {
            if (str_contains($lower, $p)) return true;
        }
        return false;
    }

    private function extraerCategoria(string $lower): ?string
    {
        $mensajeNormalizado = $this->normalizarTexto($lower);
        foreach ($this->listarCategorias() as $cat) {
            if (str_contains($mensajeNormalizado, $this->normalizarTexto((string) $cat))) {
                return (string) $cat;
            }
        }
        return null;
    }

    private function extraerDificultad(string $lower): ?string
    {
        if (str_contains($lower, 'fácil') || str_contains($lower, 'facil') || str_contains($lower, 'sencill')) return 'Fácil';
        if (str_contains($lower, 'difícil') || str_contains($lower, 'dificil') || str_contains($lower, 'avanzad')) return 'Difícil';
        if (str_contains($lower, 'intermedi') || str_contains($lower, 'media') || str_contains($lower, 'moderada')) return 'Media';
        return null;
    }

    private function limpiarTermino(string $texto): string
    {
        $stopwords = ['cómo', 'como', 'qué', 'que', 'cual', 'cuál', 'es', 'son', 'tiene',
                      'hay', 'receta', 'recetas', 'de', 'del', 'la', 'el', 'los', 'las',
                      'un', 'una', 'para', 'con', 'una', 'por', 'sus', 'puedo', 'puedes',
                      'hacer', 'preparar', 'cocinar', 'dame', 'muestra', 'quiero', 'ver',
                      'dame', 'dime', 'oficial', 'gastrodigital', 'busca', 'buscar',
                      'existe', 'tienes', 'tienen', 'disponible', 'disponibles'];
        $palabras = explode(' ', $texto);
        $filtradas = array_filter($palabras, fn($p) => !in_array($p, $stopwords) && strlen($p) >= 3);
        return trim(implode(' ', $filtradas));
    }

    // ══════════════════════════════════════════════════════════
    //  GUARDAR HISTORIAL
    // ══════════════════════════════════════════════════════════

    private function guardarSesion(
        ?int $userId,
        string $sessionKey,
        string $mensaje,
        string $respuesta
    ): void
    {
        $sessionId = $this->obtenerIdSesion($userId, $sessionKey, true);
        $stmt = $this->pdo->prepare("
            INSERT INTO mensajes_chatbot (session_id, mensaje, respuesta, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$sessionId, $mensaje, $respuesta]);
    }

    public function obtenerHistorial(?int $userId = null, ?string $sessionKey = null): array
    {
        $sessionKey = $this->resolverSessionKey($sessionKey);
        $sessionId = $this->obtenerIdSesion($userId, $sessionKey, false);
        if ($sessionId === null) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT mensaje, respuesta, created_at
            FROM mensajes_chatbot
            WHERE session_id = ?
            ORDER BY id_menss_chatbot DESC
        ");
        $stmt->execute([$sessionId]);
        return array_reverse($stmt->fetchAll());
    }

    public function limpiarHistorial(?int $userId = null, ?string $sessionKey = null): void
    {
        $sessionKey = $this->resolverSessionKey($sessionKey);
        $sessionId = $this->obtenerIdSesion($userId, $sessionKey, false);
        if ($sessionId === null) {
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM mensajes_chatbot WHERE session_id = ?');
        $stmt->execute([$sessionId]);
    }

    private function resolverSessionKey(?string $sessionKey): string
    {
        $sessionKey = $sessionKey ?? session_id();
        if ($sessionKey === '') {
            $this->sessionKey ??= bin2hex(random_bytes(32));
            $sessionKey = $this->sessionKey;
        }
        return strlen($sessionKey) <= 64 ? $sessionKey : hash('sha256', $sessionKey);
    }

    private function obtenerIdSesion(?int $userId, string $sessionKey, bool $crear): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_sesion, id_usuario FROM sesiones_chatbot WHERE session_key = ? LIMIT 1'
        );
        $stmt->execute([$sessionKey]);
        $sesion = $stmt->fetch();

        if ($sesion) {
            if ($sesion['id_usuario'] !== null
                && ($userId === null || (int) $sesion['id_usuario'] !== $userId)) {
                throw new \RuntimeException('La conversación no pertenece al usuario actual.');
            }
            if ($sesion['id_usuario'] === null && $userId !== null) {
                $stmt = $this->pdo->prepare(
                    'UPDATE sesiones_chatbot SET id_usuario = ? WHERE id_sesion = ? AND id_usuario IS NULL'
                );
                $stmt->execute([$userId, $sesion['id_sesion']]);
            }
            return (int) $sesion['id_sesion'];
        }

        if (!$crear) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO sesiones_chatbot (id_usuario, session_key, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$userId, $sessionKey]);
        return (int) $this->pdo->lastInsertId();
    }

    // ══════════════════════════════════════════════════════════
    //  HELPERS DE SCHEMA (tolerante a las dos versiones de BD)
    // ══════════════════════════════════════════════════════════

    private array $pkCache   = [];
    private array $colCache  = [];

    private function pkReal(string $tabla): string
    {
        if (!isset($this->pkCache[$tabla])) {
            try {
                $r = $this->pdo->query("SHOW KEYS FROM `$tabla` WHERE Key_name='PRIMARY'")->fetch(\PDO::FETCH_ASSOC);
                $this->pkCache[$tabla] = $r ? $r['Column_name'] : 'id';
            } catch (\Throwable $e) {
                $this->pkCache[$tabla] = 'id';
            }
        }
        return $this->pkCache[$tabla];
    }

    private function colExiste(string $tabla, string $columna): bool
    {
        $key = "$tabla.$columna";
        if (!isset($this->colCache[$key])) {
            try {
                $cols = $this->pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(\PDO::FETCH_COLUMN);
                $this->colCache[$key] = in_array($columna, $cols, true);
            } catch (\Throwable $e) {
                $this->colCache[$key] = false;
            }
        }
        return $this->colCache[$key];
    }

    private function colEntre(string $tabla, array $candidatas): string
    {
        foreach ($candidatas as $columna) {
            if ($this->colExiste($tabla, $columna)) {
                return $columna;
            }
        }

        throw new \RuntimeException(
            'No se encontró una columna compatible en la tabla ' . $tabla . '.'
        );
    }

    private function filtroRecetasPublicadas(): string
    {
        return $this->colExiste('recetas', 'publicada') ? 'r.publicada = 1' : '1=1';
    }

    // ── Respuesta de saludo rápida (sin llamar a Ollama) ──────

    private function saludoDirecto(): string
    {
        $hora = (int) date('H');
        if ($hora < 12)     $momento = '¡Buenos días';
        elseif ($hora < 18) $momento = '¡Buenas tardes';
        else                $momento = '¡Buenas noches';

        return "$momento! 👨‍🍳 Soy **Chefbot**, el asistente gastronómico de GastroDigital.\n\n"
             . "Puedo ayudarte con:\n"
             . "• 🍽️ Recetas oficiales del catálogo de GastroDigital\n"
             . "• 🥕 Ingredientes y pasos de preparación\n"
             . "• 💡 Preguntas generales de gastronomía y cocina\n"
             . "• 🔍 Buscar recetas por nombre, categoría o dificultad\n\n"
             . "¿En qué puedo ayudarte hoy?";
    }
}
