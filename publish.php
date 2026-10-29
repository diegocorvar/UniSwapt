<?php
require_once __DIR__ . '/php/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Publicar material';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/publish.php';
require __DIR__ . '/html/partials/footer.php';
