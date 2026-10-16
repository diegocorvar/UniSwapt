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

            self::runMigrations();
        }

        return self::$connection;
    }

    private static function runSchema(): void
    {
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        $schemaSql = file_get_contents($schemaPath);
        self::$connection->exec($schemaSql);
    }

    /** Aplica una sola vez las migraciones SQL pendientes en orden de nombre. */
    private static function runMigrations(): void
    {
        self::$connection->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $migrationsDirectory = __DIR__ . '/../../database/migrations';
        $migrationPaths = glob($migrationsDirectory . '/*.sql') ?: [];
        sort($migrationPaths, SORT_STRING);

        foreach ($migrationPaths as $migrationPath) {
            $version = basename($migrationPath);
            $alreadyApplied = self::$connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
            $alreadyApplied->execute(['version' => $version]);

            if ($alreadyApplied->fetchColumn()) {
                continue;
            }

            $migrationSql = file_get_contents($migrationPath);
            if ($migrationSql === false) {
                throw new RuntimeException("No se pudo leer la migracion {$version}.");
            }

            self::$connection->beginTransaction();
            try {
                self::$connection->exec($migrationSql);
                $recordMigration = self::$connection->prepare(
                    'INSERT INTO schema_migrations (version) VALUES (:version)'
                );
                $recordMigration->execute(['version' => $version]);
                self::$connection->commit();
            } catch (Throwable $exception) {
                self::$connection->rollBack();
                throw $exception;
            }
        }
    }
}
