<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$userModel = new User();
$requestedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isOwnProfile = !$requestedId || (isLoggedIn() && $requestedId === currentUserId());

if ($isOwnProfile) {
    requireLogin();
    $profileUser = $userModel->findById(currentUserId());
} else {
    $profileUser = $userModel->findById($requestedId);
    if (!$profileUser) {
        flashMessage('error', 'Ese usuario no existe.');
        redirectTo('index.php');
    }
}

$publicationModel = new Publication();
$profilePublications = array_filter(
    $publicationModel->findByUser((int) $profileUser['id']),
    fn ($p) => $p['publication_status'] === 'Disponible'
);

$ratingModel = new Rating();
$profileRatings = $ratingModel->findForUser((int) $profileUser['id']);

$pageTitle = $isOwnProfile ? 'Mi perfil' : $profileUser['full_name'];
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/profile.php';
require __DIR__ . '/html/partials/footer.php';
