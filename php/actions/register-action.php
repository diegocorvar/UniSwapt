<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../register.php');
}

requireValidCsrfToken('../../register.php');

$fullName = trim($_POST['full_name'] ?? '');
// El correo se guarda y se busca siempre normalizado (minusculas, sin espacios).
$email = normalizeEmail((string) ($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

if ($fullName === '' || $email === '' || $password === '') {
    flashMessage('error', 'Completa todos los campos para registrarte.');
    redirectTo('../../register.php');
}

if (!isValidEmailFormat($email)) {
    flashMessage('error', 'El formato del correo electronico no es valido.');
    redirectTo('../../register.php');
}

// HU-01, Escenario 2: solo se permite el dominio institucional exacto
// (sin subdominios ni dominios parecidos).
if (!isInstitutionalEmail($email)) {
    flashMessage('error', 'Dominio no permitido. Debes utilizar un correo institucional valido.');
    redirectTo('../../register.php');
}

if (strlen($password) < 8) {
    flashMessage('error', 'La contrasena debe tener al menos 8 caracteres.');
    redirectTo('../../register.php');
}

if ($password !== $passwordConfirm) {
    flashMessage('error', 'Las contrasenas no coinciden.');
    redirectTo('../../register.php');
}

$userModel = new User();

if ($userModel->emailExists($email)) {
    flashMessage('error', 'Ya existe una cuenta registrada con ese correo institucional.');
    redirectTo('../../register.php');
}

// HU-01, Escenario 1: cuenta creada en estado pendiente de verificacion.
$userId = $userModel->register($fullName, $email, $password);
$token = $userModel->createToken($userId, 'verificacion_cuenta', VERIFICATION_TOKEN_TTL_HOURS);
$delivery = Mailer::sendVerificationEmail($email, $fullName, $token);

if (!$delivery['sent']) {
    // La cuenta ya existe en estado pendiente: el usuario puede pedir otro enlace.
    flashMessage('error', 'Tu cuenta fue creada, pero no pudimos enviar el correo de verificacion. Intenta reenviarlo en unos minutos.');
    redirectTo('../../resend-verification.php');
}

$_SESSION['pending_email_action'] = pendingEmailAction('verificacion', $email, $delivery, 'verify.php?token=' . $token);

redirectTo('../../check-email.php');
