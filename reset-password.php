<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$userModel = new User();
$tokenRow = $token !== '' ? $userModel->findValidToken($token, 'restablecer_password') : false;

$pageTitle = 'Restablecer contrasena';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/reset-password.php';
require __DIR__ . '/html/partials/footer.php';
