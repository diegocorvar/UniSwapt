<div class="card form-card">
    <div class="auth-header" style="text-align:left;">
        <span class="pin"></span>
        <h1>Califica a <?= sanitizeText($counterpart['full_name']) ?></h1>
        <p class="text-muted">Sobre la transaccion de "<?= sanitizeText($publication['title']) ?>"</p>
    </div>

    <form method="post" action="php/actions/rate-action.php">
        <input type="hidden" name="transaction_id" value="<?= (int) $transaction['id'] ?>">

        <div class="field-group">
            <span class="field-label">Calificacion</span>
            <div data-role="star-rating-widget" style="font-size:2rem; cursor:pointer;">
                <input type="hidden" name="stars" value="0">
                <span data-star-value="1">☆</span><span data-star-value="2">☆</span><span data-star-value="3">☆</span><span data-star-value="4">☆</span><span data-star-value="5">☆</span>
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="comment">Comentario (opcional)</label>
            <textarea class="field-textarea" id="comment" name="comment" placeholder="¿Como fue la experiencia?"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Enviar calificacion</button>
    </form>
</div>

<script src="js/star-rating.js"></script>
