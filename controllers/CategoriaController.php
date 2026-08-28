<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/categoria.php';

requireRole(ROL_ADMIN);

$model  = new Categoria();
$accion = $_POST['accion'] ?? '';

// Verificar CSRF en todas las acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}

switch ($accion) {

    case 'crear':
        $nombre = trim($_POST['nombre'] ?? '');
        $icono  = trim($_POST['icono']  ?? 'bi-egg-fried');
        $color  = trim($_POST['color']  ?? '#c0392b');
        $bg     = trim($_POST['bg']     ?? '#f7ede0');

        if ($nombre) {
            $model->crear($nombre, $icono, $color, $bg);
            $_SESSION['exito'] = "Categoría \"$nombre\" creada.";
        } else {
            $_SESSION['error'] = 'El nombre es obligatorio.';
        }
        break;

    case 'eliminar':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $model->eliminar($id);
            $_SESSION['exito'] = 'Categoría eliminada.';
        }
        break;

    default:
        $_SESSION['error'] = 'Acción no válida.';
}

redirect('view/admin/categorias.php');
