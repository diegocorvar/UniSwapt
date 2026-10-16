<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$stars = (int) ($_POST['stars'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$transactionModel = new Transaction();
$transaction = $transactionModel->findById($transactionId);
$userId = currentUserId();

$isParticipant = $transaction && in_array($userId, [(int) $transaction['buyer_id'], (int) $transaction['seller_id']], true);

if (!$transaction || !$isParticipant || $transaction['transaction_status'] !== 'Completada') {
    flashMessage('error', 'Solo puedes calificar transacciones completadas en las que participaste.');
    redirectTo('../../dashboard.php');
}

$ratingModel = new Rating();

if ($ratingModel->alreadyRated($transactionId, $userId)) {
    flashMessage('error', 'Ya calificaste esta transaccion.');
    redirectTo('../../dashboard.php?tab=transacciones');
}

if ($stars < 1 || $stars > 5) {
    flashMessage('error', 'Selecciona una calificacion de 1 a 5 estrellas.');
    redirectTo('../../rate.php?transaction_id=' . $transactionId);
}

$ratedId = (int) $transaction['buyer_id'] === $userId ? (int) $transaction['seller_id'] : (int) $transaction['buyer_id'];
$ratingModel->submit($transactionId, $userId, $ratedId, $stars, $comment !== '' ? $comment : null);

flashMessage('success', 'Gracias, tu calificacion fue registrada.');
redirectTo('../../dashboard.php?tab=transacciones');
