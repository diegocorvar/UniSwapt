<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../login.php');
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

$userModel = new User();
$tokenRow = $userModel->findValidToken($token, 'restablecer_password');

if (!$tokenRow) {
    flashMessage('error', 'El enlace para restablecer tu contrasena expiro o no es valido. Solicita uno nuevo.');
    redirectTo('../../forgot-password.php');
}

if (strlen($password) < 8 || $password !== $passwordConfirm) {
    flashMessage('error', 'Verifica que la contrasena tenga al menos 8 caracteres y que ambas coincidan.');
    redirectTo('../../reset-password.php?token=' . urlencode($token));
}

$userModel->updatePassword((int) $tokenRow['user_id'], $password);
$userModel->markTokenAsUsed((int) $tokenRow['id']);

flashMessage('success', 'Tu contrasena fue actualizada. Ya puedes iniciar sesion.');
redirectTo('../../login.php');
