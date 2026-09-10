<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/receta.php';
require_once __DIR__ . '/../models/categoria.php';

class RecetaController
{
    private Receta $model;

    public function __construct()
    {
        $this->model = new Receta();
    }

    /** GET /api/csrf — devuelve el token CSRF actual de la sesión */
    public function csrfToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['csrf' => csrfToken()]);
    }

    /** POST /api/favorito — toggle like vía AJAX */
    public function favoritoAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Debes iniciar sesión para guardar favoritos.']);
            return;
        }

        $recetaId = (int)($_POST['receta_id'] ?? 0);
        if (!$recetaId) {
            echo json_encode(['error' => 'ID de receta inválido.']);
            return;
        }

        echo json_encode($this->model->toggleFavorito((int)$_SESSION['usuario_id'], $recetaId));
    }

    /** POST /api/valorar — valoración vía AJAX */
    public function valorarAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Debes iniciar sesión para valorar.']);
            return;
        }

        $recetaId   = (int)($_POST['receta_id']  ?? 0);
        $puntuacion = (int)($_POST['puntuacion'] ?? 0);
        $comentario = trim($_POST['comentario']  ?? '');

        if (!$recetaId || $puntuacion < 1 || $puntuacion > 5) {
            echo json_encode(['error' => 'Datos inválidos.']);
            return;
        }

        echo json_encode($this->model->valorar(
            (int)$_SESSION['usuario_id'], $recetaId, $puntuacion, $comentario
        ));
    }

    /** POST /api/receta/eliminar — eliminar receta del chef */
    public function eliminarAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $tokenPost   = $_POST['csrf_token']    ?? '';
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (!$tokenPost || !hash_equals($tokenSesion, $tokenPost)) {
            http_response_code(403); echo json_encode(['error' => 'Sesión inválida.']); return;
        }
        if (!isLoggedIn()) {
            http_response_code(401); echo json_encode(['error' => 'Debes iniciar sesión.']); return;
        }

        $recetaId = (int)($_POST['receta_id'] ?? 0);
        $userId   = (int)$_SESSION['usuario_id'];
        $rol      = $_SESSION['rol'] ?? '';

        echo json_encode($this->model->eliminar($recetaId, $userId, $rol));
    }

    /** POST /api/receta/crear — crear receta desde formulario chef */
    public function crearForm(): void
    {
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

        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
            if ($imagen === null) {
                $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                redirect('crear-receta');
            }
        }

        $recetaId = $this->model->crear([
            'titulo'       => $titulo,
            'descripcion'  => $desc,
            'tiempo'       => $tiempo,
            'porciones'    => (int)($_POST['porciones'] ?? 4),
            'dificultad'   => $dif,
            'categoria_id' => null,
            'categoria'    => $cat,
            'imagen'       => $imagen,
            'calorias'     => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
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
        $this->model->guardarIngredientes($recetaId, $ingredientes);
        $this->model->guardarPasos($recetaId, $_POST['pasos'] ?? []);

        $_SESSION['exito'] = "Receta \"$titulo\" publicada correctamente.";
        redirect('mis-recetas');
    }

    /** POST /api/receta/editar — editar receta existente */
    public function editarForm(): void
    {
        requireRole(ROL_CHEF);
        verificarCsrf();

        $recetaId = (int)($_POST['receta_id'] ?? 0);
        $userId   = $_SESSION['usuario_id'];

        if (!$recetaId) {
            $_SESSION['error'] = 'Receta no válida.';
            redirect('mis-recetas');
        }

        $receta = $this->model->obtenerParaEditar($recetaId, $userId);
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

        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = procesarImagenUpload($_FILES['imagen'], UPLOAD_PATH);
            if ($imagen === null) {
                $_SESSION['error'] = 'La imagen no es válida (solo JPG, PNG, WEBP · máx. 3 MB).';
                redirect("editar-receta?id=$recetaId");
            }
        }

        $this->model->actualizar($recetaId, [
            'titulo'      => $titulo,
            'descripcion' => $desc,
            'tiempo'      => $tiempo,
            'porciones'   => (int)($_POST['porciones'] ?? 4),
            'dificultad'  => $dif,
            'categoria'   => $cat,
            'calorias'    => $_POST['calorias'] ? (int)$_POST['calorias'] : null,
            'imagen'      => $imagen,
            'chef_id'     => $userId,
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
        $this->model->guardarIngredientes($recetaId, $ingredientes);
        $this->model->guardarPasos($recetaId, $_POST['pasos'] ?? []);

        $_SESSION['exito'] = "Receta \"$titulo\" actualizada correctamente.";
        redirect('mis-recetas');
    }
}
