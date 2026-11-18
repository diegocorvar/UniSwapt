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

// No revelamos si el correo existe o no, por seguridad; el mensaje es el mismo en ambos casos.
if ($user) {
    $token = $userModel->createToken((int) $user['id'], 'restablecer_password', PASSWORD_RESET_TTL_HOURS);
    $inboxPath = Mailer::sendPasswordResetEmail($user['email'], $user['full_name'], $token);

    $_SESSION['pending_email_action'] = [
        'mode'        => 'restablecimiento',
        'email'       => $email,
        'inbox_path'  => $inboxPath,
        'direct_link' => 'reset-password.php?token=' . $token,
    ];
}

redirectTo('../../check-email.php');
