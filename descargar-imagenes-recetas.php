<?php
/**
 * GastroDigital — Herramienta de descarga automática de imágenes v3.0
 * ─────────────────────────────────────────────────────────────────────
 * Descarga imágenes para recetas (imagen principal) y para sus pasos.
 * Fuente: Wikimedia Commons — imágenes de dominio público / Creative Commons.
 * Requiere: sesión de administrador activa + token CSRF.
 *
 * MODOS (seleccionables desde la interfaz):
 *   recetas_sin_imagen  — solo recetas que no tienen imagen en uploads/
 *   recetas_todas       — todas las recetas (respeta campo reemplazar)
 *   pasos_sin_imagen    — pasos que no tienen imagen en uploads/pasos/
 *   receta_id           — una receta concreta + todos sus pasos
 * ─────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);
set_time_limit(900);   // 15 min para lotes grandes

// ── Seguridad: solo admin autenticado ────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$esAdmin = !empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'admin';

// GET → mostrar formulario de control (sin procesamiento)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$esAdmin) {
        http_response_code(403);
        exit('Acceso prohibido. Inicia sesión como administrador.');
    }
    // continúa al HTML del formulario más abajo
    $mostrarFormulario = true;
} else {
    // POST → ejecutar proceso
    if (!$esAdmin) {
        http_response_code(403);
        exit('Acceso prohibido.');
    }
    $tokenPost    = $_POST['csrf_token']    ?? '';
    $tokenSession = $_SESSION['csrf_token'] ?? '';
    if (!hash_equals((string)$tokenSession, (string)$tokenPost)) {
        http_response_code(403);
        exit('Token CSRF inválido.');
    }
    $mostrarFormulario = false;
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(0);

// ── Cargar configuración del proyecto ────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';

$UPLOAD_DIR       = UPLOAD_PATH;          // uploads/
$UPLOAD_PASOS_DIR = defined('UPLOAD_PASOS_PATH') ? UPLOAD_PASOS_PATH : $UPLOAD_DIR . 'pasos/';
$UPLOAD_URL_BASE  = UPLOAD_URL;
$BASE             = BASE_URL;

// Crear directorio de pasos si no existe
if (!is_dir($UPLOAD_PASOS_DIR)) {
    @mkdir($UPLOAD_PASOS_DIR, 0775, true);
}

// ── PDO del proyecto ─────────────────────────────────────────────────
$pdo = Conexion::getInstancia()->getPDO();

// ── Parámetros del formulario ────────────────────────────────────────
$MODO       = $_POST['modo']       ?? 'recetas_sin_imagen';
$REEMPLAZAR = isset($_POST['reemplazar']);
$FILTRO_ID  = (int)($_POST['receta_id'] ?? 0);
$PAUSA_MS   = 400_000; // 0.4 s entre descargas
$UA         = 'GastroDigital/3.0 (imagenes-recetas; contact: GastroDigital73@gmail.com)';

// ── Mapa de países ────────────────────────────────────────────────────
const PAISES_EN = [
    'Alemania'             => 'German',
    'Argentina'            => 'Argentine',
    'Australia'            => 'Australian',
    'Austria'              => 'Austrian',
    'Bélgica'              => 'Belgian',
    'Bolivia'              => 'Bolivian',
    'Brasil'               => 'Brazilian',
    'Canadá'               => 'Canadian',
    'Chile'                => 'Chilean',
    'China'                => 'Chinese',
    'Colombia'             => 'Colombian',
    'Corea del Norte'      => 'Korean',
    'Corea del Sur'        => 'Korean',
    'Costa Rica'           => 'Costa Rican',
    'Cuba'                 => 'Cuban',
    'Dinamarca'            => 'Danish',
    'Ecuador'              => 'Ecuadorian',
    'Egipto'               => 'Egyptian',
    'España'               => 'Spanish',
    'Estados Unidos'       => 'American',
    'Etiopía'              => 'Ethiopian',
    'Filipinas'            => 'Filipino',
    'Finlandia'            => 'Finnish',
    'Francia'              => 'French',
    'Grecia'               => 'Greek',
    'Guatemala'            => 'Guatemalan',
    'Honduras'             => 'Honduran',
    'Hungría'              => 'Hungarian',
    'India'                => 'Indian',
    'Indonesia'            => 'Indonesian',
    'Irán'                 => 'Iranian',
    'Irlanda'              => 'Irish',
    'Israel'               => 'Israeli',
    'Italia'               => 'Italian',
    'Japón'                => 'Japanese',
    'Jordania'             => 'Jordanian',
    'Kenia'                => 'Kenyan',
    'Líbano'               => 'Lebanese',
    'Marruecos'            => 'Moroccan',
    'México'               => 'Mexican',
    'Nicaragua'            => 'Nicaraguan',
    'Nigeria'              => 'Nigerian',
    'Noruega'              => 'Norwegian',
    'Países Bajos'         => 'Dutch',
    'Pakistán'             => 'Pakistani',
    'Panamá'               => 'Panamanian',
    'Paraguay'             => 'Paraguayan',
    'Perú'                 => 'Peruvian',
    'Polonia'              => 'Polish',
    'Portugal'             => 'Portuguese',
    'Puerto Rico'          => 'Puerto Rican',
    'Reino Unido'          => 'British',
    'República Dominicana' => 'Dominican',
    'Rusia'                => 'Russian',
    'Siria'                => 'Syrian',
    'Sudáfrica'            => 'South African',
    'Suecia'               => 'Swedish',
    'Suiza'                => 'Swiss',
    'Tailandia'            => 'Thai',
    'Turquía'              => 'Turkish',
    'Ucrania'              => 'Ukrainian',
    'Uruguay'              => 'Uruguayan',
    'Venezuela'            => 'Venezuelan',
    'Vietnam'              => 'Vietnamese',
];

// ════════════════════════════════════════════════════════════════════
// FORMULARIO DE CONTROL (GET)
// ════════════════════════════════════════════════════════════════════
if ($mostrarFormulario):

// Contar recetas para mostrar stats en el formulario
$statsRecetasSinImg = (int)$pdo->query(
    "SELECT COUNT(*) FROM recetas WHERE imagen IS NULL OR imagen = ''"
)->fetchColumn();

$statsRecetasTotales = (int)$pdo->query("SELECT COUNT(*) FROM recetas")->fetchColumn();

// Obtener lista de recetas para el selector
$listaRecetas = $pdo->query(
    "SELECT r.id_receta, r.titulo, r.imagen, p.nombre AS pais
     FROM recetas r LEFT JOIN paises p ON p.id_pais = r.id_pais
     ORDER BY r.titulo ASC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GastroDigital — Herramienta de imágenes</title>
<link href="<?= $BASE ?>/assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= $BASE ?>/assets/vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= $BASE ?>/styles/main.css">
<style>
.stat-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.25rem 1.5rem;text-align:center}
.stat-num{font-size:2.2rem;font-weight:800;color:var(--primary);display:block;line-height:1}
.stat-lbl{font-size:.78rem;font-weight:600;color:var(--text-m);margin-top:.25rem;display:block}
.modo-card{cursor:pointer;border:2px solid var(--border);border-radius:14px;padding:1rem 1.25rem;
           transition:border-color .18s,background .18s;background:#fff}
.modo-card:has(input:checked){border-color:var(--primary);background:linear-gradient(135deg,#fff8f0,#fdf0e0)}
.atribucion{background:linear-gradient(135deg,#fffbf0,#fff8e6);border:1px solid #f4d485;
            border-radius:12px;padding:.85rem 1rem;font-size:.82rem;color:#7a5c0a}
</style>
</head>
<body class="app-layout">
<?php include __DIR__ . '/assets/navbar.php'; ?>
<?php
$pageTitle = 'Herramienta de imágenes';
$topbarBadge = 'Admin';
$topbarBreadcrumb = [
    ['label' => 'Admin', 'href' => $BASE . '/admin'],
    ['label' => 'Herramienta de imágenes'],
];
include __DIR__ . '/assets/topbar.php';
?>
<main class="pb-5">
<div class="container-fluid px-3 mt-3" style="max-width:860px">

    <div class="mb-4">
        <h2 class="fw-800 mb-1">
            <i class="bi bi-images me-2" style="color:var(--primary)"></i>
            Herramienta de imágenes
        </h2>
        <p class="text-muted mb-0">
            Descarga automática desde
            <strong>Wikimedia Commons</strong> — imágenes libres de derechos.
        </p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <span class="stat-num"><?= $statsRecetasTotales ?></span>
                <span class="stat-lbl">Recetas totales</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <span class="stat-num" style="color:<?= $statsRecetasSinImg > 0 ? 'var(--accent)' : '#27ae60' ?>">
                    <?= $statsRecetasSinImg ?>
                </span>
                <span class="stat-lbl">Recetas sin imagen</span>
            </div>
        </div>
    </div>

    <!-- Atribución -->
    <div class="atribucion mb-4">
        <i class="bi bi-info-circle-fill me-1"></i>
        <strong>Fuente:</strong> Wikimedia Commons — todas las imágenes descargadas son de dominio público
        o están bajo licencia Creative Commons. No se requiere clave API.
        GastroDigital no almacena ni redistribuye las imágenes originales: las sirve
        desde su propio servidor como miniaturas para uso educativo/personal.
    </div>

    <!-- Formulario -->
    <form method="POST" id="formDescarga">
        <?php csrfField(); ?>

        <div class="card panel-card mb-4">
            <div class="card-header py-3" style="background:var(--surface-3);border-bottom:1px solid var(--border)">
                <h5 class="mb-0 fw-800">
                    <i class="bi bi-sliders me-2" style="color:var(--primary)"></i>
                    Configurar descarga
                </h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">

                <!-- Modos -->
                <div>
                    <label class="form-label fw-700 mb-2">Modo de descarga</label>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="modo-card d-flex align-items-start gap-2 w-100">
                                <input type="radio" name="modo" value="recetas_sin_imagen" class="mt-1" checked>
                                <div>
                                    <div class="fw-700 small">Recetas sin imagen</div>
                                    <div class="text-muted" style="font-size:.78rem">
                                        Solo las <?= $statsRecetasSinImg ?> recetas que no tienen fotografía
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="modo-card d-flex align-items-start gap-2 w-100">
                                <input type="radio" name="modo" value="recetas_todas" class="mt-1">
                                <div>
                                    <div class="fw-700 small">Todas las recetas</div>
                                    <div class="text-muted" style="font-size:.78rem">
                                        Las <?= $statsRecetasTotales ?> recetas (respeta «no reemplazar»)
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="modo-card d-flex align-items-start gap-2 w-100">
                                <input type="radio" name="modo" value="receta_id" class="mt-1" id="modoRecetaId">
                                <div class="flex-grow-1">
                                    <div class="fw-700 small">Receta específica</div>
                                    <div class="text-muted" style="font-size:.78rem mb-1">
                                        Imagen principal + todos sus pasos
                                    </div>
                                    <select name="receta_id" class="form-select form-select-sm mt-1"
                                            id="selectRecetaId">
                                        <option value="0">— Seleccionar receta —</option>
                                        <?php foreach ($listaRecetas as $r): ?>
                                        <option value="<?= $r['id_receta'] ?>">
                                            <?= htmlspecialchars($r['titulo']) ?>
                                            <?= $r['pais'] ? '(' . htmlspecialchars($r['pais']) . ')' : '' ?>
                                            <?= $r['imagen'] ? '✅' : '❌' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Opción reemplazar -->
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="reemplazar" id="chkReemplazar">
                    <label class="form-check-label small" for="chkReemplazar">
                        <strong>Reemplazar imágenes existentes</strong>
                        <span class="text-muted">(por defecto se conservan las actuales)</span>
                    </label>
                </div>

            </div>
        </div>

        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary px-4 fw-700" id="btnIniciar">
                <i class="bi bi-cloud-download me-2"></i>Iniciar descarga
            </button>
            <a href="<?= $BASE ?>/admin" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver al admin
            </a>
        </div>
    </form>

</div>
</main>
<script>
// Activar el radio de receta específica al seleccionar el combo
document.getElementById('selectRecetaId').addEventListener('change', function () {
    if (this.value !== '0') {
        document.getElementById('modoRecetaId').checked = true;
    }
});
document.getElementById('formDescarga').addEventListener('submit', function () {
    const btn = document.getElementById('btnIniciar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Descargando…';
});
</script>
<?php include __DIR__ . '/assets/fooder.php'; ?>
</body>
</html>
<?php

// Fin del formulario GET — no ejecutar el proceso
return;

endif; // $mostrarFormulario

// ════════════════════════════════════════════════════════════════════
// PROCESO DE DESCARGA (POST)
// ════════════════════════════════════════════════════════════════════

// ── Funciones ────────────────────────────────────────────────────────

/**
 * Construye términos de búsqueda en orden de prioridad.
 *
 * Para imágenes de RECETA: busca el plato + país.
 * Para imágenes de PASO:   busca el plato + país primero (garantiza relevancia),
 *                           luego añade variantes con el ingrediente/técnica del paso.
 */
