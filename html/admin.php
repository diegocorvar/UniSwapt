<h1>Panel de moderacion</h1>
<p class="text-muted">Reportes enviados por la comunidad sobre usuarios o publicaciones.</p>

<?php if (empty($reports)): ?>
    <div class="empty-state">No hay reportes registrados.</div>
<?php else: ?>
    <ul class="simple-list">
        <?php foreach ($reports as $report): ?>
            <li class="simple-list-item" style="align-items:flex-start;">
                <div>
                    <strong><?= sanitizeText(ucfirst($report['target_type'])) ?> #<?= (int) $report['target_id'] ?></strong>
                    <p class="text-muted" style="margin: var(--space-1) 0;">Reportado por <?= sanitizeText($report['reporter_name']) ?> · <?= formatDate($report['created_at']) ?></p>
                    <p style="margin:0;"><?= sanitizeText($report['reason']) ?></p>
                    <?php if ($report['evidence']): ?><p class="text-muted" style="margin:0;">Evidencia: <?= sanitizeText($report['evidence']) ?></p><?php endif; ?>
                </div>
                <form method="post" action="php/actions/update-report-status-action.php" style="display:flex; gap: var(--space-2); align-items:center;">
                    <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                    <select class="field-select" name="report_status">
                        <?php foreach (['Pendiente', 'En revision', 'Resuelto', 'Descartado'] as $status): ?>
                            <option value="<?= $status ?>" <?= $report['report_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
