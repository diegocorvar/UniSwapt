<?php
require_once __DIR__ . '/php/includes/bootstrap.php';
requireLogin();

$transactionId = (int) ($_GET['transaction_id'] ?? 0);
$transactionModel = new Transaction();
$transaction = $transactionModel->findById($transactionId);
$userId = currentUserId();

$isParticipant = $transaction && in_array($userId, [(int) $transaction['buyer_id'], (int) $transaction['seller_id']], true);

if (!$transaction || !$isParticipant || $transaction['transaction_status'] !== 'Completada') {
    flashMessage('error', 'Esta transaccion no esta disponible para calificar.');
    redirectTo('dashboard.php?tab=transacciones');
}

$ratingModel = new Rating();
if ($ratingModel->alreadyRated($transactionId, $userId)) {
    flashMessage('info', 'Ya calificaste esta transaccion.');
    redirectTo('dashboard.php?tab=transacciones');
}

$publicationModel = new Publication();
$publication = $publicationModel->findById((int) $transaction['publication_id']);
$counterpartId = (int) $transaction['buyer_id'] === $userId ? (int) $transaction['seller_id'] : (int) $transaction['buyer_id'];
$counterpart = (new User())->findById($counterpartId);

$pageTitle = 'Calificar transaccion';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/rate.php';
require __DIR__ . '/html/partials/footer.php';
