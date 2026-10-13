<?php
$helpBaseUrl = rtrim(BASE_URL, '/');
$helpRoute = trim((string) ($_GET['route'] ?? ''), '/');
if ($helpRoute === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = trim((string) (parse_url(BASE_URL, PHP_URL_PATH) ?: ''), '/');
    if ($basePath !== '' && str_starts_with(trim($requestPath, '/'), $basePath . '/')) {
        $requestPath = substr(trim($requestPath, '/'), strlen($basePath));
    }
    $helpRoute = trim($requestPath, '/');
    if (str_starts_with($helpRoute, 'index.php/')) {
        $helpRoute = substr($helpRoute, strlen('index.php/'));
    } elseif ($helpRoute === 'index.php') {
        $helpRoute = '';
    }
}
$helpTitle = strtolower((string) ($pageTitle ?? ''));

$helpContent = [
    'inicio' => ['Inicio', 'Descubre recetas destacadas y accede rápidamente al catálogo, las categorías y las demás secciones.'],
    'recetas' => ['Explorar recetas', 'Busca recetas publicadas y usa los filtros para encontrar opciones por categoría, país, tiempo o dificultad. Abre una receta para consultar sus ingredientes y preparación.'],
    'categorias' => ['Categorías', 'Elige una categoría para descubrir recetas agrupadas por tipo de plato.'],
    'paises' => ['Países', 'Selecciona un país para explorar recetas asociadas a su gastronomía.'],
    'favoritos' => ['Favoritos', 'Aquí encuentras las recetas que guardaste para volver a consultarlas cuando quieras.'],
    'mis-comentarios' => ['Comentarios y valoraciones', 'Consulta y administra los comentarios y las valoraciones que has dejado en las recetas.'],
    'mis-valoraciones' => ['Comentarios y valoraciones', 'Consulta y administra los comentarios y las valoraciones que has dejado en las recetas.'],
    'perfil' => ['Mi perfil', 'Revisa y actualiza la información de tu cuenta.'],
    'dashboard' => ['Mi inicio', 'Accede a un resumen de tu actividad y a las herramientas principales de tu cuenta.'],
    'chef/dashboard' => ['Mis recetas', 'Administra las recetas de tu autoría y revisa su estado de publicación.'],
    'mis-recetas' => ['Mis recetas', 'Administra las recetas de tu autoría y revisa su estado de publicación.'],
    'crear-receta' => ['Crear receta', 'Completa los datos, ingredientes y pasos para proponer una receta a la comunidad.'],
    'editar-receta' => ['Editar receta', 'Actualiza la información, los ingredientes o los pasos de esta receta y guarda los cambios.'],
    'admin' => ['Panel de administración', 'Consulta el resumen del sitio y accede a las herramientas de gestión y supervisión.'],
    'admin/usuarios' => ['Gestión de usuarios', 'Busca cuentas y revisa la información y el estado de los usuarios registrados.'],
    'admin/categorias' => ['Gestión de categorías', 'Organiza las categorías que se utilizan para clasificar las recetas.'],
    'admin/reportes' => ['Reportes', 'Revisa los reportes enviados por la comunidad para darles seguimiento.'],
    'admin/moderacion' => ['Moderación', 'Revisa contenido reportado y comentarios para mantener la comunidad en orden.'],
    'admin/recetas' => ['Gestión de recetas', 'Consulta y administra las recetas registradas, su información y su publicación.'],
    'admin/recetas/nueva' => ['Crear receta', 'Registra una receta con su descripción, categoría, ingredientes y pasos de preparación.'],
    'admin/recetas/editar' => ['Editar receta', 'Modifica los datos de la receta y guarda los cambios para actualizar su ficha.'],
    'admin/recetas/ver' => ['Detalle de receta', 'Consulta la información completa de esta receta, incluidos sus ingredientes y pasos.'],
    'admin/cambiar-contrasenas' => ['Cambiar contraseñas', 'Actualiza las credenciales de una cuenta siguiendo las indicaciones del formulario.'],
    'login' => ['Iniciar sesión', 'Ingresa con tu correo y contraseña para acceder a las funciones de tu cuenta.'],
    'iniciar-sesion' => ['Iniciar sesión', 'Ingresa con tu correo y contraseña para acceder a las funciones de tu cuenta.'],
    'register' => ['Crear cuenta', 'Completa tus datos para registrarte y participar en la comunidad de GastroDigital.'],
    'registro' => ['Crear cuenta', 'Completa tus datos para registrarte y participar en la comunidad de GastroDigital.'],
];

$helpMessage = $helpContent[$helpRoute] ?? null;
if ($helpMessage === null) {
    foreach ($helpContent as $route => $message) {
        if ($route !== '' && str_starts_with($helpRoute, $route . '/')) {
            $helpMessage = $message;
            break;
        }
    }
}
if ($helpMessage === null) {
    $helpMessage = match (true) {
        str_contains($helpTitle, 'favorito') => $helpContent['favoritos'],
        str_contains($helpTitle, 'comentario') => $helpContent['mis-comentarios'],
        str_contains($helpTitle, 'perfil') => $helpContent['perfil'],
        str_contains($helpTitle, 'categoría'), str_contains($helpTitle, 'categoria') => $helpContent['categorias'],
        str_contains($helpTitle, 'país'), str_contains($helpTitle, 'pais') => $helpContent['paises'],
        str_contains($helpTitle, 'receta') => $helpContent['recetas'],
        default => $helpContent['inicio'],
    };
}
?>
<details class="gd-context-help">
    <summary aria-label="Información sobre <?= htmlspecialchars($helpMessage[0], ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($helpBaseUrl, ENT_QUOTES, 'UTF-8') ?>/img/icon/ICON-Sobrantes/boton-de-informacion.png" alt="">
        <span>¿Cómo funciona?</span>
    </summary>
    <div class="gd-context-help-panel" role="note">
        <strong><?= htmlspecialchars($helpMessage[0], ENT_QUOTES, 'UTF-8') ?></strong>
        <p><?= htmlspecialchars($helpMessage[1], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</details>
