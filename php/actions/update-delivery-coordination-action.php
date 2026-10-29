<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../../dashboard.php?tab=transacciones');
}

$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$meetingLocation = trim($_POST['meeting_location'] ?? '');
$meetingAtInput = trim($_POST['meeting_at'] ?? '');
$meetingNotes = trim($_POST['meeting_notes'] ?? '');

if (!in_array($meetingLocation, CAMPUS_MEETING_LOCATIONS, true)) {
    flashMessage('error', 'Selecciona un punto de encuentro valido dentro del campus.');
    redirectTo('../../dashboard.php?tab=transacciones');
}

$meetingAt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $meetingAtInput);
$dateErrors = DateTimeImmutable::getLastErrors();
$hasDateErrors = $dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);

if (!$meetingAt || $hasDateErrors || $meetingAt->format('Y-m-d\\TH:i') !== $meetingAtInput) {
    flashMessage('error', 'Indica una fecha y hora de encuentro validas.');
    redirectTo('../../dashboard.php?tab=transacciones');
}

if ($meetingAt <= new DateTimeImmutable()) {
    flashMessage('error', 'El encuentro debe programarse para una fecha y hora futuras.');
    redirectTo('../../dashboard.php?tab=transacciones');
}

if (strlen($meetingNotes) > 500) {
    flashMessage('error', 'Las notas del encuentro no pueden superar los 500 caracteres.');
    redirectTo('../../dashboard.php?tab=transacciones');
}

$transactionModel = new Transaction();
$result = $transactionModel->updateCoordination(
    $transactionId,
    currentUserId(),
    $meetingLocation,
    $meetingAt->format('Y-m-d H:i:s'),
    $meetingNotes !== '' ? $meetingNotes : null
);

if (!$result['success']) {
    $message = match ($result['reason']) {
        'sin_permiso' => 'No tienes permiso para coordinar esta transaccion.',
        'estado_invalido' => 'Solo puedes coordinar encuentros para transacciones aceptadas.',
        default => 'No fue posible guardar la coordinacion de entrega.',
    };
    flashMessage('error', $message);
    redirectTo('../../dashboard.php?tab=transacciones');
}

flashMessage('success', 'La coordinacion del encuentro fue guardada. Ambas partes ya pueden confirmar la entrega despues de reunirse.');
redirectTo('../../dashboard.php?tab=transacciones');
