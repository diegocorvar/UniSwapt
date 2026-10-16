<?php
/** @var string $pageTitle definido por cada vista antes de incluir este partial */
$pageTitle = $pageTitle ?? 'UniMarket';
$navUser = isLoggedIn() ? (new User())->findById(currentUserId()) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeText($pageTitle) ?> · UniMarket</title>
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/pages.css">
</head>
<body>
<header class="navbar">
    <div class="container navbar-inner">
        <a href="index.php" class="navbar-brand">Uni<span>Market</span></a>
        <nav>
            <ul class="navbar-links">
                <li><a href="index.php">Catalogo</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="publish.php">Publicar</a></li>
                    <li><a href="dashboard.php">Mi panel</a></li>
                    <?php if ($navUser && $navUser['role'] === 'administrador'): ?>
                        <li><a href="admin.php">Moderacion</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="navbar-user">
            <?php if (isLoggedIn() && $navUser): ?>
                <a href="profile.php" class="navbar-user" style="text-decoration:none;">
                    <img class="avatar-chip" src="<?= $navUser['profile_photo'] ? sanitizeText(UPLOAD_URL_PROFILES . $navUser['profile_photo']) : 'assets/images/default-avatar.svg' ?>" alt="">
                    <span><?= sanitizeText($navUser['full_name']) ?></span>
                </a>
                <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesion</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary btn-sm">Iniciar sesion</a>
                <a href="register.php" class="btn btn-primary btn-sm">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="page-main">
    <div class="container">
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitizeText($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'success')) ?>">
                <?= sanitizeText($flash['message']) ?>
            </div>
        <?php endif; ?>
