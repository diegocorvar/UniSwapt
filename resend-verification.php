<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

if (isLoggedIn()) {
    redirectTo('index.php');
}

$pageTitle = 'Reenviar verificacion';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/resend-verification.php';
require __DIR__ . '/html/partials/footer.php';
