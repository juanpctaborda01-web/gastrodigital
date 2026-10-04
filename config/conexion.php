<?php
require_once __DIR__ . '/config.php';
// config.php ya define BASE_URL, ROOT_PATH y arranca la sesión

class Conexion {
    private static $instancia = null;
    private $pdo;

    private $host     = '127.0.0.1';
    private $dbname   = 'recetas_db';
    private $usuario  = 'root';
    private $password = '';
    private $charset  = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->usuario, $this->password, $opciones);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }

    // Singleton: una sola instancia de conexión
    public static function getInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function getPDO() {
        return $this->pdo;
    }
}
