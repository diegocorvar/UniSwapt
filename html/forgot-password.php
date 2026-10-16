<div class="auth-shell">
    <div class="card form-card">
        <div class="auth-header">
            <span class="pin"></span>
            <h1>¿Olvidaste tu contrasena?</h1>
            <p class="text-muted">Ingresa tu correo institucional y te enviaremos instrucciones.</p>
        </div>

        <form method="post" action="php/actions/forgot-password-action.php">
            <div class="field-group">
                <label class="field-label" for="email">Correo institucional</label>
                <input class="field-input" type="email" id="email" name="email" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Enviar instrucciones</button>
        </form>

        <p class="auth-footer-link"><a href="login.php">Volver a inicio de sesion</a></p>
    </div>
</div>
