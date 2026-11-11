<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

requireValidCsrfToken('../../dashboard.php');

$publicationId = (int) ($_POST['publication_id'] ?? 0);
$newStatus = $_POST['publication_status'] ?? '';

if (!in_array($newStatus, PUBLICATION_STATUSES, true)) {
    flashMessage('error', 'Estado no valido.');
    redirectTo('../../dashboard.php');
}

$publicationModel = new Publication();
$publication = $publicationModel->findById($publicationId);

if (!$publication || (int) $publication['user_id'] !== currentUserId()) {
    flashMessage('error', 'No fue posible actualizar esa publicacion.');
    redirectTo('../../dashboard.php');
}

// RF-10 / Flujo 02: mientras exista una transaccion aceptada (encuentro pendiente
// o coordinado), el estado lo controla el flujo de entrega. Volver a "Disponible"
// a mano permitiria recibir y aceptar nuevas propuestas sobre un articulo reservado.
if ($newStatus !== $publication['publication_status']
    && (new Transaction())->hasActiveTransactionForPublication($publicationId)) {
    $message = $newStatus === 'Disponible'
        ? 'No puedes volver a marcar la publicacion como Disponible mientras haya una entrega en curso. Cancela la transaccion desde tu panel si ya no se realizara.'
        : 'Hay una entrega en curso para esta publicacion. Completa o cancela la transaccion desde tu panel antes de cambiar su estado.';
    flashMessage('error', $message);
    redirectTo('../../product.php?id=' . $publicationId);
}

$updated = $publicationModel->updateStatus($publicationId, currentUserId(), $newStatus);

flashMessage($updated ? 'success' : 'error', $updated
    ? 'El estado de tu publicacion fue actualizado.'
    : 'No fue posible actualizar esa publicacion.');

redirectTo('../../dashboard.php');
