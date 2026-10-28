<div class="auth-shell">
    <div class="card form-card">
        <div class="auth-header">
            <span class="pin"></span>
            <h1>Restablece tu contrasena</h1>
        </div>

        <?php if (!$tokenRow): ?>
            <div class="alert alert-error">Este enlace expiro o no es valido. Solicita uno nuevo.</div>
            <p class="auth-footer-link"><a href="forgot-password.php">Solicitar nuevo enlace</a></p>
        <?php else: ?>
            <form method="post" action="php/actions/reset-password-action.php">
                <input type="hidden" name="token" value="<?= sanitizeText($token) ?>">
                <div class="field-group">
                    <label class="field-label" for="password">Nueva contrasena</label>
                    <input class="field-input" type="password" id="password" name="password" minlength="8" required autofocus>
                </div>
                <div class="field-group">
                    <label class="field-label" for="password_confirm">Confirmar contrasena</label>
                    <input class="field-input" type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar nueva contrasena</button>
            </form>
        <?php endif; ?>
    </div>
</div>
