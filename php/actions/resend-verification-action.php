<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../resend-verification.php');
}

requireValidCsrfToken('../../resend-verification.php');

$email = trim($_POST['email'] ?? '');

if (!isValidEmailFormat($email)) {
    flashMessage('error', 'Ingresa un correo electronico con formato valido.');
    redirectTo('../../resend-verification.php');
}

$userModel = new User();
$user = $userModel->findByEmail($email);

// HU-02: solo las cuentas pendientes reciben un nuevo enlace. Los tokens de
// verificacion anteriores se invalidan para que solo funcione el mas reciente.
if ($user && $user['account_status'] === 'pendiente') {
    $userModel->invalidateTokens((int) $user['id'], 'verificacion_cuenta');
    $token = $userModel->createToken((int) $user['id'], 'verificacion_cuenta', VERIFICATION_TOKEN_TTL_HOURS);
    $inboxPath = Mailer::sendVerificationEmail($user['email'], $user['full_name'], $token);

    $_SESSION['pending_email_action'] = [
        'mode'        => 'verificacion',
        'email'       => $user['email'],
        'inbox_path'  => $inboxPath,
        'direct_link' => 'verify.php?token=' . $token,
    ];

    redirectTo('../../check-email.php');
}

// Mismo mensaje si el correo no existe o si la cuenta ya esta activa.
flashMessage('info', 'Si el correo corresponde a una cuenta pendiente de verificacion, te enviamos un nuevo enlace.');
redirectTo('../../login.php');
