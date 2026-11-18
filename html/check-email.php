<?php
$mode = $pendingAction['mode'] ?? 'verificacion';
$safeEmail = '<strong>' . sanitizeText($pendingAction['email'] ?? '') . '</strong>';
$isSimulatedDelivery = !empty($pendingAction['inbox_path']);

// Los textos de restablecimiento y reenvio son genericos a proposito: no deben
// revelar si el correo pertenece a una cuenta (ni si esta pendiente).
[$heading, $message] = match ($mode) {
    'restablecimiento' => [
        'Revisa tu correo',
        "Si el correo existe en nuestros registros, enviamos instrucciones a {$safeEmail}. El enlace vence en "
            . PASSWORD_RESET_TTL_HOURS . ' hora(s).',
    ],
    'reenvio' => [
        'Revisa tu correo',
        "Si {$safeEmail} corresponde a una cuenta pendiente de verificacion, enviamos un nuevo enlace de activacion. "
            . 'Los enlaces anteriores dejaron de funcionar.',
    ],
    default => [
        'Confirma tu correo',
        "Enviamos un enlace de verificacion a {$safeEmail}. El enlace vence en "
            . VERIFICATION_TOKEN_TTL_HOURS . ' horas.',
    ],
};
?>
<div class="auth-shell">
    <div class="card form-card" style="text-align:center;">
        <span class="pin"></span>
        <h1><?= $heading ?></h1>
        <p class="text-muted"><?= $message ?></p>

        <?php if ($isSimulatedDelivery): ?>
            <div class="simulated-mail-banner" style="text-align:left; margin-top: var(--space-5);">
                <strong>Modo local:</strong> no hay un servicio de correo configurado (MAIL_API_KEY), asi que el
                correo no salio a una bandeja externa. Quedo guardado aqui mismo para que puedas revisarlo.
                <div style="margin-top: var(--space-3); display:flex; gap: var(--space-3); flex-wrap:wrap;">
                    <a class="btn btn-secondary btn-sm" href="<?= sanitizeText($pendingAction['inbox_path']) ?>" target="_blank">Ver correo simulado</a>
                    <?php if (!empty($pendingAction['direct_link'])): ?>
                        <a class="btn btn-primary btn-sm" href="<?= sanitizeText($pendingAction['direct_link']) ?>">Abrir el enlace directamente</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted" style="margin-top: var(--space-4);">
                Revisa tambien tu carpeta de spam o correo no deseado.
            </p>
        <?php endif; ?>

        <?php if ($mode !== 'restablecimiento'): ?>
            <p class="auth-footer-link"><a href="resend-verification.php">¿No te llego? Solicita un nuevo enlace</a></p>
        <?php endif; ?>
        <p class="auth-footer-link"><a href="login.php">Ir a inicio de sesion</a></p>
    </div>
</div>
<?php unset($_SESSION['pending_email_action']); ?>
