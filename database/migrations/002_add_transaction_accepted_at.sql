-- Registra cuando se acepto una propuesta para poder liberar reservas vencidas
-- (RESERVATION_EXPIRATION_HOURS). A las reservas ya activas se les concede el
-- plazo completo a partir de la aplicacion de esta migracion.
ALTER TABLE transactions ADD COLUMN accepted_at TEXT;

UPDATE transactions
   SET accepted_at = CURRENT_TIMESTAMP
 WHERE transaction_status = 'Aceptada';

CREATE INDEX IF NOT EXISTS idx_transactions_status_accepted
    ON transactions (transaction_status, accepted_at);
