<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Router.php';

/**
 * RouterController — registra todas las rutas y despacha la petición.
 */
class RouterController
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $r = $this->router;

        // ── Auth ──────────────────────────────────────────────────────────
        $r->add('GET',  'login',          ['PageController',  'login']);
        $r->add('POST', 'login',          ['AuthController',  'login']);
        $r->add('GET',  'iniciar-sesion', ['PageController',  'login']);
        $r->add('POST', 'iniciar-sesion', ['AuthController',  'login']);

        $r->add('GET',  'register',       ['PageController',  'register']);
        $r->add('POST', 'register',       ['AuthController',  'registro']);
        $r->add('GET',  'registro',       ['PageController',  'register']);
        $r->add('POST', 'registro',       ['AuthController',  'registro']);

        $r->add('GET',  'logout',         ['AuthController',  'logout']);
        $r->add('GET',  'cerrar-sesion',  ['AuthController',  'logout']);
        $r->add('POST', 'chatbot',        ['ChatbotController', 'respuesta']);

        // ── Perfil (Común a todos) ────────────────────────────────────────
        $r->add('GET',  'perfil',   ['PageController', 'perfil']);

        // ── Usuario ───────────────────────────────────────────────────────
        $r->add('GET',  'dashboard',       ['PageController', 'dashboardUsuario']);
        $r->add('GET',  'recetas',         ['PageController', 'verRecetas']);
        $r->add('GET',  'categorias',      ['PageController', 'categorias']);
        $r->add('GET',  'paises',          ['PageController', 'paises']);
        $r->add('GET',  'favoritos',       ['PageController', 'favoritos']);
        $r->add('POST', 'favorito',        ['PageController', 'favorito']);
        $r->add('GET',  'mis-comentarios', ['PageController', 'misComentarios']);
        $r->add('GET',  'mis-valoraciones',['PageController', 'misComentarios']); // alias → misma vista, tab=valoraciones

        // ── Chef ──────────────────────────────────────────────────────────
        $r->add('GET',  'chef/dashboard',  ['PageController', 'misRecetas']);
        $r->add('GET',  'mis-recetas',     ['PageController', 'misRecetas']);
        $r->add('GET',  'crear-receta',    ['PageController', 'crearReceta']);
        $r->add('GET',  'editar-receta',   ['PageController', 'editarReceta']);

        // ── Admin ─────────────────────────────────────────────────────────
        $r->add('GET',  'admin',             ['PageController', 'dashboardAdmin']);
        $r->add('GET',  'admin/usuarios',    ['PageController', 'adminUsuarios']);
        $r->add('GET',  'admin/categorias',  ['PageController', 'adminCategorias']);
        $r->add('GET',  'admin/reportes',    ['PageController', 'adminReportes']);
        $r->add('GET',  'admin/moderacion',  ['PageController', 'adminModeracion']);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = isset($_GET['route'])
            ? '/' . ltrim((string) $_GET['route'], '/')
            : $_SERVER['REQUEST_URI'];

        $match = $this->router->resolve($method, $uri);

        if ($match === null) {
            // Ruta no encontrada → página de inicio
            require_once __DIR__ . '/../index_home.php';
            return;
        }

        [$controllerName, $action, $params] = $match;

        // Cargar el archivo del controlador correspondiente
        $controllerFile = __DIR__ . '/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(404);
            echo "Controlador no encontrado.";
            return;
        }
        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            http_response_code(404);
            echo "Clase de controlador no encontrada.";
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            http_response_code(404);
            echo "Acción no encontrada.";
            return;
        }

        $controller->$action(...$params);
    }
}
