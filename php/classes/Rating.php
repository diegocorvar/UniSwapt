<?php
/**
 * Flujo de Trabajo 03: sistema de reputacion, valoracion y moderacion.
 */
class Rating
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function alreadyRated(int $transactionId, int $raterId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM ratings WHERE transaction_id = :t_id AND rater_id = :rater_id');
        $stmt->execute(['t_id' => $transactionId, 'rater_id' => $raterId]);
        return (bool) $stmt->fetch();
    }

    /** HU-09: guarda la valoracion y dispara el recalculo de reputacion del evaluado. */
    public function submit(int $transactionId, int $raterId, int $ratedId, int $stars, ?string $comment): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ratings (transaction_id, rater_id, rated_id, stars, comment)
             VALUES (:t_id, :rater_id, :rated_id, :stars, :comment)'
        );
        $stmt->execute([
            't_id'    => $transactionId,
            'rater_id' => $raterId,
            'rated_id' => $ratedId,
            'stars'   => max(1, min(5, $stars)),
            'comment' => $comment,
        ]);

        (new User())->recalculateReputation($ratedId);
    }

    public function findForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.full_name AS rater_name
             FROM ratings r JOIN users u ON u.id = r.rater_id
             WHERE r.rated_id = :user_id ORDER BY r.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /** Flujo 03, flujo alterno: registro de un reporte para moderacion. */
    public function fileReport(int $reporterId, string $targetType, int $targetId, string $reason, ?string $evidence): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reports (reporter_id, target_type, target_id, reason, evidence)
             VALUES (:reporter_id, :target_type, :target_id, :reason, :evidence)'
        );
        $stmt->execute([
            'reporter_id' => $reporterId,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'reason'      => $reason,
            'evidence'    => $evidence,
        ]);
    }

    /** Panel de administracion: lista todos los reportes, mas recientes primero. */
    public function listReports(): array
    {
        $stmt = $this->db->query(
            'SELECT r.*, u.full_name AS reporter_name
             FROM reports r JOIN users u ON u.id = r.reporter_id
             ORDER BY r.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public function updateReportStatus(int $reportId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE reports SET report_status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $reportId]);
    }
}
