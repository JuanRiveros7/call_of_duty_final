<?php
class Database
{
    private static ?PDO $pdo = null;

    public static function conectar(): PDO
    {
        if (self::$pdo === null) {
            try {
                // Cargar configuración externa
                $config = require __DIR__ . '/config.php';

                $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}";

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                // No mostrar detalles al usuario final
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error al conectar con la base de datos. Intente nuevamente más tarde.");
            }
        }

        return self::$pdo;
    }
}
