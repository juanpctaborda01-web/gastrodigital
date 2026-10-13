<?php
/**
 * AdminRecetaController.php
 * ─────────────────────────────────────────────────────────────
 * Controlador de gestión de recetas para el panel administrativo.
 * Cada acción verifica requireRole(ROL_ADMIN) antes de ejecutarse.
 *
 * Rutas registradas en RouterController:
 *   GET  admin/recetas          → listado
 *   GET  admin/recetas/nueva    → formulario crear
 *   POST admin/recetas/nueva    → procesar crear
 *   GET  admin/recetas/editar   → formulario editar  (?id=N)
 *   POST admin/recetas/editar   → procesar editar
 *   GET  admin/recetas/ver      → vista detalle      (?id=N)
 *   POST admin/recetas/eliminar → procesar eliminar
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../models/categoria.php';

class AdminRecetaController
{
    private Receta    $recetaModel;
    private Categoria $catModel;

    public function __construct()
    {
        $this->recetaModel = new Receta();
        $this->catModel    = new Categoria();
    }

    // ──────────────────────────────────────────────────────────
    // GET  admin/recetas — Listado con filtros y paginación
    // ──────────────────────────────────────────────────────────
    public function listado(): void
    {
        requireRole(ROL_ADMIN);

        $filtros = [
            'q'           => trim($_GET['q']           ?? ''),
            'id_categoria' => (int)($_GET['id_categoria'] ?? 0),
            'id_pais'      => (int)($_GET['id_pais']      ?? 0),
            'id_chef'      => (int)($_GET['id_chef']      ?? 0),
            'dificultad'   => trim($_GET['dificultad']    ?? ''),
            'publicada'    => $_GET['publicada']  ?? '',
            'destacada'    => $_GET['destacada']  ?? '',
        ];
        // Limpiar valores vacíos para que el modelo no filtre por ellos
        foreach ($filtros as $k => $v) {
            if ($v === 0 || $v === '') unset($filtros[$k]);
        }

        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $resultado = $this->recetaModel->listarAdmin($filtros, $pagina, 15);

        $categorias = $this->catModel->listarSimple();
        $chefs      = $this->recetaModel->listarChefs();
        // Países para el filtro — consulta directa reutilizando PDO
        $paises     = $this->listarPaisesSimple();

        $pageTitle = 'Administrar recetas';
        extract($resultado); // $recetas, $total, $paginas, $pagina

        include __DIR__ . '/../view/admin/recetas.php';
    }

    // ──────────────────────────────────────────────────────────
    // GET  admin/recetas/nueva — Formulario de creación
    // ──────────────────────────────────────────────────────────
    public function nueva(): void
    {
        requireRole(ROL_ADMIN);

        $pageTitle  = 'Nueva receta';
        $modoEditar = false;
        $receta     = null;
        $categorias = $this->catModel->listarSimple();
        $paises     = $this->listarPaisesSimple();
        $chefs      = $this->recetaModel->listarChefs();

        include __DIR__ . '/../view/admin/receta_form.php';
    }

    // ──────────────────────────────────────────────────────────
    // POST admin/recetas/nueva — Procesar creación
    // ──────────────────────────────────────────────────────────
    public function crearPost(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $errores = $this->validarFormulario($_POST, $_FILES);
        if ($errores) {
            $_SESSION['error'] = implode('<br>', $errores);
            redirect('admin/recetas/nueva');
        }

        // Imagen opcional
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
            if ($imagen === null) {
                $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                redirect('admin/recetas/nueva');
            }
        }

        try {
            $id = $this->recetaModel->crearAdmin([
                'titulo'             => limpiarTexto($_POST['titulo'],       150),
                'descripcion'        => limpiarTexto($_POST['descripcion'],  5000),
                'tiempo_preparacion' => (int)($_POST['tiempo_preparacion']   ?? 0),
                'porciones'          => (int)($_POST['porciones']            ?? 4),
                'dificultad'         => $this->dificultadValida($_POST['dificultad'] ?? ''),
                'id_categoria'       => (int)($_POST['id_categoria']         ?? 0) ?: null,
                'id_pais'            => (int)($_POST['id_pais']              ?? 0) ?: null,
                'id_chef'            => (int)($_POST['id_chef']              ?? 0) ?: null,
                'imagen'             => $imagen,
                'video_url'          => limpiarTexto($_POST['video_url']     ?? '', 255) ?: null,
                'calorias'           => (int)($_POST['calorias']             ?? 0) ?: null,
                'publicada'          => isset($_POST['publicada']) ? 1 : 0,
                'destacada'          => isset($_POST['destacada']) ? 1 : 0,
                'ingredientes'       => $this->normalizarIngredientes($_POST),
                'pasos'              => $this->normalizarPasos($_POST),
            ]);

            $_SESSION['exito'] = '✅ Receta "' . htmlspecialchars($_POST['titulo']) . '" creada correctamente (ID: ' . $id . ').';
            redirect('admin/recetas');

        } catch (\Throwable $e) {
            error_log('[AdminRecetaController::crearPost] ' . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar la receta. Inténtalo de nuevo.';
            redirect('admin/recetas/nueva');
        }
    }

    // ──────────────────────────────────────────────────────────
    // GET  admin/recetas/editar?id=N — Formulario de edición
    // ──────────────────────────────────────────────────────────
    public function editar(): void
    {
        requireRole(ROL_ADMIN);

        $id     = (int)($_GET['id'] ?? 0);
        $receta = $id ? $this->recetaModel->obtenerCompletaAdmin($id) : null;

        if (!$receta) {
            $_SESSION['error'] = 'Receta no encontrada.';
            redirect('admin/recetas');
        }

        $pageTitle  = 'Editar receta — ' . htmlspecialchars($receta['titulo']);
        $modoEditar = true;
        $categorias = $this->catModel->listarSimple();
        $paises     = $this->listarPaisesSimple();
        $chefs      = $this->recetaModel->listarChefs();

        include __DIR__ . '/../view/admin/receta_form.php';
    }

    // ──────────────────────────────────────────────────────────
    // POST admin/recetas/editar — Procesar edición
    // ──────────────────────────────────────────────────────────
    public function editarPost(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id = (int)($_POST['receta_id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'ID de receta inválido.';
            redirect('admin/recetas');
        }

        $errores = $this->validarFormulario($_POST, $_FILES);
        if ($errores) {
            $_SESSION['error'] = implode('<br>', $errores);
            redirect("admin/recetas/editar?id=$id");
        }

        // Imagen opcional — solo reemplaza si se sube una nueva
        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
            if ($imagen === null) {
                $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                redirect("admin/recetas/editar?id=$id");
            }
        }

        try {
            $datos = [
                'titulo'             => limpiarTexto($_POST['titulo'],       150),
                'descripcion'        => limpiarTexto($_POST['descripcion'],  5000),
                'tiempo_preparacion' => (int)($_POST['tiempo_preparacion']   ?? 0),
                'porciones'          => (int)($_POST['porciones']            ?? 4),
                'dificultad'         => $this->dificultadValida($_POST['dificultad'] ?? ''),
                'id_categoria'       => (int)($_POST['id_categoria']         ?? 0) ?: null,
                'id_pais'            => (int)($_POST['id_pais']              ?? 0) ?: null,
                'id_chef'            => (int)($_POST['id_chef']              ?? 0) ?: null,
                'video_url'          => limpiarTexto($_POST['video_url']     ?? '', 255) ?: null,
                'calorias'           => (int)($_POST['calorias']             ?? 0) ?: null,
                'publicada'          => isset($_POST['publicada']) ? 1 : 0,
                'destacada'          => isset($_POST['destacada']) ? 1 : 0,
                'ingredientes'       => $this->normalizarIngredientes($_POST),
                'pasos'              => $this->normalizarPasos($_POST),
            ];
            if ($imagen !== null) $datos['imagen'] = $imagen;

            $ok = $this->recetaModel->editarAdmin($id, $datos);

            if ($ok) {
                $_SESSION['exito'] = '✅ Receta actualizada correctamente.';
                redirect('admin/recetas');
            } else {
                $_SESSION['error'] = 'No se encontró la receta para actualizar.';
                redirect("admin/recetas/editar?id=$id");
            }

        } catch (\Throwable $e) {
            error_log('[AdminRecetaController::editarPost] ' . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar la receta. Inténtalo de nuevo.';
            redirect("admin/recetas/editar?id=$id");
        }
    }

    // ──────────────────────────────────────────────────────────
    // GET  admin/recetas/ver?id=N — Detalle de receta
    // ──────────────────────────────────────────────────────────
    public function ver(): void
    {
        requireRole(ROL_ADMIN);

        $id     = (int)($_GET['id'] ?? 0);
        $receta = $id ? $this->recetaModel->obtenerCompletaAdmin($id) : null;

        if (!$receta) {
            $_SESSION['error'] = 'Receta no encontrada.';
            redirect('admin/recetas');
        }

        $pageTitle = 'Ver receta — ' . htmlspecialchars($receta['titulo']);
        include __DIR__ . '/../view/admin/receta_ver.php';
    }

    // ──────────────────────────────────────────────────────────
    // POST admin/recetas/eliminar — Procesar eliminación
    // ──────────────────────────────────────────────────────────
    public function eliminarPost(): void
    {
        requireRole(ROL_ADMIN);

        // Acepta JSON (fetch) o form normal
        $raw  = file_get_contents('php://input');
        $json = $raw ? (json_decode($raw, true) ?? []) : [];

        $id         = (int)(($_POST['receta_id'] ?? $json['receta_id'] ?? 0));
        $tokenPost  = $_POST['csrf_token'] ?? $json['csrf_token'] ?? '';
        $tokenSes   = $_SESSION['csrf_token'] ?? '';

        header('Content-Type: application/json; charset=utf-8');

        if (!$id) {
            echo json_encode(['error' => 'ID inválido.']); return;
        }
        if (!$tokenPost || !hash_equals($tokenSes, $tokenPost)) {
            http_response_code(403);
            echo json_encode(['error' => 'Petición no autorizada.']); return;
        }
        if (!hasRole(ROL_ADMIN)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permisos.']); return;
        }

        try {
            $ok = $this->recetaModel->eliminarAdmin($id);
            echo json_encode($ok
                ? ['exito' => true,  'mensaje' => 'Receta eliminada correctamente.']
                : ['error' => 'La receta no existe o ya fue eliminada.']
            );
        } catch (\Throwable $e) {
            error_log('[AdminRecetaController::eliminarPost] ' . $e->getMessage());
            echo json_encode(['error' => 'Error al eliminar. Inténtalo de nuevo.']);
        }
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════

    /** Validación centralizada del formulario (crear + editar) */
    private function validarFormulario(array $post, array $files): array
    {
        $e = [];
        $titulo = trim($post['titulo'] ?? '');
        if ($titulo === '')                      $e[] = 'El título es obligatorio.';
        if (mb_strlen($titulo) > 150)            $e[] = 'El título no puede superar 150 caracteres.';

        $tiempo = (int)($post['tiempo_preparacion'] ?? 0);
        if ($tiempo <= 0)                        $e[] = 'El tiempo de preparación debe ser mayor a 0.';
        if ($tiempo > 9999)                      $e[] = 'El tiempo de preparación parece excesivo.';

        $porciones = (int)($post['porciones'] ?? 0);
        if ($porciones <= 0)                     $e[] = 'Las porciones deben ser mayor a 0.';

        $dif = $post['dificultad'] ?? '';
        if (!in_array($dif, ['Fácil', 'Media', 'Difícil'], true))
                                                 $e[] = 'Selecciona una dificultad válida.';

        // Al menos un ingrediente con nombre
        $nombres = array_filter(array_map('trim', $post['ing_nombre'] ?? []));
        if (empty($nombres))                     $e[] = 'Agrega al menos un ingrediente.';

        // Al menos un paso
        $pasos = array_filter(array_map('trim', $post['paso_desc'] ?? []));
        if (empty($pasos))                       $e[] = 'Agrega al menos un paso de preparación.';

        return $e;
    }

    /** Normaliza los arrays de ingredientes desde $_POST */
    private function normalizarIngredientes(array $post): array
    {
        $nombres    = $post['ing_nombre']   ?? [];
        $cantidades = $post['ing_cantidad'] ?? [];
        $unidades   = $post['ing_unidad']   ?? [];
        $result     = [];
        foreach ($nombres as $i => $nombre) {
            $nombre = trim($nombre);
            if ($nombre === '') continue;
            $result[] = [
                'nombre'   => $nombre,
                'cantidad' => trim($cantidades[$i] ?? ''),
                'unidad'   => trim($unidades[$i]   ?? ''),
            ];
        }
        return $result;
    }

    /** Normaliza los arrays de pasos desde $_POST */
    private function normalizarPasos(array $post): array
    {
        return array_values(
            array_filter(
                array_map('trim', $post['paso_desc'] ?? [])
            )
        );
    }

    /** Valida y normaliza el valor de dificultad */
    private function dificultadValida(string $v): string
    {
        return in_array($v, ['Fácil', 'Media', 'Difícil'], true) ? $v : 'Media';
    }

    /** Lista países ordenados para selects — reutiliza el PDO del modelo */
    private function listarPaisesSimple(): array
    {
        try {
            $pdo = Conexion::getInstancia()->getPDO();
            return $pdo->query("SELECT id_pais, nombre FROM paises ORDER BY nombre ASC")
                       ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
