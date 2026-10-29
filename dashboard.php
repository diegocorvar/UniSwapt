<?php
require_once __DIR__ . '/php/includes/bootstrap.php';
requireLogin();

$userId = currentUserId();
$activeTab = $_GET['tab'] ?? 'publicaciones';

$publicationModel = new Publication();
$transactionModel = new Transaction();
$ratingModel = new Rating();

$myPublications = $publicationModel->findByUser($userId);
$allTransactions = $transactionModel->findByUser($userId);

$receivedProposals = array_filter($allTransactions, fn ($t) => (int) $t['seller_id'] === $userId && $t['transaction_status'] === 'Propuesta');
$activeAndPastTransactions = array_filter($allTransactions, fn ($t) => $t['transaction_status'] !== 'Propuesta' || (int) $t['buyer_id'] === $userId);
$myRatings = $ratingModel->findForUser($userId);

$alreadyRatedTransactionIds = [];
foreach ($activeAndPastTransactions as $transaction) {
    if ($transaction['transaction_status'] === 'Completada' && $ratingModel->alreadyRated((int) $transaction['id'], $userId)) {
        $alreadyRatedTransactionIds[] = (int) $transaction['id'];
    }
}

$pageTitle = 'Mi panel';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/dashboard.php';
require __DIR__ . '/html/partials/footer.php';
