<section class="hero">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="hero-tag">Solo comunidad ITESA</span>
            <h1>Dale una segunda vuelta a tus materiales de clase</h1>
            <p>Compra, vende, dona o intercambia libros, componentes y herramientas con otros estudiantes verificados de tu propio campus.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">Crear cuenta institucional</a>
            <?php else: ?>
                <a href="publish.php" class="btn btn-primary">Publicar un material</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div>
                <span class="hero-stat-number"><?= count($publications) ?></span>
                <span class="hero-stat-label">Publicaciones activas</span>
            </div>
            <div>
                <span class="hero-stat-number"><?= count(ACADEMIC_CAREERS) ?></span>
                <span class="hero-stat-label">Carreras participantes</span>
            </div>
        </div>
    </div>
</section>

<form class="filter-bar" method="get" action="index.php" data-role="filter-form">
    <div class="field-group" style="margin:0;">
        <label class="field-label" for="q">Buscar</label>
        <input class="field-input" type="text" id="q" name="q" placeholder="Ej. calculadora, libro de fisica..." value="<?= sanitizeText($filters['search_term']) ?>">
    </div>
    <div class="field-group" style="margin:0;">
        <label class="field-label" for="career_category">Carrera</label>
        <select class="field-select" id="career_category" name="career_category">
            <option value="">Todas</option>
            <?php foreach (ACADEMIC_CAREERS as $career): ?>
                <option value="<?= sanitizeText($career) ?>" <?= $filters['career_category'] === $career ? 'selected' : '' ?>><?= sanitizeText($career) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field-group" style="margin:0;">
        <label class="field-label" for="material_category">Tipo de material</label>
        <select class="field-select" id="material_category" name="material_category">
            <option value="">Todos</option>
            <?php foreach (MATERIAL_CATEGORIES as $category): ?>
                <option value="<?= sanitizeText($category) ?>" <?= $filters['material_category'] === $category ? 'selected' : '' ?>><?= sanitizeText($category) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field-group" style="margin:0;">
        <label class="field-label" for="modality">Modalidad</label>
        <select class="field-select" id="modality" name="modality">
            <option value="">Todas</option>
            <?php foreach (TRANSACTION_MODALITIES as $modality): ?>
                <option value="<?= sanitizeText($modality) ?>" <?= $filters['modality'] === $modality ? 'selected' : '' ?>><?= sanitizeText($modality) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filtrar</button>
</form>

<div class="section-heading">
    <h2>Publicaciones disponibles</h2>
</div>

<?php if (empty($publications)): ?>
    <div class="empty-state">
        <p>No hay publicaciones que coincidan con esos filtros todavia.</p>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($publications as $publication): ?>
            <article class="product-card">
                <div class="product-card-image">
                    <?php if ($publication['cover_image']): ?>
                        <img src="<?= sanitizeText(UPLOAD_URL_PRODUCTS . $publication['cover_image']) ?>" alt="<?= sanitizeText($publication['title']) ?>">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <span class="badge badge-modalidad"><?= sanitizeText($publication['modality']) ?></span>
                    <h3 class="product-card-title"><a href="product.php?id=<?= (int) $publication['id'] ?>"><?= sanitizeText($publication['title']) ?></a></h3>
                    <div class="product-card-price">
                        <?= $publication['modality'] === 'Venta' ? formatPrice((float) $publication['price']) : sanitizeText($publication['modality']) ?>
                    </div>
                    <div class="product-card-meta">
                        <?= sanitizeText($publication['career_category']) ?> · <?= sanitizeText($publication['item_condition']) ?>
                    </div>
                    <div class="product-card-meta">
                        Publicado por <?= sanitizeText($publication['seller_name']) ?>
                        <?php if ((float) $publication['reputation_avg'] > 0): ?>
                            · <span class="stars"><?= starsMarkup((float) $publication['reputation_avg']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
