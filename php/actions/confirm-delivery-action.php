<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$action = $_POST['delivery_action'] ?? '';

$transactionModel = new Transaction();
$publicationModel = new Publication();
$transaction = $transactionModel->findById($transactionId);

$isParticipant = $transaction && in_array(currentUserId(), [(int) $transaction['buyer_id'], (int) $transaction['seller_id']], true);

if (!$transaction || !$isParticipant) {
    flashMessage('error', 'No tienes permiso sobre esta transaccion.');
    redirectTo('../../dashboard.php');
}

if ($action === 'confirmar') {
    // Flujo 02, paso 4-5: al confirmar ambas partes, la publicacion se cierra.
    $result = $transactionModel->confirmDelivery($transactionId, currentUserId());

    if ($result['completed']) {
        $publicationModel->markAsClosed((int) $transaction['publication_id'], $transaction['modality']);
        flashMessage('success', 'Entrega confirmada por ambas partes. La transaccion quedo Completada.');
    } else {
        flashMessage('info', 'Confirmaste la entrega. Falta la confirmacion de la otra parte.');
    }
} elseif ($action === 'cancelar') {
    // Flujo 02, excepcion E2: se libera el articulo.
    $transactionModel->cancel($transactionId);
    $publicationModel->markAsAvailable((int) $transaction['publication_id']);
    flashMessage('info', 'La transaccion fue cancelada y el articulo vuelve a estar disponible.');
} else {
    flashMessage('error', 'Accion no reconocida.');
}

redirectTo('../../dashboard.php?tab=transacciones');
