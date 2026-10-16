<div class="auth-shell">
    <div class="card form-card">
        <div class="auth-header">
            <span class="pin"></span>
            <h1>Inicia sesion</h1>
            <p class="text-muted">Accede con tu correo institucional y contrasena.</p>
        </div>

        <form method="post" action="php/actions/login-action.php">
            <div class="field-group">
                <label class="field-label" for="email">Correo institucional</label>
                <input class="field-input" type="email" id="email" name="email" required autofocus>
            </div>
            <div class="field-group">
                <label class="field-label" for="password">Contrasena</label>
                <input class="field-input" type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>

        <p class="auth-footer-link"><a href="forgot-password.php">¿Olvidaste tu contrasena?</a></p>
        <p class="auth-footer-link">¿Aun no tienes cuenta? <a href="register.php">Registrate</a></p>
    </div>
</div>
