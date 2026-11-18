<?php
/**
 * Conexion PDO unica de la aplicacion.
 *
 * - Produccion (Railway + Supabase): si existe la variable de entorno
 *   DATABASE_URL (postgres://usuario:clave@host:puerto/base), se conecta a
 *   PostgreSQL con sslmode=require. El esquema se crea con
 *   database/schema_postgres.sql (SQL Editor de Supabase); si la base esta
 *   vacia, se aplica automaticamente en el primer arranque.
 * - Desarrollo local: sin DATABASE_URL se usa SQLite en
 *   database/uniswapt.sqlite, creado a partir de database/schema.sql.
 *
 * En ambos casos se aplican, una sola vez, las migraciones pendientes de
 * database/migrations/ registradas en la tabla schema_migrations.
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $databaseUrl = envValue('DATABASE_URL');

            if ($databaseUrl !== null) {
                self::connectPostgres($databaseUrl);
            } else {
                self::connectSqlite();
            }

            self::runMigrations();
        }

        return self::$connection;
    }

    public static function isPostgres(): bool
    {
        return self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }

    /** Operador de busqueda de texto sin distinguir mayusculas en el motor actual. */
    public static function caseInsensitiveLike(): string
    {
        // En SQLite LIKE ya ignora mayusculas (ASCII); en PostgreSQL se necesita ILIKE.
        return self::isPostgres() ? 'ILIKE' : 'LIKE';
    }

    /**
     * Ejecuta un INSERT y devuelve el id generado. En PostgreSQL usa
     * RETURNING id, que es fiable incluso detras del pooler de Supabase,
     * donde lastval() podria ejecutarse en otra conexion del servidor.
     */
    public static function insertAndGetId(string $insertSql, array $params): int
    {
        $db = self::getConnection();

        if (self::isPostgres()) {
            $stmt = $db->prepare($insertSql . ' RETURNING id');
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $db->prepare($insertSql);
        $stmt->execute($params);
        return (int) $db->lastInsertId();
    }

    private static function connectPostgres(string $databaseUrl): void
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL no tiene un formato valido (postgres://usuario:clave@host:puerto/base).');
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $parts['host'],
            $parts['port'] ?? 5432,
            rawurldecode(ltrim($parts['path'], '/')),
            // Supabase exige TLS; solo se permite endurecerlo (verify-ca / verify-full).
            in_array($query['sslmode'] ?? '', ['verify-ca', 'verify-full'], true) ? $query['sslmode'] : 'require'
        );

        self::$connection = new PDO(
            $dsn,
            isset($parts['user']) ? rawurldecode($parts['user']) : null,
            isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // El pooler de Supabase en modo transaccion (puerto 6543) no admite
                // sentencias preparadas del lado del servidor; la emulacion en PDO
                // sigue escapando los parametros y funciona con cualquier modo.
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]
        );

        // Si la base aun no tiene tablas, aplica el esquema completo de Supabase.
        $hasSchema = self::$connection->query("SELECT to_regclass('public.users') IS NOT NULL")->fetchColumn();
        if (!$hasSchema) {
            self::runSchemaFile(__DIR__ . '/../../database/schema_postgres.sql');
        }
    }

    private static function connectSqlite(): void
    {
        $databaseFile = __DIR__ . '/../../database/uniswapt.sqlite';
        $isFirstRun = !file_exists($databaseFile);

        self::$connection = new PDO('sqlite:' . $databaseFile);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$connection->exec('PRAGMA foreign_keys = ON;');

        if ($isFirstRun) {
            self::runSchemaFile(__DIR__ . '/../../database/schema.sql');
        }
    }

    private static function runSchemaFile(string $schemaPath): void
    {
        $schemaSql = file_get_contents($schemaPath);
        if ($schemaSql === false) {
            throw new RuntimeException("No se pudo leer el esquema {$schemaPath}.");
        }
        self::$connection->exec($schemaSql);
    }

    /** Aplica una sola vez las migraciones SQL pendientes en orden de nombre. */
    private static function runMigrations(): void
    {
        $isPostgres = self::$connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
        self::$connection->exec(
            $isPostgres
                ? 'CREATE TABLE IF NOT EXISTS schema_migrations (
                       version TEXT PRIMARY KEY,
                       applied_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
                   )'
                : 'CREATE TABLE IF NOT EXISTS schema_migrations (
                       version TEXT PRIMARY KEY,
                       applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                   )'
        );

        $migrationsDirectory = __DIR__ . '/../../database/migrations';
        $migrationPaths = glob($migrationsDirectory . '/*.sql') ?: [];
        sort($migrationPaths, SORT_STRING);

        $alreadyApplied = self::$connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');

        foreach ($migrationPaths as $migrationPath) {
            $version = basename($migrationPath);
            $alreadyApplied->execute(['version' => $version]);
            $isApplied = (bool) $alreadyApplied->fetchColumn();
            $alreadyApplied->closeCursor();

            if ($isApplied) {
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
