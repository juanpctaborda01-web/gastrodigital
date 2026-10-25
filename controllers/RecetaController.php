<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../models/categoria.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

// ── Acciones JSON (AJAX) ──────────────────────────────────────────────────────
$jsonAcciones = ['favorito', 'valorar', 'comentar', 'seguirChef', 'valorarChef', 'reportar', 'togglePublicada'];

if (in_array($accion, $jsonAcciones)) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']); exit();
    }
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Inicia sesión para realizar esta acción.']); exit();
    }

    // Verificar CSRF para acciones que modifican datos
    $tokenPost   = $_POST['csrf_token']    ?? '';
    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    if (!$tokenPost || !hash_equals($tokenSesion, $tokenPost)) {
        http_response_code(403);
        echo json_encode(['error' => 'Petición no válida']); exit();
    }

    $model  = new Receta();
    $userId = $_SESSION['usuario_id'];
    $rol    = $_SESSION['rol'];

    try {
    switch ($accion) {
        case 'favorito':
            $recetaId = (int)($_POST['receta_id'] ?? 0);
            if (!$recetaId) { echo json_encode(['error' => 'ID inválido']); exit(); }
            echo json_encode($model->toggleFavorito($userId, $recetaId));
            break;

        case 'valorar':
            $recetaId   = (int)($_POST['receta_id'] ?? 0);
            $puntuacion = (int)($_POST['puntuacion'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            if (!$recetaId || $puntuacion < 1 || $puntuacion > 5) {
                echo json_encode(['error' => 'Datos inválidos']); exit();
            }
            echo json_encode($model->valorar($userId, $recetaId, $puntuacion, $comentario));
            break;

        case 'comentar':
            $recetaId = (int)($_POST['receta_id'] ?? 0);
            $contenido = trim($_POST['contenido'] ?? '');
            if (!$recetaId) { echo json_encode(['error' => 'ID inválido']); exit(); }
            echo json_encode($model->comentar($userId, $recetaId, $contenido));
            break;

        case 'seguirChef':
            $chefId = (int)($_POST['chef_id'] ?? 0);
            if (!$chefId) { echo json_encode(['error' => 'ID inválido']); exit(); }
            echo json_encode($model->seguirChef($userId, $chefId));
            break;

        case 'valorarChef':
            $chefId = (int)($_POST['chef_id'] ?? 0);
            $puntuacion = (int)($_POST['puntuacion'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            if (!$chefId || $puntuacion < 1 || $puntuacion > 5) {
                echo json_encode(['error' => 'Datos inválidos']); exit();
            }
            echo json_encode($model->valorarChef($userId, $chefId, $puntuacion, $comentario));
            break;

        case 'reportar':
            $tipo = trim($_POST['tipo'] ?? '');
            $objetivoId = (int)($_POST['objetivo_id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            if (!$tipo || !$objetivoId) { echo json_encode(['error' => 'Datos inválidos']); exit(); }
            echo json_encode($model->reportar($userId, $tipo, $objetivoId, $motivo, $descripcion));
            break;

        case 'togglePublicada':
            requireRole(ROL_CHEF);
            $recetaId = (int)($_POST['receta_id'] ?? 0);
            if (!$recetaId) { echo json_encode(['error' => 'ID inválido']); exit(); }
            echo json_encode($model->togglePublicada($recetaId, $userId));
            break;

        default:
            echo json_encode(['error' => 'Acción desconocida']);
    }
    } catch (Throwable $e) {
        error_log('[RecetaController] Error en la acción ' . $accion . ': ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo completar la acción. Inténtalo de nuevo.']);
    }
    exit();
}

// ── Acción: eliminar receta (POST desde formulario — redirige tras eliminar) ─
if ($accion === 'eliminar') {
    requireLogin();
    verificarCsrf();
    $recetaId = (int)($_POST['receta_id'] ?? 0);
    $userId   = $_SESSION['usuario_id'];
    $rol      = $_SESSION['rol'];
    $model    = new Receta();
    if ($recetaId) {
        $res = $model->eliminar($recetaId, $userId, $rol);
        if (isset($res['exito'])) {
            $_SESSION['exito'] = 'Receta eliminada correctamente.';
        } else {
            $_SESSION['error'] = $res['error'] ?? 'No se pudo eliminar la receta.';
        }
    }
    redirect('mis-recetas');
}

// ── Acción: crear receta (POST desde formulario) ──────────────────────────────
if ($accion === 'crear') {
    requireRole(ROL_CHEF);
    verificarCsrf();

    $titulo = limpiarTexto($_POST['titulo']      ?? '', 150);
    $desc   = limpiarTexto($_POST['descripcion'] ?? '', 2000);
    $cat    = limpiarTexto($_POST['categoria']   ?? '', 80);
    $dif    = $_POST['dificultad'] ?? '';
    $tiempo = (int)($_POST['tiempo'] ?? 0);

    if (!$titulo || !$cat || !in_array($dif, ['Fácil','Media','Difícil']) || !$tiempo) {
        $_SESSION['error'] = 'Completa todos los campos obligatorios correctamente.';
        redirect('crear-receta');
    }

    // Upload seguro via seguridad.php
    $imagen = null;
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
        if ($imagen === null) {
            $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
            redirect('crear-receta');
        }
    }

    $model    = new Receta();
    $recetaId = $model->crear([
        'titulo'      => $titulo,
        'descripcion' => $desc,
        'tiempo'      => $tiempo,
        'porciones'   => (int)($_POST['porciones'] ?? 4),
        'dificultad'  => $dif,
        'categoria_id'=> null,
        'categoria'   => $cat,
        'imagen'      => $imagen,
        'calorias'    => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
        'publicada'   => isset($_POST['publicada']) ? (int)(bool)$_POST['publicada'] : 1,
    ], $_SESSION['usuario_id']);

    $ingredientes = [];
    foreach (($_POST['ing_nombre'] ?? []) as $i => $nombre) {
        if (trim($nombre)) {
            $ingredientes[] = [
                'nombre'   => $nombre,
                'cantidad' => $_POST['ing_cantidad'][$i] ?? '',
                'unidad'   => $_POST['ing_unidad'][$i]   ?? '',
            ];
        }
    }
    $model->guardarIngredientes($recetaId, $ingredientes);

    // Normalizar pasos con imagen y duración (chef)
    $pasosData = normalizarPasosChef($_POST, $_FILES, $recetaId, null);
    $model->guardarPasos($recetaId, $pasosData);

    $_SESSION['exito'] = isset($_POST['publicada']) && !$_POST['publicada']
        ? "Receta \"$titulo\" guardada como borrador."
        : "Receta \"$titulo\" publicada correctamente.";
    redirect('mis-recetas');
}

// ── Acción: editar receta (POST desde formulario) ────────────────────────────
if ($accion === 'editar') {
    requireRole(ROL_CHEF);
    verificarCsrf();

    $recetaId = (int)($_POST['receta_id'] ?? 0);
    if (!$recetaId) {
        $_SESSION['error'] = 'Receta no válida.';
        redirect('mis-recetas');
    }

    $model  = new Receta();
    $userId = $_SESSION['usuario_id'];

    // Verificar que la receta le pertenece al chef
    $receta = $model->obtenerParaEditar($recetaId, $userId);
    if (!$receta) {
        $_SESSION['error'] = 'No tienes permiso para editar esa receta.';
        redirect('mis-recetas');
    }

    $titulo = limpiarTexto($_POST['titulo']      ?? '', 150);
    $desc   = limpiarTexto($_POST['descripcion'] ?? '', 2000);
    $cat    = limpiarTexto($_POST['categoria']   ?? '', 80);
    $dif    = $_POST['dificultad'] ?? '';
    $tiempo = (int)($_POST['tiempo'] ?? 0);

    if (!$titulo || !$cat || !in_array($dif, ['Fácil','Media','Difícil']) || !$tiempo) {
        $_SESSION['error'] = 'Completa todos los campos obligatorios.';
        redirect("editar-receta?id=$recetaId");
    }

    // Upload de nueva imagen (opcional)
    $imagen = null;
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
        if ($imagen === null) {
            $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
            redirect("editar-receta?id=$recetaId");
        }
    }

    $model->actualizar($recetaId, [
        'titulo'      => $titulo,
        'descripcion' => $desc,
        'tiempo'      => $tiempo,
        'porciones'   => (int)($_POST['porciones'] ?? 4),
        'dificultad'  => $dif,
        'categoria'   => $cat,
        'calorias'    => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
        'imagen'      => $imagen,
        'chef_id'     => $userId,
        'publicada'   => isset($_POST['publicada']) ? (int)(bool)$_POST['publicada'] : null,
    ]);

    $ingredientes = [];
    foreach (($_POST['ing_nombre'] ?? []) as $i => $nombre) {
        if (trim($nombre)) {
            $ingredientes[] = [
                'nombre'   => $nombre,
                'cantidad' => $_POST['ing_cantidad'][$i] ?? '',
                'unidad'   => $_POST['ing_unidad'][$i]   ?? '',
            ];
        }
    }
    $model->guardarIngredientes($recetaId, $ingredientes);

    // Normalizar pasos con imagen y duración (chef editar)
    $pasosData = normalizarPasosChef($_POST, $_FILES, $recetaId, null);
    $model->guardarPasos($recetaId, $pasosData);

    $_SESSION['exito'] = "Receta \"$titulo\" actualizada correctamente.";
    redirect('mis-recetas');
}

// ── Helper: normalizar pasos del chef con imagen y duración ──────────────────
// Recibe $_POST y $_FILES, devuelve array listo para guardarPasos().
// Compatible con la versión anterior que solo enviaba $_POST['pasos'][].
if (!function_exists('normalizarPasosChef')) {
    function normalizarPasosChef(array $post, array $files, int $recetaId, ?int $chefId): array
    {
        // Nuevo formato: paso_desc[] + paso_duracion[] + paso_imagen[]
        $descs    = $post['paso_desc']         ?? [];
        $durs     = $post['paso_duracion']      ?? [];
        $actuales = $post['paso_imagen_actual'] ?? [];

        // Soporte retrocompatible: vistas chef antiguas envían pasos[] sin desc
        if (empty($descs) && !empty($post['pasos'])) {
            $descs    = $post['pasos'];
            $durs     = [];
            $actuales = [];
        }

        $imgNames  = $files['paso_imagen']['name']     ?? [];
        $imgTmps   = $files['paso_imagen']['tmp_name'] ?? [];
        $imgErrors = $files['paso_imagen']['error']    ?? [];
        $imgSizes  = $files['paso_imagen']['size']     ?? [];

        $result = [];
        foreach ($descs as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') continue;

            $imagen = null;
            $hayArchivo = isset($imgNames[$i]) && $imgNames[$i] !== ''
                       && ($imgErrors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

            if ($hayArchivo) {
                $fileSingle = [
                    'name'     => $imgNames[$i],
                    'tmp_name' => $imgTmps[$i],
                    'error'    => $imgErrors[$i],
                    'size'     => $imgSizes[$i],
                    'type'     => mime_content_type($imgTmps[$i]) ?: 'image/jpeg',
                ];
                $subido = procesarImagenUpload($fileSingle, UPLOAD_PASOS_PATH);
                if ($subido !== null) {
                    $imagen = $subido;
                }
            }

            if ($imagen === null) {
                $actual = trim($actuales[$i] ?? '');
                if ($actual !== '' && is_file(UPLOAD_PASOS_PATH . $actual)) {
                    $imagen = $actual;
                }
            }

            $dur = isset($durs[$i]) && (int)$durs[$i] > 0 ? (int)$durs[$i] : null;

            $result[] = [
                'descripcion' => $desc,
                'imagen'      => $imagen,
                'duracion'    => $dur,
            ];
        }
        return $result;
    }
}