function gdBuildTerms(string $titulo, string $paisEs, string $contexto = ''): array
{
    $terms  = [];
    $t      = trim($titulo);
    $paisEn = PAISES_EN[$paisEs] ?? '';

    if ($contexto !== '') {
        // ── ESTRATEGIA PARA PASOS ─────────────────────────────────────────
        // Prioridad 1: nombre del plato + país (garantiza que sea la receta correcta)
        // Prioridad 2: variantes con ingrediente/técnica principal del paso

        // Extraer el ingrediente o técnica más importante del paso
        $keyword = extraerKeywordPaso($contexto);

        if ($paisEn) {
            $terms[] = "$t $paisEn food";           // más relevante: plato + país
            $terms[] = "$t $paisEn dish";
            if ($keyword) {
                $terms[] = "$t $keyword $paisEn";   // plato + ingrediente + país
            }
            $terms[] = "$t $paisEn";
        }
        if ($keyword) {
            $terms[] = "$t $keyword food";          // plato + ingrediente sin país
        }
        $terms[] = "$t food";
        $terms[] = "$t dish";
        $terms[] = $t;
        // Solo como último recurso: la técnica sola
        if ($keyword) {
            $terms[] = "$keyword cooking";
        }
    } else {
        // ── ESTRATEGIA PARA RECETA PRINCIPAL ─────────────────────────────
        if ($paisEn) {
            $terms[] = "$t $paisEn food";
            $terms[] = "$t $paisEn dish";
            $terms[] = "$t $paisEn";
            $terms[] = "$paisEn $t";
        }
        if ($paisEs && $paisEs !== $paisEn) {
            $terms[] = "$t $paisEs food";
        }
        $terms[] = "$t dish";
        $terms[] = "$t food";
        $terms[] = "$t recipe";
        $terms[] = $t;
    }

    return array_unique($terms);
}

