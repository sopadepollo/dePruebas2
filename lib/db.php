<?php
declare(strict_types=1);
namespace App\Lib;
use PDO;
use PDOException;

// Cargar configuración si existe
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    /** @noinspection PhpIncludeInspection */
    require_once $configPath;
}

// Valores por defecto si no se define config.php
if (!defined('DB_HOST')) { define('DB_HOST', '127.0.0.1'); }
if (!defined('DB_PORT')) { define('DB_PORT', 3306); }
if (!defined('DB_NAME')) { define('DB_NAME', 'Sevillanas'); }
if (!defined('DB_USER')) { define('DB_USER', 'root'); }
if (!defined('DB_PASS')) { define('DB_PASS', ''); }

/**
 * Retorna una instancia única de PDO (MySQL/MariaDB)
 */
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Usa las constantes definidas en lib/config.php
            $host = DB_HOST;
            $db = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;
            $port = DB_PORT;

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    return $pdo;
}