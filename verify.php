<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$userModel = new User();
$tokenRow = $token !== '' ? $userModel->findValidToken($token, 'verificacion_cuenta') : false;

if ($tokenRow) {
    $userModel->activateAccount((int) $tokenRow['user_id']);
    $userModel->markTokenAsUsed((int) $tokenRow['id']);
}

$pageTitle = 'Verificacion de cuenta';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/verify.php';
require __DIR__ . '/html/partials/footer.php';