/**
 * Extrae el ingrediente o técnica culinaria más importante del texto del paso.
 * Devuelve una palabra clave en inglés cuando reconoce el término, o vacío.
 */
function extraerKeywordPaso(string $texto): string
{
    $lower = mb_strtolower($texto, 'UTF-8');

    // Mapa de términos ES → EN ordenado de más específico a más genérico
    $mapa = [
        // Ingredientes comunes
        'arroz'        => 'rice',
        'pasta'        => 'pasta',
        'cebolla'      => 'onion',
        'ajo'          => 'garlic',
        'tomate'       => 'tomato',
        'pollo'        => 'chicken',
        'carne'        => 'meat',
        'pescado'      => 'fish',
        'camarón'      => 'shrimp',
        'camaron'      => 'shrimp',
        'huevo'        => 'egg',
        'queso'        => 'cheese',
        'leche'        => 'milk',
        'harina'       => 'flour',
        'mantequilla'  => 'butter',
        'aceite'       => 'oil',
        'caldo'        => 'broth',
        'crema'        => 'cream',
        'limón'        => 'lemon',
        'limon'        => 'lemon',
        'fideo'        => 'noodles',
        'fideos'       => 'noodles',
        'verdura'      => 'vegetables',
        'especia'      => 'spices',
        'salsa'        => 'sauce',
        // Técnicas
        'fríe'         => 'frying',
        'frie'         => 'frying',
        'hornea'       => 'baking',
        'asa'          => 'grilling',
        'hierve'       => 'boiling',
        'sofríe'       => 'sauteing',
        'sofrie'       => 'sauteing',
        'mezcla'       => 'mixing',
        'corta'        => 'chopping',
        'marina'       => 'marinating',
    ];

    foreach ($mapa as $es => $en) {
        if (mb_strpos($lower, $es) !== false) {
            return $en;
        }
    }

    return '';
}

