<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../forgot-password.php');
}

requireValidCsrfToken('../../forgot-password.php');

$email = normalizeEmail((string) ($_POST['email'] ?? ''));

// Todas las cuentas usan el dominio institucional; cualquier otro se rechaza aqui.
if (!isInstitutionalEmail($email)) {
    flashMessage('error', 'Ingresa tu correo institucional (' . INSTITUTIONAL_EMAIL_DOMAIN . ') con un formato valido.');
    redirectTo('../../forgot-password.php');
}

$userModel = new User();
$user = $userModel->findByEmail($email);

$delivery = null;
$directLink = null;

if ($user) {
    $token = $userModel->createToken((int) $user['id'], 'restablecer_password', PASSWORD_RESET_TTL_HOURS);
    // Si el envio falla se registra en error_log, pero no se informa: hacerlo
    // revelaria que el correo pertenece a una cuenta.
    $delivery = Mailer::sendPasswordResetEmail($user['email'], $user['full_name'], $token);
    $directLink = 'reset-password.php?token=' . $token;
}

// No revelamos si el correo existe: en ambos casos se muestra la misma pantalla.
$_SESSION['pending_email_action'] = pendingEmailAction('restablecimiento', $email, $delivery, $directLink);

redirectTo('../../check-email.php');
