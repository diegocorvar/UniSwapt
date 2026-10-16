<?php
$tabs = [
    'publicaciones' => 'Mis publicaciones (' . count($myPublications) . ')',
    'propuestas'    => 'Propuestas recibidas (' . count($receivedProposals) . ')',
    'transacciones' => 'Mis transacciones (' . count($activeAndPastTransactions) . ')',
    'valoraciones'  => 'Valoraciones recibidas (' . count($myRatings) . ')',
];

function statusBadgeFor(string $status): string
{
    return match ($status) {
        'Disponible' => 'badge-disponible',
        'En Proceso de Entrega' => 'badge-proceso',
        'Vendido/Intercambiado', 'Donado', 'Completada' => 'badge-cerrado',
        'Rechazada', 'Cancelada', 'Inactivo' => 'badge-inactivo',
        default => 'badge-modalidad',
    };
}
?>

<div class="section-heading">
    <h1>Mi panel</h1>
    <a href="publish.php" class="btn btn-primary">+ Nueva publicacion</a>
</div>

<nav class="dashboard-tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="dashboard-tab <?= $activeTab === $key ? 'is-active' : '' ?>" href="dashboard.php?tab=<?= $key ?>"><?= sanitizeText($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($activeTab === 'publicaciones'): ?>
    <?php if (empty($myPublications)): ?>
        <div class="empty-state">Aun no has publicado ningun material. <a href="publish.php">Publica el primero</a>.</div>
    <?php else: ?>
        <ul class="simple-list">
            <?php foreach ($myPublications as $publication): ?>
                <li class="simple-list-item">
                    <div>
                        <strong><a href="product.php?id=<?= (int) $publication['id'] ?>"><?= sanitizeText($publication['title']) ?></a></strong>
                        <div class="text-muted"><?= sanitizeText($publication['modality']) ?> · <?= formatDate($publication['created_at']) ?></div>
                    </div>
                    <span class="badge <?= statusBadgeFor($publication['publication_status']) ?>"><?= sanitizeText($publication['publication_status']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php elseif ($activeTab === 'propuestas'): ?>
    <?php if (empty($receivedProposals)): ?>
        <div class="empty-state">No tienes propuestas pendientes por responder.</div>
    <?php else: ?>
        <ul class="simple-list">
            <?php foreach ($receivedProposals as $proposal): ?>
                <li class="simple-list-item">
                    <div>
                        <strong><?= sanitizeText($proposal['publication_title']) ?></strong>
                        <div class="text-muted">
                            Propuesta de <?= sanitizeText($proposal['buyer_name']) ?> · <?= sanitizeText($proposal['modality']) ?>
                            <?php if ($proposal['offer_amount']): ?> · Oferta: <?= formatPrice((float) $proposal['offer_amount']) ?><?php endif; ?>
                        </div>
                    </div>
                    <form method="post" action="php/actions/respond-transaction-action.php" style="display:flex; gap: var(--space-2);">
                        <input type="hidden" name="transaction_id" value="<?= (int) $proposal['id'] ?>">
                        <button class="btn btn-success btn-sm" name="decision" value="aceptar">Aceptar</button>
                        <button class="btn btn-danger btn-sm" name="decision" value="rechazar">Rechazar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php elseif ($activeTab === 'transacciones'): ?>
    <?php if (empty($activeAndPastTransactions)): ?>
        <div class="empty-state">Todavia no tienes transacciones en curso.</div>
    <?php else: ?>
        <ul class="simple-list">
            <?php foreach ($activeAndPastTransactions as $transaction): ?>
                <?php
                    $isBuyer = (int) $transaction['buyer_id'] === $userId;
                    $counterpart = $isBuyer ? $transaction['seller_name'] : $transaction['buyer_name'];
                    $myConfirmation = $isBuyer ? $transaction['buyer_confirmed'] : $transaction['seller_confirmed'];
                ?>
                <li class="simple-list-item">
                    <div>
                        <strong><?= sanitizeText($transaction['publication_title']) ?></strong>
                        <div class="text-muted">
                            <?= $isBuyer ? 'Como comprador, con' : 'Como vendedor/donante, con' ?> <?= sanitizeText($counterpart) ?>
                        </div>
                    </div>
                    <span class="badge <?= statusBadgeFor($transaction['transaction_status']) ?>"><?= sanitizeText($transaction['transaction_status']) ?></span>

                    <?php if ($transaction['transaction_status'] === 'Aceptada'): ?>
                        <form method="post" action="php/actions/confirm-delivery-action.php" style="display:flex; gap: var(--space-2);">
                            <input type="hidden" name="transaction_id" value="<?= (int) $transaction['id'] ?>">
                            <button class="btn btn-success btn-sm" name="delivery_action" value="confirmar" <?= $myConfirmation ? 'disabled' : '' ?>>
                                <?= $myConfirmation ? 'Ya confirmaste' : 'Confirmar entrega' ?>
                            </button>
                            <button class="btn btn-danger btn-sm" name="delivery_action" value="cancelar">Cancelar</button>
                        </form>
                    <?php elseif ($transaction['transaction_status'] === 'Completada' && !in_array((int) $transaction['id'], $alreadyRatedTransactionIds, true)): ?>
                        <a class="btn btn-secondary btn-sm" href="rate.php?transaction_id=<?= (int) $transaction['id'] ?>">Calificar</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php elseif ($activeTab === 'valoraciones'): ?>
    <?php if (empty($myRatings)): ?>
        <div class="empty-state">Aun no has recibido valoraciones.</div>
    <?php else: ?>
        <ul class="simple-list">
            <?php foreach ($myRatings as $rating): ?>
                <li class="simple-list-item" style="align-items:flex-start;">
                    <div>
                        <span class="stars"><?= starsMarkup((float) $rating['stars']) ?></span>
                        <strong> — <?= sanitizeText($rating['rater_name']) ?></strong>
                        <?php if ($rating['comment']): ?><p class="text-muted" style="margin-top:var(--space-1);"><?= sanitizeText($rating['comment']) ?></p><?php endif; ?>
                    </div>
                    <span class="text-muted"><?= formatDate($rating['created_at']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
