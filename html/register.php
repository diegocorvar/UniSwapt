<div class="auth-shell">
    <div class="card form-card">
        <div class="auth-header">
            <span class="pin"></span>
            <h1>Crea tu cuenta UniSwapt</h1>
            <p class="text-muted">Reservado para la comunidad ITESA. Usa tu correo institucional.</p>
        </div>

        <form method="post" action="php/actions/register-action.php" data-role="register-form" novalidate>
            <div class="field-group">
                <label class="field-label" for="full_name">Nombre completo</label>
                <input class="field-input" type="text" id="full_name" name="full_name" required>
            </div>

            <div class="field-group">
                <label class="field-label" for="email">Correo institucional</label>
                <input class="field-input" type="email" id="email" name="email" placeholder="nombre.apellido@itesa.edu.mx" required>
                <p class="field-hint">Debe terminar en <?= sanitizeText(INSTITUTIONAL_EMAIL_DOMAIN) ?></p>
                <p class="field-error" id="email-error"></p>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <label class="field-label" for="password">Contrasena</label>
                    <input class="field-input" type="password" id="password" name="password" minlength="8" required>
                </div>
                <div class="field-group">
                    <label class="field-label" for="password_confirm">Confirmar contrasena</label>
                    <input class="field-input" type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                </div>
            </div>
            <p class="field-error" id="confirm-error"></p>

            <button type="submit" class="btn btn-primary btn-block">Registrarme</button>
        </form>

        <p class="auth-footer-link">¿Ya tienes cuenta? <a href="login.php">Inicia sesion</a></p>
    </div>
</div>

<script src="js/validation.js"></script>
