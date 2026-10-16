<?php
/**
 * Implementa el Flujo de Trabajo 01 (Negociacion) y el Flujo de Trabajo 02
 * (Coordinacion de entrega y cierre). Simplificado para el prototipo:
 * una publicacion "En Proceso" solo permite una transaccion activa.
 */
class Transaction
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Flujo 01, paso 1-2: el comprador envia una propuesta. */
    public function createProposal(int $publicationId, int $buyerId, int $sellerId, string $modality, ?float $offerAmount): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions (publication_id, buyer_id, seller_id, modality, offer_amount, transaction_status)
             VALUES (:pub_id, :buyer_id, :seller_id, :modality, :offer_amount, :status)'
        );
        $stmt->execute([
            'pub_id'       => $publicationId,
            'buyer_id'     => $buyerId,
            'seller_id'    => $sellerId,
            'modality'     => $modality,
            'offer_amount' => $offerAmount,
            'status'       => 'Propuesta',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Flujo 01, paso 4-5: el vendedor acepta y el articulo pasa a "En Proceso". */
    public function accept(int $transactionId): void
    {
        $stmt = $this->db->prepare("UPDATE transactions SET transaction_status = 'Aceptada' WHERE id = :id");
        $stmt->execute(['id' => $transactionId]);
    }

    /** Flujo 01, alterno A1: el vendedor rechaza y la publicacion sigue disponible. */
    public function reject(int $transactionId): void
    {
        $stmt = $this->db->prepare("UPDATE transactions SET transaction_status = 'Rechazada' WHERE id = :id");
        $stmt->execute(['id' => $transactionId]);
    }

    /** Flujo 02, excepcion E2: cualquiera de las partes cancela. */
    public function cancel(int $transactionId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions
             SET transaction_status = 'Cancelada', coordination_status = 'Cancelada'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $transactionId]);
    }

    /**
     * Flujo 02, paso 1-3: cualquiera de las partes propone o ajusta el
     * encuentro presencial antes de confirmar la entrega.
     */
    public function updateCoordination(
        int $transactionId,
        int $userId,
        string $meetingLocation,
        string $meetingAt,
        ?string $meetingNotes
    ): array {
        $transaction = $this->findById($transactionId);
        if (!$transaction) {
            return ['success' => false, 'reason' => 'transaccion_inexistente'];
        }

        $isParticipant = in_array($userId, [(int) $transaction['buyer_id'], (int) $transaction['seller_id']], true);
        if (!$isParticipant) {
            return ['success' => false, 'reason' => 'sin_permiso'];
        }

        if ($transaction['transaction_status'] !== 'Aceptada') {
            return ['success' => false, 'reason' => 'estado_invalido'];
        }

        $stmt = $this->db->prepare(
            "UPDATE transactions
             SET meeting_location = :meeting_location,
                 meeting_at = :meeting_at,
                 meeting_notes = :meeting_notes,
                 coordination_status = 'Coordinada',
                 coordination_updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND transaction_status = 'Aceptada'"
        );
        $stmt->execute([
            'meeting_location' => $meetingLocation,
            'meeting_at'       => $meetingAt,
            'meeting_notes'    => $meetingNotes,
            'id'               => $transactionId,
        ]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'reason' => 'estado_invalido'];
        }

        return ['success' => true, 'transaction' => $this->findById($transactionId)];
    }

    /**
     * Flujo 02, paso 4-5: registra la confirmacion de una de las partes.
     * Cuando ambas partes han confirmado, la transaccion se marca "Completada".
     */
    public function confirmDelivery(int $transactionId, int $userId): array
    {
        $transaction = $this->findById($transactionId);
        if (!$transaction) {
            return ['success' => false, 'reason' => 'transaccion_inexistente'];
        }

        if ($transaction['transaction_status'] !== 'Aceptada') {
            return ['success' => false, 'reason' => 'estado_invalido'];
        }

        if ($transaction['coordination_status'] !== 'Coordinada'
            || empty($transaction['meeting_location'])
            || empty($transaction['meeting_at'])) {
            return ['success' => false, 'reason' => 'coordinacion_pendiente'];
        }

        if ((int) $transaction['buyer_id'] === $userId) {
            $stmt = $this->db->prepare('UPDATE transactions SET buyer_confirmed = 1 WHERE id = :id');
        } elseif ((int) $transaction['seller_id'] === $userId) {
            $stmt = $this->db->prepare('UPDATE transactions SET seller_confirmed = 1 WHERE id = :id');
        } else {
            return ['success' => false, 'reason' => 'sin_permiso'];
        }
        $stmt->execute(['id' => $transactionId]);

        $transaction = $this->findById($transactionId);
        $bothConfirmed = (bool) $transaction['buyer_confirmed'] && (bool) $transaction['seller_confirmed'];

        if ($bothConfirmed) {
            $complete = $this->db->prepare(
                "UPDATE transactions
                 SET transaction_status = 'Completada', coordination_status = 'Completada'
                 WHERE id = :id"
            );
            $complete->execute(['id' => $transactionId]);
        }

        return ['success' => true, 'completed' => $bothConfirmed, 'transaction' => $this->findById($transactionId)];
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Todas las transacciones (compras y ventas) visibles para un usuario. */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, p.title AS publication_title,
                    buyer.full_name AS buyer_name, seller.full_name AS seller_name
             FROM transactions t
             JOIN publications p ON p.id = t.publication_id
             JOIN users buyer ON buyer.id = t.buyer_id
             JOIN users seller ON seller.id = t.seller_id
             WHERE t.buyer_id = :user_id OR t.seller_id = :user_id
             ORDER BY t.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
