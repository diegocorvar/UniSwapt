<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$currentUser = (new User())->findById(currentUserId());
if (!$currentUser || $currentUser['role'] !== 'administrador') {
    flashMessage('error', 'No tienes permisos de administrador.');
    redirectTo('../../index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../admin.php');
}

$reportId = (int) ($_POST['report_id'] ?? 0);
$newStatus = $_POST['report_status'] ?? '';

if (!in_array($newStatus, ['Pendiente', 'En revision', 'Resuelto', 'Descartado'], true)) {
    flashMessage('error', 'Estado de reporte no valido.');
    redirectTo('../../admin.php');
}

(new Rating())->updateReportStatus($reportId, $newStatus);
flashMessage('success', 'El reporte fue actualizado.');
redirectTo('../../admin.php');
