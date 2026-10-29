<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

$publicationId = (int) ($_POST['publication_id'] ?? 0);
$newStatus = $_POST['publication_status'] ?? '';

if (!in_array($newStatus, PUBLICATION_STATUSES, true)) {
    flashMessage('error', 'Estado no valido.');
    redirectTo('../../dashboard.php');
}

$publicationModel = new Publication();
$updated = $publicationModel->updateStatus($publicationId, currentUserId(), $newStatus);

flashMessage($updated ? 'success' : 'error', $updated
    ? 'El estado de tu publicacion fue actualizado.'
    : 'No fue posible actualizar esa publicacion.');

redirectTo('../../dashboard.php');
