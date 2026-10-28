<div class="profile-header">
    <img class="profile-photo-lg" src="<?= $profileUser['profile_photo'] ? sanitizeText(UPLOAD_URL_PROFILES . $profileUser['profile_photo']) : 'assets/images/default-avatar.svg' ?>" alt="">
    <div>
        <h1 style="margin-bottom:4px;"><?= sanitizeText($profileUser['full_name']) ?></h1>
        <p class="text-muted" style="margin-bottom:4px;">
            <?= sanitizeText($profileUser['career'] ?: 'Carrera sin definir') ?>
            <?= $profileUser['semester'] ? ' · Semestre ' . sanitizeText($profileUser['semester']) : '' ?>
        </p>
        <?php if ((float) $profileUser['reputation_avg'] > 0): ?>
            <span class="stars"><?= starsMarkup((float) $profileUser['reputation_avg']) ?></span>
            <span class="text-muted"> (<?= (int) $profileUser['reputation_count'] ?> valoraciones)</span>
        <?php else: ?>
            <span class="text-muted">Sin valoraciones aun</span>
        <?php endif; ?>
    </div>
</div>

<?php if ($isOwnProfile): ?>
    <div class="card form-card" style="max-width:520px; margin-bottom: var(--space-7);">
        <h2>Editar perfil</h2>
        <form method="post" action="php/actions/update-profile-action.php" enctype="multipart/form-data">
            <div class="field-group">
                <label class="field-label" for="career">Carrera</label>
                <select class="field-select" id="career" name="career" required>
                    <?php foreach (ACADEMIC_CAREERS as $career): ?>
                        <option value="<?= sanitizeText($career) ?>" <?= $profileUser['career'] === $career ? 'selected' : '' ?>><?= sanitizeText($career) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label" for="semester">Semestre o condicion (ej. "6" o "Egresado")</label>
                <input class="field-input" type="text" id="semester" name="semester" value="<?= sanitizeText($profileUser['semester'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label class="field-label" for="whatsapp_phone">Numero de WhatsApp (10 digitos, para que te contacten)</label>
                <input class="field-input" type="text" id="whatsapp_phone" name="whatsapp_phone" value="<?= sanitizeText($profileUser['whatsapp_phone'] ?? '') ?>" placeholder="2461234567">
            </div>
            <div class="field-group">
                <label class="field-label" for="profile_photo">Foto de perfil (JPG/PNG, max 2MB)</label>
                <input class="field-input" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
        </form>
    </div>
<?php elseif (isLoggedIn()): ?>
    <details style="margin-bottom: var(--space-6);">
        <summary class="text-muted" style="cursor:pointer;">Reportar a este usuario</summary>
        <form method="post" action="php/actions/report-action.php" class="card" style="max-width:480px; margin-top: var(--space-3);">
            <input type="hidden" name="target_type" value="usuario">
            <input type="hidden" name="target_id" value="<?= (int) $profileUser['id'] ?>">
            <div class="field-group">
                <label class="field-label" for="reason">Motivo</label>
                <textarea class="field-textarea" id="reason" name="reason" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger btn-sm">Enviar reporte</button>
        </form>
    </details>
<?php endif; ?>

<div class="section-heading"><h2>Publicaciones activas</h2></div>
<?php if (empty($profilePublications)): ?>
    <p class="text-muted">Sin publicaciones activas por el momento.</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($profilePublications as $publication): ?>
            <article class="product-card">
                <div class="product-card-image">
                    <?php if ($publication['cover_image']): ?>
                        <img src="<?= sanitizeText(UPLOAD_URL_PRODUCTS . $publication['cover_image']) ?>" alt="">
                    <?php else: ?><span>Sin imagen</span><?php endif; ?>
                </div>
                <div class="product-card-body">
                    <h3 class="product-card-title"><a href="product.php?id=<?= (int) $publication['id'] ?>"><?= sanitizeText($publication['title']) ?></a></h3>
                    <div class="product-card-price"><?= $publication['modality'] === 'Venta' ? formatPrice((float) $publication['price']) : sanitizeText($publication['modality']) ?></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-heading" style="margin-top: var(--space-7);"><h2>Valoraciones recibidas</h2></div>
<?php if (empty($profileRatings)): ?>
    <p class="text-muted">Sin valoraciones todavia.</p>
<?php else: ?>
    <ul class="simple-list">
        <?php foreach ($profileRatings as $rating): ?>
            <li class="simple-list-item" style="align-items:flex-start;">
                <div>
                    <span class="stars"><?= starsMarkup((float) $rating['stars']) ?></span>
                    <strong> — <?= sanitizeText($rating['rater_name']) ?></strong>
                    <?php if ($rating['comment']): ?><p class="text-muted" style="margin-top:var(--space-1);"><?= sanitizeText($rating['comment']) ?></p><?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
