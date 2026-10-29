<div class="card form-card" style="max-width:640px;">
    <div class="auth-header" style="text-align:left;">
        <span class="pin"></span>
        <h1>Publica un material</h1>
        <p class="text-muted">Comparte lo que ya no usas: alguien en tu campus probablemente lo necesita.</p>
    </div>

    <form method="post" action="php/actions/publish-action.php" enctype="multipart/form-data" data-role="publish-form">
        <div class="field-group">
            <label class="field-label" for="title">Titulo</label>
            <input class="field-input" type="text" id="title" name="title" placeholder="Ej. Calculadora cientifica Casio fx-991" required>
        </div>

        <div class="field-group">
            <label class="field-label" for="description">Descripcion</label>
            <textarea class="field-textarea" id="description" name="description" placeholder="Describe el estado, uso que le diste, accesorios incluidos, etc." required></textarea>
        </div>

        <div class="field-row">
            <div class="field-group">
                <label class="field-label" for="item_condition">Condicion</label>
                <select class="field-select" id="item_condition" name="item_condition" required>
                    <?php foreach (ITEM_CONDITIONS as $condition): ?>
                        <option value="<?= sanitizeText($condition) ?>"><?= sanitizeText($condition) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label" for="material_category">Tipo de material</label>
                <select class="field-select" id="material_category" name="material_category" required>
                    <?php foreach (MATERIAL_CATEGORIES as $category): ?>
                        <option value="<?= sanitizeText($category) ?>"><?= sanitizeText($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="career_category">Carrera relacionada</label>
            <select class="field-select" id="career_category" name="career_category" required>
                <?php foreach (ACADEMIC_CAREERS as $career): ?>
                    <option value="<?= sanitizeText($career) ?>"><?= sanitizeText($career) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field-group">
            <span class="field-label">Modalidad</span>
            <div class="radio-options">
                <?php foreach (TRANSACTION_MODALITIES as $index => $modality): ?>
                    <label class="radio-option">
                        <input type="radio" name="modality" value="<?= sanitizeText($modality) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                        <?= sanitizeText($modality) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="price">Precio (MXN)</label>
            <input class="field-input" type="number" id="price" name="price" min="1" step="0.5" placeholder="0.00">
            <p class="field-hint">Se deshabilita automaticamente en Trueque y Donacion.</p>
        </div>

        <div class="field-group" data-role="trade-interest-group" style="display:none;">
            <label class="field-label" for="trade_interest">Materiales de tu interes para intercambio (opcional)</label>
            <input class="field-input" type="text" id="trade_interest" name="trade_interest" placeholder="Ej. Busco un multimetro o libros de electronica">
        </div>

        <div class="field-group">
            <span class="field-label">Fotografias (1 a 5, JPG/PNG/WebP, max 5MB c/u)</span>
            <div class="image-upload-zone" data-role="image-drop-zone">
                <p style="margin:0;">Arrastra tus imagenes aqui o haz clic para seleccionarlas</p>
                <p class="field-hint" style="margin:0;">La primera imagen se usara como portada</p>
            </div>
            <input type="file" name="images[]" data-role="image-input" accept="image/jpeg,image/png,image/webp" multiple hidden>
            <p class="field-error" data-role="image-error"></p>
            <div class="image-preview-grid" data-role="image-preview-grid"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Publicar material</button>
    </form>
</div>

<script src="js/validation.js"></script>
<script src="js/image-upload.js"></script>
