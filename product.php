<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$publicationId = (int) ($_GET['id'] ?? 0);
$publicationModel = new Publication();
$publication = $publicationModel->findById($publicationId);

if (!$publication) {
    flashMessage('error', 'Esa publicacion ya no existe.');
    redirectTo('index.php');
}

$images = $publicationModel->getImages($publicationId);
$isOwner = isLoggedIn() && currentUserId() === (int) $publication['user_id'];

$pageTitle = $publication['title'];
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/product-detail.php';
require __DIR__ . '/html/partials/footer.php';
