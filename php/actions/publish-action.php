<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../publish.php');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$itemCondition = $_POST['item_condition'] ?? '';
$modality = $_POST['modality'] ?? '';
$price = (float) ($_POST['price'] ?? 0);
$tradeInterest = trim($_POST['trade_interest'] ?? '');
$careerCategory = $_POST['career_category'] ?? '';
$materialCategory = $_POST['material_category'] ?? '';

$errors = [];

if ($title === '' || $description === '') {
    $errors[] = 'El titulo y la descripcion son obligatorios.';
}
if (!in_array($itemCondition, ITEM_CONDITIONS, true)) {
    $errors[] = 'Selecciona una condicion valida del material.';
}
if (!in_array($modality, TRANSACTION_MODALITIES, true)) {
    $errors[] = 'Selecciona una modalidad valida.';
}
// HU-06, Escenario 1: en modalidad Venta el precio debe ser mayor a $0.
if ($modality === 'Venta' && $price <= 0) {
    $errors[] = 'Para la modalidad Venta, el precio debe ser mayor a $0.';
}
if (!in_array($careerCategory, ACADEMIC_CAREERS, true) || !in_array($materialCategory, MATERIAL_CATEGORIES, true)) {
    $errors[] = 'Selecciona una carrera y una categoria de material validas.';
}

$uploadedImages = $_FILES['images'] ?? null;
$imageCount = $uploadedImages ? count(array_filter($uploadedImages['name'])) : 0;

// HU-07, Escenario 2: entre 1 y 5 imagenes.
if ($imageCount < 1) {
    $errors[] = 'Debes adjuntar al menos 1 imagen del material.';
}
if ($imageCount > MAX_IMAGES_PER_PUBLICATION) {
    $errors[] = 'Solo se permite un maximo de ' . MAX_IMAGES_PER_PUBLICATION . ' imagenes por publicacion.';
}

if (!empty($errors)) {
    flashMessage('error', implode(' ', $errors));
    redirectTo('../../publish.php');
}

$publicationModel = new Publication();
$publicationId = $publicationModel->create(currentUserId(), [
    'title'             => $title,
    'description'       => $description,
    'item_condition'    => $itemCondition,
    'modality'          => $modality,
    'price'             => $price,
    'trade_interest'    => $tradeInterest,
    'career_category'   => $careerCategory,
    'material_category' => $materialCategory,
]);

if (!is_dir(UPLOAD_DIR_PRODUCTS)) {
    mkdir(UPLOAD_DIR_PRODUCTS, 0755, true);
}

$imagesSaved = 0;
foreach ($uploadedImages['name'] as $index => $originalName) {
    if ($originalName === '' || $imagesSaved >= MAX_IMAGES_PER_PUBLICATION) {
        continue;
    }

    $mimeType = $uploadedImages['type'][$index];
    $tmpPath = $uploadedImages['tmp_name'][$index];
    $size = $uploadedImages['size'][$index];

    if (!in_array($mimeType, ALLOWED_IMAGE_MIME_TYPES, true) || $size > MAX_IMAGE_SIZE_BYTES) {
        continue; // Se ignoran archivos invalidos; la validacion de JS ya evita esto en el flujo normal.
    }

    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $fileName = 'pub_' . $publicationId . '_' . $imagesSaved . '_' . time() . '.' . $extensionMap[$mimeType];
    move_uploaded_file($tmpPath, UPLOAD_DIR_PRODUCTS . $fileName);

    $publicationModel->addImage($publicationId, $fileName, $imagesSaved === 0);
    $imagesSaved++;
}

flashMessage('success', 'Tu publicacion fue creada y ya esta visible en el catalogo.');
redirectTo('../../product.php?id=' . $publicationId);
