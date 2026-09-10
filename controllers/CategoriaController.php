<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/categoria.php';

class CategoriaController
{
    private Categoria $model;

    public function __construct()
    {
        $this->model = new Categoria();
    }

    public function crear(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $nombre = trim($_POST['nombre'] ?? '');
        $icono  = trim($_POST['icono']  ?? 'bi-egg-fried');
        $color  = trim($_POST['color']  ?? '#c0392b');
        $bg     = trim($_POST['bg']     ?? '#f7ede0');

        if ($nombre) {
            $this->model->crear($nombre, $icono, $color, $bg);
            $_SESSION['exito'] = "Categoría \"$nombre\" creada.";
        } else {
            $_SESSION['error'] = 'El nombre es obligatorio.';
        }
        redirect('admin/categorias');
    }

    public function eliminar(): void
    {
        requireRole(ROL_ADMIN);
        verificarCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->model->eliminar($id);
            $_SESSION['exito'] = 'Categoría eliminada.';
        } else {
            $_SESSION['error'] = 'ID inválido.';
        }
        redirect('admin/categorias');
    }
}
