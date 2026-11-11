<?php
/**
 * Arranque de sesion, unico punto de entrada para iniciar sesiones PHP
 * en toda la aplicacion (RF-03: inicio y cierre de sesion seguro).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flashMessage('error', 'Debes iniciar sesion para continuar.');
        redirectTo('login.php');
    }
}

/**
 * Proteccion CSRF (patron "synchronizer token"): cada sesion tiene un token
 * aleatorio que se incrusta en todos los formularios POST y se compara en
 * cada action antes de procesar datos.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto listo para imprimir dentro de un <form method="post">. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function isValidCsrfToken(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/** Corta la peticion si el token enviado no coincide con el de la sesion. */
function requireValidCsrfToken(string $redirectPath): void
{
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('error', 'Tu sesion del formulario expiro o la solicitud no es valida. Intenta de nuevo.');
        redirectTo($redirectPath);
    }
}

/** Genera un token nuevo (por ejemplo, al iniciar sesion). */
function rotateCsrfToken(): void
{
    unset($_SESSION['csrf_token']);
}
