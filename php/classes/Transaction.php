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

    /**
     * Flujo 01: un comprador solo puede tener una propuesta activa (pendiente o
     * aceptada) por publicacion.
     */
    public function hasActiveProposal(int $publicationId, int $buyerId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM transactions
             WHERE publication_id = :pub_id AND buyer_id = :buyer_id
               AND transaction_status IN ('Propuesta', 'Aceptada')
             LIMIT 1"
        );
        $stmt->execute(['pub_id' => $publicationId, 'buyer_id' => $buyerId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Flujo 01, paso 4-5: el vendedor acepta y el articulo pasa a "En Proceso".
     * Solo cambia transacciones que siguen en "Propuesta"; devuelve false si no.
     */
    public function accept(int $transactionId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET transaction_status = 'Aceptada', accepted_at = :accepted_at
             WHERE id = :id AND transaction_status = 'Propuesta'"
        );
        $stmt->execute(['id' => $transactionId, 'accepted_at' => date('Y-m-d H:i:s')]);
        return $stmt->rowCount() > 0;
    }

    /** Flujo 01, alterno A1: el vendedor rechaza y la publicacion sigue disponible. */
    public function reject(int $transactionId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET transaction_status = 'Rechazada'
             WHERE id = :id AND transaction_status = 'Propuesta'"
        );
        $stmt->execute(['id' => $transactionId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Al aceptar una propuesta, el resto de propuestas pendientes de la misma
     * publicacion se rechazan automaticamente. Devuelve cuantas se rechazaron.
     */
    public function rejectOtherPendingProposals(int $publicationId, int $acceptedTransactionId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET transaction_status = 'Rechazada'
             WHERE publication_id = :pub_id AND id <> :accepted_id AND transaction_status = 'Propuesta'"
        );
        $stmt->execute(['pub_id' => $publicationId, 'accepted_id' => $acceptedTransactionId]);
        return $stmt->rowCount();
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

    /**
     * Flujo 02: una transaccion "Aceptada" (con encuentro pendiente o ya
     * coordinado) mantiene reservada la publicacion hasta completarse o cancelarse.
     */
    public function hasActiveTransactionForPublication(int $publicationId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM transactions
             WHERE publication_id = :pub_id AND transaction_status = 'Aceptada'
             LIMIT 1"
        );
        $stmt->execute(['pub_id' => $publicationId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Flujo 02: fecha a partir de la cual una reserva sin confirmar se libera.
     * Se cuenta RESERVATION_EXPIRATION_HOURS desde la aceptacion o, si ya hay un
     * encuentro coordinado, desde la fecha acordada para ese encuentro.
     */
    public static function reservationDeadline(array $transaction): ?DateTimeImmutable
    {
        if (($transaction['transaction_status'] ?? '') !== 'Aceptada') {
            return null;
        }

        $reference = ($transaction['coordination_status'] ?? '') === 'Coordinada' && !empty($transaction['meeting_at'])
            ? $transaction['meeting_at']
            : ($transaction['accepted_at'] ?? null);

        if (empty($reference)) {
            return null;
        }

        return (new DateTimeImmutable($reference))->modify('+' . RESERVATION_EXPIRATION_HOURS . ' hours');
    }

    /**
     * Flujo 02: cancela las transacciones "Aceptada" cuya reserva vencio y
     * regresa sus publicaciones a "Disponible". Devuelve cuantas se liberaron.
     */
    public function releaseExpiredReservations(): int
    {
        $cutoff = (new DateTimeImmutable())
            ->modify('-' . RESERVATION_EXPIRATION_HOURS . ' hours')
            ->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "SELECT id, publication_id FROM transactions
             WHERE transaction_status = 'Aceptada'
               AND accepted_at IS NOT NULL
               AND datetime(CASE
                       WHEN coordination_status = 'Coordinada' AND meeting_at IS NOT NULL THEN meeting_at
                       ELSE accepted_at
                   END) <= datetime(:cutoff)"
        );
        $stmt->execute(['cutoff' => $cutoff]);
        $expired = $stmt->fetchAll();

        if (empty($expired)) {
            return 0;
        }

        $publicationModel = new Publication();
        $cancel = $this->db->prepare(
            "UPDATE transactions
             SET transaction_status = 'Cancelada', coordination_status = 'Cancelada'
             WHERE id = :id AND transaction_status = 'Aceptada'"
        );

        $released = 0;
        $this->db->beginTransaction();
        try {
            foreach ($expired as $row) {
                $cancel->execute(['id' => $row['id']]);
                if ($cancel->rowCount() === 0) {
                    continue; // Otra peticion ya la cerro o cancelo.
                }
                $publicationModel->releaseReservation((int) $row['publication_id']);
                $released++;
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $released;
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
