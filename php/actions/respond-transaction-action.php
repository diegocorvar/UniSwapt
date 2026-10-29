<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$decision = $_POST['decision'] ?? '';

$transactionModel = new Transaction();
$transaction = $transactionModel->findById($transactionId);

if (!$transaction || (int) $transaction['seller_id'] !== currentUserId()) {
    flashMessage('error', 'No tienes permiso para responder a esta propuesta.');
    redirectTo('../../dashboard.php');
}

$publicationModel = new Publication();

if ($decision === 'aceptar') {
    $transactionModel->accept($transactionId);
    $publicationModel->markAsReserved((int) $transaction['publication_id']);
    flashMessage('success', 'Propuesta aceptada. El articulo paso a "En Proceso de Entrega".');
} elseif ($decision === 'rechazar') {
    // Flujo 01, alterno A1: la publicacion permanece disponible.
    $transactionModel->reject($transactionId);
    flashMessage('info', 'Propuesta rechazada.');
} else {
    flashMessage('error', 'Accion no reconocida.');
}

redirectTo('../../dashboard.php?tab=propuestas');
