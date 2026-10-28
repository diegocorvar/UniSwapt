<?php
$isVerification = $pendingAction['mode'] === 'verificacion';
?>
<div class="auth-shell">
    <div class="card form-card" style="text-align:center;">
        <span class="pin"></span>
        <h1><?= $isVerification ? 'Confirma tu correo' : 'Revisa tu correo' ?></h1>
        <p class="text-muted">
            <?= $isVerification
                ? 'Enviamos un enlace de verificacion a '
                : 'Si el correo existe en nuestros registros, enviamos instrucciones a ' ?>
            <strong><?= sanitizeText($pendingAction['email']) ?></strong>.
        </p>

        <div class="simulated-mail-banner" style="text-align:left; margin-top: var(--space-5);">
            <strong>Nota del prototipo:</strong> como esta version corre en local y no tiene un servidor
            SMTP real conectado, el correo no llega a una bandeja externa. En su lugar, quedo guardado
            aqui mismo para que puedas revisarlo, tal como lo haria el proveedor de correo en produccion.
            <div style="margin-top: var(--space-3); display:flex; gap: var(--space-3); flex-wrap:wrap;">
                <a class="btn btn-secondary btn-sm" href="<?= sanitizeText($pendingAction['inbox_path']) ?>" target="_blank">Ver correo simulado</a>
                <a class="btn btn-primary btn-sm" href="<?= sanitizeText($pendingAction['direct_link']) ?>">Abrir el enlace directamente</a>
            </div>
        </div>
    </div>
</div>
<?php unset($_SESSION['pending_email_action']); ?>
