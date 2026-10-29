-- Agrega la coordinacion presencial requerida antes de confirmar una entrega.
ALTER TABLE transactions ADD COLUMN meeting_location TEXT;
ALTER TABLE transactions ADD COLUMN meeting_at TEXT;
ALTER TABLE transactions ADD COLUMN meeting_notes TEXT;
ALTER TABLE transactions ADD COLUMN coordination_status TEXT NOT NULL DEFAULT 'Pendiente';
ALTER TABLE transactions ADD COLUMN coordination_updated_at TEXT;

CREATE INDEX IF NOT EXISTS idx_transactions_coordination
    ON transactions (coordination_status, meeting_at);
