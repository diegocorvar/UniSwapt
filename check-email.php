<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$pendingAction = $_SESSION['pending_email_action'] ?? null;

if (!$pendingAction) {
    redirectTo('login.php');
}

$pageTitle = 'Revisa tu correo';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/check-email.php';
require __DIR__ . '/html/partials/footer.php';
