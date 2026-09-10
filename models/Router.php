<?php
/**
 * Router — modelo que registra y resuelve rutas limpias.
 */
class Router
{
    private array $routes = [];

    /** Registra una ruta: método HTTP, patrón, [controlador, acción] */
    public function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => '#^' . trim($pattern, '/') . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Resuelve la URI actual y devuelve [controlador, acción, params]
     * o null si no hay coincidencia.
     */
    public function resolve(string $method, string $uri): ?array
    {
        // Decodificar la URI para que espacios (%20) y otros caracteres
        // coincidan correctamente con el BASE_URL calculado desde DOCUMENT_ROOT
        $uri = trim(urldecode(parse_url($uri, PHP_URL_PATH)), '/');

        // Quitar el prefijo del subdirectorio si existe (ej: GastroDigital 2.0)
        $base = trim(urldecode(parse_url(BASE_URL, PHP_URL_PATH)), '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = trim(substr($uri, strlen($base)), '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // quitar match completo
                return [$route['handler'][0], $route['handler'][1], $matches];
            }
        }
        return null;
    }
}
