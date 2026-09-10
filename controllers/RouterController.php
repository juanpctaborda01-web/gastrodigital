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
        $r->add('GET',  'login',    ['PageController',  'login']);
        $r->add('POST', 'login',    ['AuthController',  'login']);
        $r->add('GET',  'register', ['PageController',  'register']);
        $r->add('POST', 'register', ['AuthController',  'registro']);
        $r->add('GET',  'logout',   ['AuthController',  'logout']);

        // ── Usuario ───────────────────────────────────────────────────────
        $r->add('GET',  'dashboard',       ['PageController', 'dashboardUsuario']);
        $r->add('GET',  'recetas',         ['PageController', 'verRecetas']);
        $r->add('GET',  'favoritos',       ['PageController', 'favoritos']);
        $r->add('GET',  'categorias', ['PageController', 'categorias']);
        $r->add('GET',  'perfil',          ['PageController', 'perfil']);
        $r->add('POST', 'perfil/guardar',  ['PerfilController', 'guardar']);
        $r->add('GET',  'chef/(\d+)',       ['PageController', 'perfilChef']);

        // ── Chef ──────────────────────────────────────────────────────────
        $r->add('GET',  'mis-recetas',   ['PageController', 'misRecetas']);
        $r->add('GET',  'crear-receta',  ['PageController', 'crearReceta']);
        $r->add('GET',  'editar-receta', ['PageController', 'editarReceta']);

        // ── Admin ─────────────────────────────────────────────────────────
        $r->add('GET',  'admin',                     ['PageController',     'dashboardAdmin']);
        $r->add('GET',  'admin/usuarios',            ['PageController',     'adminUsuarios']);
        $r->add('GET',  'admin/categorias',          ['PageController',     'adminCategorias']);
        $r->add('GET',  'admin/reportes',            ['PageController',     'adminReportes']);
        $r->add('GET',  'admin/moderacion',          ['PageController',     'adminModeracion']);
        $r->add('POST', 'admin/moderacion/accion',   ['ReporteController',  'accion']);
        $r->add('POST', 'admin/moderacion/borrar-comentario', ['ModeraController', 'borrarComentario']);
        $r->add('POST', 'admin/moderacion/borrar-valoracion', ['ModeraController', 'borrarValoracion']);
        $r->add('POST', 'admin/categoria/crear',     ['CategoriaController','crear']);
        $r->add('POST', 'admin/categoria/eliminar',  ['CategoriaController','eliminar']);
        $r->add('POST', 'admin/usuario/rol',         ['UsuarioController',  'cambiarRol']);
        $r->add('POST', 'admin/usuario/eliminar',    ['UsuarioController',  'eliminar']);

        // ── Reportes (AJAX) ────────────────────────────────────────────────
        $r->add('POST', 'api/reportar',              ['ReporteController',  'crear']);

        // ── Chatbot API ────────────────────────────────────────────────────
        $r->add('GET',  'api/chatbot/token', ['ChatbotController', 'token']);
        $r->add('POST', 'api/chatbot',       ['ChatbotController', 'responder']);

        // ── Acciones de recetas ────────────────────────────────────────────
        $r->add('POST', 'api/favorito',        ['RecetaController',    'favoritoAjax']);
        $r->add('POST', 'api/valorar',         ['RecetaController',    'valorarAjax']);
        $r->add('POST', 'api/receta/eliminar', ['RecetaController',    'eliminarAjax']);
        $r->add('POST', 'api/receta/crear',    ['RecetaController',    'crearForm']);
        $r->add('POST', 'api/receta/editar',   ['RecetaController',    'editarForm']);

        // ── Token CSRF para fetch AJAX ─────────────────────────────────────
        $r->add('GET',  'api/csrf',            ['RecetaController',    'csrfToken']);

        // ── Comentarios, búsqueda y notificaciones ─────────────────────────
        $r->add('POST', 'api/comentario',      ['ComentarioController','crear']);
        $r->add('GET',  'api/buscar',          ['ComentarioController','buscar']);
        $r->add('GET',  'api/notificaciones',  ['ComentarioController','notificaciones']);

        // ── Perfil (usuario y chef) ────────────────────────────────────────
        $r->add('POST', 'api/perfil/guardar', ['UsuarioController', 'guardarPerfil']);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'];

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
