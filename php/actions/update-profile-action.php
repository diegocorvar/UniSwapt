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

    // Supabase Storage en "profiles/<archivo>" o assets/uploads/profiles/ en local;
    // se guarda en la base lo que devuelva ImageStorage (URL publica o nombre).
    $extension = extensionForImageMime($mimeType);
    $fileName = 'perfil_' . currentUserId() . '_' . time() . '.' . $extension;
    $profilePhotoPath = ImageStorage::store((string) $file['tmp_name'], ImageStorage::TYPE_PROFILES, $fileName);

    if ($profilePhotoPath === null) {
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
