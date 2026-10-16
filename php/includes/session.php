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
