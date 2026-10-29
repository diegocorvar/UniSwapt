<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../index.php');
}

$targetType = $_POST['target_type'] ?? '';
$targetId = (int) ($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$evidence = trim($_POST['evidence'] ?? '');

if (!in_array($targetType, ['usuario', 'publicacion'], true) || $reason === '') {
    flashMessage('error', 'Describe el motivo del reporte antes de enviarlo.');
    redirectTo('../../index.php');
}

(new Rating())->fileReport(currentUserId(), $targetType, $targetId, $reason, $evidence !== '' ? $evidence : null);

flashMessage('success', 'Tu reporte fue enviado al equipo de moderacion. Gracias por ayudar a cuidar la comunidad.');
redirectTo($targetType === 'publicacion' ? 'product.php?id=' . $targetId : 'profile.php?id=' . $targetId);
