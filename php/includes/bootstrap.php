<?php
/**
 * Punto unico de arranque incluido por cada pagina publica.
 * Evita repetir la misma lista de requires en cada archivo (DRY).
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session.php';

spl_autoload_register(function (string $className): void {
    $path = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// Flujo 02: el prototipo no tiene tareas programadas (cron), asi que las reservas
// vencidas (RESERVATION_EXPIRATION_HOURS) se liberan al inicio de cada peticion,
// antes de que cualquier pagina o action lea el estado de las publicaciones.
try {
    (new Transaction())->releaseExpiredReservations();
} catch (Throwable $exception) {
    error_log('UniSwapt: no se pudieron liberar las reservas vencidas: ' . $exception->getMessage());
}
