<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$userModel = new User();
$result = $userModel->attemptLogin($email, $password);

if (!$result['success'] && $result['reason'] === 'cuenta_no_verificada') {
    flashMessage('error', 'Tu cuenta aun no ha sido verificada. Revisa tu correo institucional para activarla.');
    redirectTo('../../login.php');
}

// HU-03, Escenario 2: mensaje generico, sin revelar si el correo existe.
if (!$result['success']) {
    flashMessage('error', 'Credenciales incorrectas. Verifica tu informacion.');
    redirectTo('../../login.php');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $result['user']['id'];
$_SESSION['user_name'] = $result['user']['full_name'];

flashMessage('success', 'Bienvenido de vuelta, ' . $result['user']['full_name'] . '.');
redirectTo('../../index.php');
