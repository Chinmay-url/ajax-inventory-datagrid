<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host    = Env::get('DB_HOST', '127.0.0.1');
            $dbname  = Env::get('DB_NAME', 'datagrid_db');
            $user    = Env::get('DB_USER', 'root');
            $pass    = Env::get('DB_PASS', '');
            $charset = Env::get('DB_CHARSET', 'utf8mb4');

            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $dbname, $charset);

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB Connection Error: ' . $e->getMessage());
                JsonResponse::send(false, 500, 'Database connection failed.');
            }
        }

        return self::$instance;
    }
}
