<?php
require_once __DIR__ . '/php/includes/bootstrap.php';
requireLogin();

$currentUser = (new User())->findById(currentUserId());
if (!$currentUser || $currentUser['role'] !== 'administrador') {
    flashMessage('error', 'No tienes permisos de administrador.');
    redirectTo('index.php');
}

$reports = (new Rating())->listReports();

$pageTitle = 'Panel de moderacion';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/admin.php';
require __DIR__ . '/html/partials/footer.php';
