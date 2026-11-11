<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../index.php');
}

requireValidCsrfToken('../../index.php');

$targetType = $_POST['target_type'] ?? '';
$targetId = (int) ($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$evidence = trim($_POST['evidence'] ?? '');

if (!in_array($targetType, ['usuario', 'publicacion'], true)) {
    flashMessage('error', 'Tipo de reporte no valido.');
    redirectTo('../../index.php');
}

// Flujo 03: solo se aceptan reportes sobre usuarios o publicaciones que existan.
$targetExists = $targetType === 'publicacion'
    ? (bool) (new Publication())->findById($targetId)
    : (bool) (new User())->findById($targetId);

if ($targetId <= 0 || !$targetExists) {
    flashMessage('error', 'El usuario o la publicacion que intentas reportar no existe.');
    redirectTo('../../index.php');
}

// Las actions viven en php/actions/, por eso la ruta de regreso sube dos niveles.
$returnPath = $targetType === 'publicacion'
    ? '../../product.php?id=' . $targetId
    : '../../profile.php?id=' . $targetId;

if ($reason === '') {
    flashMessage('error', 'Describe el motivo del reporte antes de enviarlo.');
    redirectTo($returnPath);
}

(new Rating())->fileReport(currentUserId(), $targetType, $targetId, $reason, $evidence !== '' ? $evidence : null);

flashMessage('success', 'Tu reporte fue enviado al equipo de moderacion. Gracias por ayudar a cuidar la comunidad.');
redirectTo($returnPath);
