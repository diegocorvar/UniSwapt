<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../index.php');
}

$publicationId = (int) ($_POST['publication_id'] ?? 0);
$offerAmount = isset($_POST['offer_amount']) && $_POST['offer_amount'] !== '' ? (float) $_POST['offer_amount'] : null;

$publicationModel = new Publication();
$publication = $publicationModel->findById($publicationId);

if (!$publication || $publication['publication_status'] !== 'Disponible') {
    flashMessage('error', 'Esta publicacion ya no esta disponible.');
    redirectTo('../../product.php?id=' . $publicationId);
}

if ((int) $publication['user_id'] === currentUserId()) {
    flashMessage('error', 'No puedes enviarte una propuesta a ti mismo.');
    redirectTo('../../product.php?id=' . $publicationId);
}

$transactionModel = new Transaction();
$transactionModel->createProposal(
    $publicationId,
    currentUserId(),
    (int) $publication['user_id'],
    $publication['modality'],
    $offerAmount
);

flashMessage('success', 'Tu propuesta fue enviada al vendedor/donante.');
redirectTo('../../product.php?id=' . $publicationId);
