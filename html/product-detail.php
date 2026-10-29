<?php
$statusBadgeClass = match ($publication['publication_status']) {
    'Disponible' => 'badge-disponible',
    'En Proceso de Entrega' => 'badge-proceso',
    'Vendido/Intercambiado', 'Donado' => 'badge-cerrado',
    default => 'badge-inactivo',
};
?>
<div class="product-detail-grid">
    <div>
        <div class="gallery-main">
            <?php if (!empty($images)): ?>
                <img id="gallery-main-image" src="<?= sanitizeText(UPLOAD_URL_PRODUCTS . $images[0]['image_path']) ?>" alt="<?= sanitizeText($publication['title']) ?>">
            <?php else: ?>
                <div class="empty-state">Sin imagenes</div>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $index => $image): ?>
                    <img src="<?= sanitizeText(UPLOAD_URL_PRODUCTS . $image['image_path']) ?>" class="<?= $index === 0 ? 'is-active' : '' ?>" onclick="document.getElementById('gallery-main-image').src=this.src; document.querySelectorAll('.gallery-thumbs img').forEach(t=>t.classList.remove('is-active')); this.classList.add('is-active');">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <span class="badge badge-modalidad"><?= sanitizeText($publication['modality']) ?></span>
        <span class="badge <?= $statusBadgeClass ?>"><?= sanitizeText($publication['publication_status']) ?></span>

        <h1 style="margin-top: var(--space-3);"><?= sanitizeText($publication['title']) ?></h1>

        <p class="product-card-price" style="font-size:1.4rem;">
            <?= $publication['modality'] === 'Venta' ? formatPrice((float) $publication['price']) : sanitizeText($publication['modality']) ?>
        </p>

        <p><?= nl2br(sanitizeText($publication['description'])) ?></p>

        <p class="text-muted">
            <?= sanitizeText($publication['career_category']) ?> · <?= sanitizeText($publication['material_category']) ?> · <?= sanitizeText($publication['item_condition']) ?>
        </p>

        <?php if ($publication['modality'] === 'Trueque' && !empty($publication['trade_interest'])): ?>
            <p><strong>Le interesa a cambio:</strong> <?= sanitizeText($publication['trade_interest']) ?></p>
        <?php endif; ?>

        <div class="seller-card" style="margin: var(--space-5) 0;">
            <img class="avatar-chip" style="width:44px;height:44px;" src="assets/images/default-avatar.svg" alt="">
            <div>
                <strong><a href="profile.php?id=<?= (int) $publication['user_id'] ?>"><?= sanitizeText($publication['seller_name']) ?></a></strong>
                <div>
                    <?php if ((float) $publication['reputation_avg'] > 0): ?>
                        <span class="stars"><?= starsMarkup((float) $publication['reputation_avg']) ?></span>
                        <span class="text-muted"> (<?= (int) $publication['reputation_count'] ?> valoraciones)</span>
                    <?php else: ?>
                        <span class="text-muted">Sin valoraciones aun</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($isOwner): ?>
            <div class="card" style="background: var(--color-surface-alt);">
                <h3>Gestionar publicacion</h3>
                <form method="post" action="php/actions/update-status-action.php" class="field-row" style="align-items:end;">
                    <input type="hidden" name="publication_id" value="<?= (int) $publication['id'] ?>">
                    <div class="field-group" style="margin:0; flex:2;">
                        <label class="field-label" for="publication_status">Estado</label>
                        <select class="field-select" id="publication_status" name="publication_status">
                            <?php foreach (PUBLICATION_STATUSES as $status): ?>
                                <option value="<?= sanitizeText($status) ?>" <?= $publication['publication_status'] === $status ? 'selected' : '' ?>><?= sanitizeText($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Actualizar</button>
                </form>
            </div>
        <?php elseif (isLoggedIn()): ?>
            <?php if ($publication['publication_status'] === 'Disponible'): ?>
                <form method="post" action="php/actions/propose-transaction-action.php" class="card" style="margin-bottom: var(--space-4);">
                    <input type="hidden" name="publication_id" value="<?= (int) $publication['id'] ?>">
                    <h3>Enviar propuesta</h3>
                    <?php if ($publication['modality'] === 'Venta'): ?>
                        <div class="field-group">
                            <label class="field-label" for="offer_amount">Tu oferta (opcional, deja vacio para pagar el precio publicado)</label>
                            <input class="field-input" type="number" min="1" step="0.5" id="offer_amount" name="offer_amount" placeholder="<?= (float) $publication['price'] ?>">
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-block">
                        <?= $publication['modality'] === 'Donacion' ? 'Solicitar donacion' : ($publication['modality'] === 'Trueque' ? 'Proponer trueque' : 'Enviar propuesta de compra') ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!empty($publication['whatsapp_phone'])): ?>
                <a class="btn btn-success btn-block" style="margin-bottom: var(--space-4);" target="_blank"
                   href="<?= sanitizeText(buildWhatsAppLink($publication['whatsapp_phone'], $publication['title'])) ?>">
                    Contactar vía WhatsApp
                </a>
            <?php endif; ?>

            <details>
                <summary class="text-muted" style="cursor:pointer;">Reportar esta publicacion</summary>
                <form method="post" action="php/actions/report-action.php" style="margin-top: var(--space-3);">
                    <input type="hidden" name="target_type" value="publicacion">
                    <input type="hidden" name="target_id" value="<?= (int) $publication['id'] ?>">
                    <div class="field-group">
                        <label class="field-label" for="reason">Motivo</label>
                        <textarea class="field-textarea" id="reason" name="reason" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">Enviar reporte</button>
                </form>
            </details>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-block">Inicia sesion para contactar al vendedor</a>
        <?php endif; ?>
    </div>
</div>
