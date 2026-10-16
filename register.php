<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

if (isLoggedIn()) {
    redirectTo('index.php');
}

$pageTitle = 'Crear cuenta';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/register.php';
require __DIR__ . '/html/partials/footer.php';
