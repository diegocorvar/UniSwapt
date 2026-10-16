<?php
require_once __DIR__ . '/php/includes/bootstrap.php';

$filters = [
    'career_category'   => $_GET['career_category'] ?? '',
    'material_category' => $_GET['material_category'] ?? '',
    'modality'          => $_GET['modality'] ?? '',
    'search_term'       => $_GET['q'] ?? '',
];

$publicationModel = new Publication();
$publications = $publicationModel->search($filters);

$pageTitle = 'Catalogo';
require __DIR__ . '/html/partials/header.php';
require __DIR__ . '/html/catalog.php';
require __DIR__ . '/html/partials/footer.php';
