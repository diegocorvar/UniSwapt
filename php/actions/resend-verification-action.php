<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../resend-verification.php');
}

requireValidCsrfToken('../../resend-verification.php');

$email = normalizeEmail((string) ($_POST['email'] ?? ''));

// Solo existen cuentas con el dominio institucional exacto.
if (!isInstitutionalEmail($email)) {
    flashMessage('error', 'Ingresa tu correo institucional (' . INSTITUTIONAL_EMAIL_DOMAIN . ') con un formato valido.');
    redirectTo('../../resend-verification.php');
}

$userModel = new User();
$user = $userModel->findByEmail($email);
$delivery = null;
$directLink = null;

// HU-02: solo las cuentas pendientes reciben un nuevo enlace; los anteriores
// se invalidan para que unicamente funcione el mas reciente.
$token = $user ? $userModel->regenerateVerificationToken((int) $user['id']) : null;

if ($token !== null) {
    // Un fallo de envio se registra en error_log, pero no se informa para no
    // revelar que el correo pertenece a una cuenta pendiente.
    $delivery = Mailer::sendVerificationEmail($user['email'], $user['full_name'], $token);
    $directLink = 'verify.php?token=' . $token;
}

// Respuesta identica si el correo no existe, si la cuenta ya esta activa o si
// se pidio otro enlace hace muy poco.
$_SESSION['pending_email_action'] = pendingEmailAction('reenvio', $email, $delivery, $directLink);
redirectTo('../../check-email.php');
