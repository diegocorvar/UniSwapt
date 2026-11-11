<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../profile.php');
}

requireValidCsrfToken('../../profile.php');

$career = trim($_POST['career'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$whatsappPhone = trim($_POST['whatsapp_phone'] ?? '');
$userModel = new User();

if (!in_array($career, ACADEMIC_CAREERS, true)) {
    flashMessage('error', 'Selecciona una carrera valida del catalogo institucional.');
    redirectTo('../../profile.php');
}

$profilePhotoPath = null;

if (!empty($_FILES['profile_photo']['name'])) {
    $file = $_FILES['profile_photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        flashMessage('error', 'Ocurrio un problema al subir la foto de perfil.');
        redirectTo('../../profile.php');
    }

    // HU-04: el tipo se comprueba leyendo el archivo en el servidor (finfo_file),
    // no con $file['type'], que es un dato controlado por el cliente.
    $mimeType = detectUploadedMimeType((string) $file['tmp_name']);

    if ($mimeType === null || !in_array($mimeType, ALLOWED_PROFILE_PHOTO_MIME_TYPES, true)) {
        flashMessage('error', 'La foto de perfil debe ser JPG o PNG.');
        redirectTo('../../profile.php');
    }

    if ($file['size'] > MAX_PROFILE_PHOTO_SIZE_BYTES) {
        flashMessage('error', 'La foto de perfil no debe superar 2MB.');
        redirectTo('../../profile.php');
    }

    if (!is_dir(UPLOAD_DIR_PROFILES)) {
        mkdir(UPLOAD_DIR_PROFILES, 0755, true);
    }

    $extension = extensionForImageMime($mimeType);
    $profilePhotoPath = 'perfil_' . currentUserId() . '_' . time() . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR_PROFILES . $profilePhotoPath)) {
        flashMessage('error', 'No fue posible guardar la foto de perfil.');
        redirectTo('../../profile.php');
    }
}

$userModel->updateProfile(currentUserId(), $career, $semester, $profilePhotoPath);

if ($whatsappPhone !== '') {
    $userModel->updateWhatsAppPhone(currentUserId(), $whatsappPhone);
}

flashMessage('success', 'Tu perfil se actualizo correctamente.');
redirectTo('../../profile.php');
