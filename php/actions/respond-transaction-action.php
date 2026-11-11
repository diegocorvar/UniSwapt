<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php');
}

requireValidCsrfToken('../../dashboard.php');

$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$decision = $_POST['decision'] ?? '';

$transactionModel = new Transaction();
$transaction = $transactionModel->findById($transactionId);

if (!$transaction || (int) $transaction['seller_id'] !== currentUserId()) {
    flashMessage('error', 'No tienes permiso para responder a esta propuesta.');
    redirectTo('../../dashboard.php');
}

// Solo se puede responder a propuestas pendientes (evita reaceptar una propuesta
// rechazada, cancelada o completada reenviando el formulario).
if ($transaction['transaction_status'] !== 'Propuesta') {
    flashMessage('error', 'Esta propuesta ya fue respondida o ya no esta vigente.');
    redirectTo('../../dashboard.php?tab=propuestas');
}

$publicationModel = new Publication();
$publicationId = (int) $transaction['publication_id'];

if ($decision === 'aceptar') {
    $publication = $publicationModel->findById($publicationId);

    if (!$publication || $publication['publication_status'] !== 'Disponible') {
        flashMessage('error', 'La publicacion ya no esta disponible; no es posible aceptar esta propuesta.');
        redirectTo('../../dashboard.php?tab=propuestas');
    }

    // Reservar, aceptar y rechazar al resto debe ocurrir de forma atomica: las
    // sentencias UPDATE condicionadas al estado actual evitan aceptar dos
    // propuestas de la misma publicacion aunque lleguen peticiones simultaneas.
    $db = Database::getConnection();
    $db->beginTransaction();
    try {
        $accepted = $publicationModel->markAsReserved($publicationId)
            && $transactionModel->accept($transactionId);

        if (!$accepted) {
            $db->rollBack();
            flashMessage('error', 'No fue posible aceptar la propuesta: su estado o el de la publicacion cambio.');
            redirectTo('../../dashboard.php?tab=propuestas');
        }

        $rejectedCount = $transactionModel->rejectOtherPendingProposals($publicationId, $transactionId);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }

    $message = 'Propuesta aceptada. El articulo paso a "En Proceso de Entrega".';
    if ($rejectedCount > 0) {
        $message .= ' Las otras ' . $rejectedCount . ' propuesta(s) pendientes se rechazaron automaticamente.';
    }
    flashMessage('success', $message);
} elseif ($decision === 'rechazar') {
    // Flujo 01, alterno A1: la publicacion permanece disponible.
    $rejected = $transactionModel->reject($transactionId);
    flashMessage($rejected ? 'info' : 'error', $rejected
        ? 'Propuesta rechazada.'
        : 'Esta propuesta ya fue respondida o ya no esta vigente.');
} else {
    flashMessage('error', 'Accion no reconocida.');
}

redirectTo('../../dashboard.php?tab=propuestas');