/**
 * Busca una imagen en Wikimedia Commons.
 * Devuelve ['url', 'mime', 'titulo'] o null.
 */
function gdBuscarWikimedia(array $terminos, string $ua): ?array
{
    foreach ($terminos as $busqueda) {
        $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
            'action'        => 'query',
            'generator'     => 'search',
            'gsrsearch'     => $busqueda,
            'gsrnamespace'  => 6,
            'gsrlimit'      => 10,
            'prop'          => 'imageinfo',
            'iiprop'        => 'url|mime|size',
            'iiurlwidth'    => 900,
            'format'        => 'json',
            'formatversion' => 2,
        ]);

        $ctx  = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: $ua\r\nAccept: application/json\r\n",
            'timeout' => 25,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if (!$resp) continue;

        $data = json_decode($resp, true);
        if (empty($data['query']['pages'])) continue;

        foreach ($data['query']['pages'] as $page) {
            if (empty($page['imageinfo'][0])) continue;
            $info = $page['imageinfo'][0];
            $mime = $info['mime'] ?? '';
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) continue;
            $w = $info['width']  ?? 0;
            $h = $info['height'] ?? 0;
            if ($w < 200 || $h < 150) continue;
            $imgUrl = $info['thumburl'] ?? $info['url'] ?? '';
            if (!$imgUrl) continue;
            return ['url' => $imgUrl, 'mime' => $mime, 'titulo' => $page['title'] ?? ''];
        }
    }
    return null;
}

/**
 * Descarga y valida un archivo de imagen.
 */
