<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

if (isLoggedIn()) {
    redirectTo('index.php');
}

$pageTitle = 'Iniciar sesion';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/login.php';
require __DIR__ . '/html/partials/footer.php';
