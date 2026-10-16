<?php
/**
 * Maneja la conexion a la base de datos local SQLite.
 * Al ser un prototipo, SQLite evita depender de un motor externo o de la
 * nube, pero toda la logica de acceso a datos es real (PDO + SQL real).
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $databaseFile = __DIR__ . '/../../database/uniswapt.sqlite';
            $isFirstRun = !file_exists($databaseFile);

            self::$connection = new PDO('sqlite:' . $databaseFile);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->exec('PRAGMA foreign_keys = ON;');

            if ($isFirstRun) {
                self::runSchema();
            }
        }

        return self::$connection;
    }

    private static function runSchema(): void
    {
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        $schemaSql = file_get_contents($schemaPath);
        self::$connection->exec($schemaSql);
    }
}
