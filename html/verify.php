<div class="auth-shell">
    <div class="card form-card" style="text-align:center;">
        <?php if ($tokenRow): ?>
            <span class="pin"></span>
            <h1>¡Cuenta verificada!</h1>
            <p class="text-muted">Tu cuenta institucional fue activada correctamente. Ya puedes iniciar sesion y comenzar a publicar o buscar materiales.</p>
            <a href="login.php" class="btn btn-primary btn-block">Iniciar sesion</a>
        <?php else: ?>
            <span class="pin"></span>
            <h1>Enlace expirado o invalido</h1>
            <p class="text-muted">Este enlace de verificacion ya caduco o fue alterado. Puedes solicitar uno nuevo registrandote de nuevo o contactando a soporte.</p>
            <a href="register.php" class="btn btn-secondary btn-block">Volver a registro</a>
        <?php endif; ?>
    </div>
</div>
