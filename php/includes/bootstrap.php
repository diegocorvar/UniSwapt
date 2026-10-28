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