function gdDescargar(string $url, string $destino, string $ua): bool
{
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => "User-Agent: $ua\r\nAccept: image/*\r\n",
        'timeout' => 40,
    ]]);
    $contenido = @file_get_contents($url, false, $ctx);
    if ($contenido === false || strlen($contenido) < 2000) return false;

    $tmp = tempnam(sys_get_temp_dir(), 'gd_img_');
    file_put_contents($tmp, $contenido);
    $valid = @getimagesize($tmp) !== false;
    unlink($tmp);
    if (!$valid) return false;

    return file_put_contents($destino, $contenido) !== false;
}

function gdMimeExt(string $mime): string
{
    return match($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
}

function gdSlug(string $texto, int $max = 80): string
{
    $s = mb_strtolower(trim($texto), 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', trim($s, '-'));
    return mb_substr($s, 0, $max, 'UTF-8');
}

// ── Obtener recetas/pasos según modo ────────────────────────────────

$filas = [];
$tipo  = 'receta'; // 'receta' | 'paso'

switch ($MODO) {
    case 'recetas_todas':
        $filas = $pdo->query("
            SELECT r.id_receta, r.titulo, r.imagen,
                   p.nombre AS pais_nombre
            FROM recetas r
            LEFT JOIN paises p ON p.id_pais = r.id_pais
            ORDER BY r.id_receta ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $tipo = 'receta';
        break;

    case 'pasos_sin_imagen':
        $filas = $pdo->query("
            SELECT ps.id_pasos, ps.id_receta, ps.numero, ps.descripcion, ps.imagen,
                   r.titulo AS receta_titulo,
                   p.nombre AS pais_nombre
            FROM pasos ps
            JOIN recetas r ON r.id_receta = ps.id_receta
            LEFT JOIN paises p ON p.id_pais = r.id_pais
            WHERE ps.imagen IS NULL OR ps.imagen = ''
            ORDER BY ps.id_receta ASC, ps.numero ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $tipo = 'paso';
        break;

    case 'receta_id':
        if ($FILTRO_ID <= 0) {
            http_response_code(400);
            exit('ID de receta inválido.');
        }
        // Receta principal
        $stmt = $pdo->prepare("
            SELECT r.id_receta, r.titulo, r.imagen,
                   p.nombre AS pais_nombre
            FROM recetas r
            LEFT JOIN paises p ON p.id_pais = r.id_pais
            WHERE r.id_receta = ?
        ");
        $stmt->execute([$FILTRO_ID]);
        $recetaRow = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pasos de esa receta
        $stmtP = $pdo->prepare("
            SELECT ps.id_pasos, ps.id_receta, ps.numero, ps.descripcion, ps.imagen,
                   r.titulo AS receta_titulo,
                   p.nombre AS pais_nombre
            FROM pasos ps
            JOIN recetas r ON r.id_receta = ps.id_receta
            LEFT JOIN paises p ON p.id_pais = r.id_pais
            WHERE ps.id_receta = ?
            ORDER BY ps.numero ASC
        ");
        $stmtP->execute([$FILTRO_ID]);
        $pasosRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Procesar receta + pasos juntos (usaremos ambos arrays separados)
        $filas       = $recetaRow ? [$recetaRow] : [];
        $filasPasos  = $pasosRows;
        $tipo        = 'mixto'; // procesaremos receta y pasos
        break;

    case 'recetas_sin_imagen':
    default:
        $filas = $pdo->query("
            SELECT r.id_receta, r.titulo, r.imagen,
                   p.nombre AS pais_nombre
            FROM recetas r
            LEFT JOIN paises p ON p.id_pais = r.id_pais
            WHERE r.imagen IS NULL OR r.imagen = ''
            ORDER BY r.id_receta ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $tipo = 'receta';
        break;
}

// Para modo mixto, unificar en dos pasadas
if ($tipo === 'mixto') {
    $filasPasosParaHtml = $filasPasos ?? [];
} else {
    $filasPasosParaHtml = ($tipo === 'paso') ? $filas : [];
    $filas              = ($tipo === 'paso') ? [] : $filas;
}

$totalRecetas = count($filas);
$totalPasos   = count($filasPasosParaHtml);
$totalItems   = $totalRecetas + $totalPasos;

// ── Función para procesar y renderizar un ítem ───────────────────────

/**
 * Descarga una imagen, la guarda en $dir, actualiza MySQL.
 * Devuelve un array de estado para renderizar.
 */
function gdProcesarItem(
    string $labelId,
    string $titulo,
    string $imgActual,
    string $paisEs,
    string $dir,
    string $ua,
    bool   $reemplazar,
    string $contexto = ''   // descripción del paso (solo para pasos)
): array {
    $rutaActual  = $imgActual ? $dir . basename($imgActual) : '';
    $tieneImagen = $rutaActual && file_exists($rutaActual);

    if ($tieneImagen && !$reemplazar) {
        return ['estado' => 'omitida', 'nombre' => basename($imgActual), 'fuente' => ''];
    }

    $terminos = gdBuildTerms($titulo, $paisEs, $contexto);
    $imagen   = gdBuscarWikimedia($terminos, $ua);

    if (!$imagen) {
        return ['estado' => 'sin_imagen', 'nombre' => '', 'fuente' => '', 'terminos' => $terminos];
    }

    $paisEn  = PAISES_EN[$paisEs] ?? $paisEs;
    $ext     = gdMimeExt($imagen['mime']);
    $slug    = gdSlug($titulo . ($paisEn ? '-' . $paisEn : '')) . '.' . $ext;
    $destino = $dir . $slug;

    // Si ya existe con ese nombre, añadir prefijo con id
    if (file_exists($destino) && !$reemplazar) {
        $slug    = 'rec' . $labelId . '-' . $slug;
        $destino = $dir . $slug;
    }

    $ok = gdDescargar($imagen['url'], $destino, $ua);
    if (!$ok) {
        return ['estado' => 'error_descarga', 'nombre' => '', 'fuente' => $imagen['url'], 'terminos' => $terminos];
    }

    return [
        'estado'   => 'ok',
        'nombre'   => $slug,
        'fuente'   => $imagen['titulo'],
        'terminos' => $terminos,
    ];
}

// ── Salida HTML del proceso ──────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
ob_implicit_flush(true);
ob_end_flush();

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GastroDigital — Descargando imágenes</title>
<link href="<?= $BASE ?>/assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= $BASE ?>/assets/vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= $BASE ?>/styles/main.css">
<style>
.item{border-radius:12px;border:1px solid var(--border);background:#fff;padding:12px 16px;
      margin-bottom:8px;display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center}
.item-num{font-size:.78rem;font-weight:700;color:var(--text-m);min-width:52px}
.item-body .titulo{font-weight:700;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-body .detalle{font-size:.78rem;color:#666;margin-top:2px}
.item-body .busqueda{font-size:.72rem;color:#aaa;font-style:italic}
.item img{width:100px;height:66px;object-fit:cover;border-radius:8px;flex-shrink:0}
.ok  {border-left:4px solid #27ae60;background:#f0faf4}
.err {border-left:4px solid #e74c3c;background:#fdf5f5}
.warn{border-left:4px solid #f39c12;background:#fffbf0}
.skip{border-left:4px solid var(--border);opacity:.65}
.paso-header{background:var(--surface-3);border-radius:10px;padding:8px 14px;margin:16px 0 8px;
             font-weight:700;font-size:.85rem;color:var(--text)}
.resumen{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
.res-card{flex:1;min-width:120px;padding:14px;border-radius:12px;text-align:center}
.res-ok  {background:#eafaf1;color:#1e8449}
.res-err {background:#fdedec;color:#c0392b}
.res-skip{background:#f2f3f4;color:#7f8c8d}
.progress-sticky{position:sticky;top:0;z-index:50;background:var(--cream);padding:10px 0;
                  border-bottom:1px solid var(--border)}
</style>
</head>
<body class="app-layout">
<?php include __DIR__ . '/assets/navbar.php'; ?>

<main class="pb-5">
<div class="container-fluid px-3 mt-3" style="max-width:900px">

    <div class="mb-3 d-flex align-items-center justify-content-between">
        <h2 class="fw-800 mb-0">
            <i class="bi bi-cloud-download me-2" style="color:var(--primary)"></i>
            Descargando imágenes
        </h2>
        <a href="<?= $BASE ?>/descargar-imagenes-recetas.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <!-- Barra de progreso sticky -->
    <div class="progress-sticky mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-700 text-muted" id="progTxt">Iniciando…</span>
            <span class="small fw-700 text-muted" id="progPct">0%</span>
        </div>
        <div class="progress" style="height:8px;border-radius:6px">
            <div class="progress-bar" id="progBar" style="width:0%;
                 background:linear-gradient(90deg,var(--primary),var(--secondary))"
                 role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

<?php

$okCount   = 0;
$errCount  = 0;
$omitCount = 0;
$processed = 0;

// ────────────────────────────────────────────────────────────────────
// PROCESAR RECETAS
// ────────────────────────────────────────────────────────────────────
if ($totalRecetas > 0): ?>
    <h5 class="fw-800 mb-3">
        <i class="bi bi-journal-richtext me-2" style="color:var(--primary)"></i>
        Imagen principal de recetas
    </h5>
<?php
    foreach ($filas as $rec):
        $id        = (int)$rec['id_receta'];
        $titulo    = trim($rec['titulo']);
        $paisEs    = trim($rec['pais_nombre'] ?? '');
        $imgActual = trim($rec['imagen'] ?? '');

        $res = gdProcesarItem(
            (string)$id, $titulo, $imgActual, $paisEs,
            $UPLOAD_DIR, $UA, $REEMPLAZAR
        );

        // Actualizar BD si hubo descarga exitosa
        if ($res['estado'] === 'ok') {
            $pdo->prepare("UPDATE recetas SET imagen = ? WHERE id_receta = ?")
                ->execute([$res['nombre'], $id]);
            $okCount++;
        } elseif ($res['estado'] === 'omitida') {
            $omitCount++;
        } else {
            $errCount++;
        }
        $processed++;

        // Renderizar ítem
        $cls = match($res['estado']) {
            'ok'      => 'ok',
            'omitida' => 'skip',
            default   => 'err',
        };
?>
        <div class="item <?= $cls ?>">
            <span class="item-num">#<?= $id ?></span>
            <div class="item-body">
                <div class="titulo"><?= htmlspecialchars($titulo) ?>
                    <?php if ($paisEs): ?>
                    <span class="text-muted fw-400" style="font-size:.78rem">
                        — <?= htmlspecialchars($paisEs) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="detalle">
                    <?php if ($res['estado'] === 'ok'): ?>
                        <span class="badge" style="background:#27ae60;color:#fff">✅ Descargada</span>
                        <code><?= htmlspecialchars($res['nombre']) ?></code>
                        <span class="text-muted"> · <?= htmlspecialchars(mb_substr($res['fuente'], 0, 60)) ?></span>
                    <?php elseif ($res['estado'] === 'omitida'): ?>
                        <span class="badge bg-secondary">Omitida</span>
                        ya tiene imagen: <code><?= htmlspecialchars($res['nombre']) ?></code>
                    <?php elseif ($res['estado'] === 'sin_imagen'): ?>
                        <span class="badge bg-danger">Sin resultado</span>
                        No encontrada en Wikimedia Commons.
                        <div class="busqueda">
                            Intentado: <?= htmlspecialchars(implode(' | ', array_slice($res['terminos'] ?? [], 0, 3))) ?>
                        </div>
                    <?php elseif ($res['estado'] === 'error_descarga'): ?>
                        <span class="badge bg-danger">Error descarga</span>
                        La imagen se encontró pero no se pudo descargar.
                    <?php else: ?>
                        <span class="badge bg-danger">Error BD</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($res['estado'] === 'ok'): ?>
            <img src="<?= $UPLOAD_URL_BASE . rawurlencode($res['nombre']) ?>"
                 alt="<?= htmlspecialchars($titulo) ?>"
                 onerror="this.style.display='none'">
            <?php elseif ($res['estado'] === 'omitida' && $imgActual): ?>
            <img src="<?= $UPLOAD_URL_BASE . rawurlencode(basename($imgActual)) ?>"
                 alt="" onerror="this.style.display='none'">
            <?php else: ?>
            <span></span>
            <?php endif; ?>
        </div>
<?php
        // Actualizar progreso via JS
        $pct = $totalItems > 0 ? round(($processed / $totalItems) * 100) : 0;
        echo "<script>
            document.getElementById('progBar').style.width='{$pct}%';
            document.getElementById('progBar').setAttribute('aria-valuenow',{$pct});
            document.getElementById('progPct').textContent='{$pct}%';
            document.getElementById('progTxt').textContent='Receta {$processed} / {$totalItems}: " . addslashes(htmlspecialchars($titulo)) . "';
        </script>";
        ob_flush(); flush();
        usleep($PAUSA_MS);
    endforeach;
endif;

// ────────────────────────────────────────────────────────────────────
// PROCESAR PASOS
// ────────────────────────────────────────────────────────────────────
if ($totalPasos > 0):
    $recetaAnterior = null;
?>
    <h5 class="fw-800 mb-3 mt-4">
        <i class="bi bi-list-ol me-2" style="color:var(--secondary)"></i>
        Imágenes de pasos
    </h5>
<?php
    foreach ($filasPasosParaHtml as $paso):
        $idPaso    = (int)$paso['id_pasos'];
        $idReceta  = (int)$paso['id_receta'];
        $numPaso   = (int)$paso['numero'];
        $desc      = trim($paso['descripcion']);
        $tituloR   = trim($paso['receta_titulo']);
        $paisEs    = trim($paso['pais_nombre'] ?? '');
        $imgActual = trim($paso['imagen'] ?? '');

        // Mostrar cabecera de receta cuando cambia
        if ($recetaAnterior !== $idReceta):
            $recetaAnterior = $idReceta;
?>
            <div class="paso-header">
                <i class="bi bi-journal-richtext me-2"></i>
                <?= htmlspecialchars($tituloR) ?> (receta #<?= $idReceta ?>)
            </div>
<?php
        endif;

        // Contexto para búsqueda: descripción completa del paso
        // gdBuildTerms extrae solo el ingrediente/técnica relevante
        $ctxBusqueda = $desc;

        $res = gdProcesarItem(
            "p{$idPaso}", $tituloR, $imgActual, $paisEs,
            $UPLOAD_PASOS_DIR, $UA, $REEMPLAZAR, $ctxBusqueda
        );

        if ($res['estado'] === 'ok') {
            $pdo->prepare("UPDATE pasos SET imagen = ? WHERE id_pasos = ?")
                ->execute([$res['nombre'], $idPaso]);
            $okCount++;
        } elseif ($res['estado'] === 'omitida') {
            $omitCount++;
        } else {
            $errCount++;
        }
        $processed++;

        $cls = match($res['estado']) {
            'ok'      => 'ok',
            'omitida' => 'skip',
            default   => 'err',
        };
?>
        <div class="item <?= $cls ?> ms-3" style="border-left-width:4px">
            <span class="item-num">P.<?= $numPaso ?></span>
            <div class="item-body">
                <div class="titulo" style="font-size:.82rem">
                    Paso <?= $numPaso ?>: <?= htmlspecialchars(mb_substr($desc, 0, 80)) ?>…
                </div>
                <div class="detalle">
                    <?php if ($res['estado'] === 'ok'): ?>
                        <span class="badge" style="background:#27ae60;color:#fff">✅ Descargada</span>
                        <code><?= htmlspecialchars($res['nombre']) ?></code>
                    <?php elseif ($res['estado'] === 'omitida'): ?>
                        <span class="badge bg-secondary">Omitida</span>
                        ya tiene imagen
                    <?php elseif ($res['estado'] === 'sin_imagen'): ?>
                        <span class="badge bg-danger">Sin resultado</span>
                        No encontrada.
                    <?php else: ?>
                        <span class="badge bg-danger">Error</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($res['estado'] === 'ok'): ?>
            <img src="<?= $UPLOAD_URL_BASE . 'pasos/' . rawurlencode($res['nombre']) ?>"
                 alt="" onerror="this.style.display='none'">
            <?php else: ?>
            <span></span>
            <?php endif; ?>
        </div>
<?php
        $pct = $totalItems > 0 ? round(($processed / $totalItems) * 100) : 0;
        echo "<script>
            document.getElementById('progBar').style.width='{$pct}%';
            document.getElementById('progBar').setAttribute('aria-valuenow',{$pct});
            document.getElementById('progPct').textContent='{$pct}%';
            document.getElementById('progTxt').textContent='Paso {$processed} / {$totalItems}';
        </script>";
        ob_flush(); flush();
        usleep($PAUSA_MS);
    endforeach;
endif;
?>

<!-- Resumen final -->
<script>
document.getElementById('progBar').style.width='100%';
document.getElementById('progPct').textContent='100%';
document.getElementById('progTxt').textContent='¡Proceso completado!';
</script>

<div class="resumen mt-4">
    <div class="res-card res-ok">
        <span style="font-size:2rem;font-weight:800;display:block"><?= $okCount ?></span>
        <span style="font-size:.78rem;font-weight:600">✅ Descargadas</span>
    </div>
    <div class="res-card res-skip">
        <span style="font-size:2rem;font-weight:800;display:block"><?= $omitCount ?></span>
        <span style="font-size:.78rem;font-weight:600">⏭️ Omitidas</span>
    </div>
    <div class="res-card res-err">
        <span style="font-size:2rem;font-weight:800;display:block"><?= $errCount ?></span>
        <span style="font-size:.78rem;font-weight:600">❌ Errores</span>
    </div>
    <div class="res-card" style="background:var(--surface-2);color:var(--text)">
        <span style="font-size:2rem;font-weight:800;display:block"><?= $processed ?></span>
        <span style="font-size:.78rem;font-weight:600">📋 Total procesados</span>
    </div>
</div>

<div class="mt-4 d-flex gap-3">
    <a href="<?= $BASE ?>/descargar-imagenes-recetas.php"
       class="btn btn-primary">
        <i class="bi bi-arrow-repeat me-2"></i>Ejecutar otra descarga
    </a>
    <a href="<?= $BASE ?>/admin" class="btn btn-outline-secondary">
        <i class="bi bi-house me-2"></i>Volver al admin
    </a>
</div>

</div>
</main>
<?php include __DIR__ . '/assets/fooder.php'; ?>
</body>
</html>
