-- =============================================================================
-- UniSwapt · Esquema completo para PostgreSQL (Supabase)
-- =============================================================================
-- Equivale a database/schema.sql + todas las migraciones de database/migrations/
-- (001_add_transaction_coordination.sql y 002_add_transaction_accepted_at.sql).
--
-- Uso: copiar y ejecutar completo en el SQL Editor de Supabase. Es idempotente
-- (IF NOT EXISTS / ON CONFLICT DO NOTHING), asi que puede volver a ejecutarse.
--
-- Convenciones para mantener compatibilidad total con el codigo PHP:
--   * Banderas booleanas como SMALLINT 0/1 (used, is_cover, buyer_confirmed,
--     seller_confirmed): el codigo compara y escribe 0/1, no true/false.
--   * Fechas como TIMESTAMP(0) sin zona horaria: PHP escribe 'Y-m-d H:i:s' y
--     compara contra parametros generados en PHP, igual que con SQLite.
--   * Importes como NUMERIC(10,2).
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- Usuarios (RF-01, RF-03, HU-01 a HU-05, HU-09)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id               SERIAL PRIMARY KEY,
    full_name        TEXT NOT NULL,
    email            TEXT NOT NULL UNIQUE,
    password_hash    TEXT NOT NULL,
    role             TEXT NOT NULL DEFAULT 'estudiante',
    career           TEXT,
    semester         TEXT,
    profile_photo    TEXT,
    whatsapp_phone   TEXT,
    account_status   TEXT NOT NULL DEFAULT 'pendiente',
    reputation_avg   NUMERIC(3,2) NOT NULL DEFAULT 0,
    reputation_count INTEGER NOT NULL DEFAULT 0,
    created_at       TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_role_check
        CHECK (role IN ('estudiante', 'egresado', 'administrador')),
    CONSTRAINT users_account_status_check
        CHECK (account_status IN ('pendiente', 'activo', 'suspendido')),
    -- El correo se guarda siempre normalizado (minusculas, sin espacios).
    CONSTRAINT users_email_normalized_check
        CHECK (email = LOWER(BTRIM(email))),
    CONSTRAINT users_reputation_avg_check
        CHECK (reputation_avg BETWEEN 0 AND 5),
    CONSTRAINT users_reputation_count_check
        CHECK (reputation_count >= 0)
);

-- -----------------------------------------------------------------------------
-- Tokens de verificacion de cuenta y restablecimiento de contrasena (HU-02, HU-05)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tokens (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       TEXT NOT NULL UNIQUE,
    token_type  TEXT NOT NULL,
    expires_at  TIMESTAMP(0) NOT NULL,
    used        SMALLINT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT tokens_token_type_check
        CHECK (token_type IN ('verificacion_cuenta', 'restablecer_password')),
    CONSTRAINT tokens_used_check
        CHECK (used IN (0, 1))
);

-- -----------------------------------------------------------------------------
-- Publicaciones (HU-06, HU-08, RF-10)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publications (
    id                 SERIAL PRIMARY KEY,
    user_id            INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title              TEXT NOT NULL,
    description        TEXT NOT NULL,
    item_condition     TEXT NOT NULL,
    modality           TEXT NOT NULL,
    price              NUMERIC(10,2) NOT NULL DEFAULT 0,
    trade_interest     TEXT,
    career_category    TEXT NOT NULL,
    material_category  TEXT NOT NULL,
    publication_status TEXT NOT NULL DEFAULT 'Disponible',
    created_at         TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT publications_item_condition_check
        CHECK (item_condition IN ('Nuevo', 'Usado - Buen Estado', 'Usado - Detalle')),
    CONSTRAINT publications_modality_check
        CHECK (modality IN ('Venta', 'Trueque', 'Donacion')),
    CONSTRAINT publications_price_check
        CHECK (price >= 0),
    CONSTRAINT publications_status_check
        CHECK (publication_status IN (
            'Disponible', 'En Proceso de Entrega', 'Vendido/Intercambiado', 'Donado', 'Inactivo'
        ))
);

-- -----------------------------------------------------------------------------
-- Galeria de imagenes por publicacion (HU-07)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publication_images (
    id              SERIAL PRIMARY KEY,
    publication_id  INTEGER NOT NULL REFERENCES publications(id) ON DELETE CASCADE,
    image_path      TEXT NOT NULL,
    is_cover        SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT publication_images_is_cover_check
        CHECK (is_cover IN (0, 1))
);

-- -----------------------------------------------------------------------------
-- Transacciones (Flujo 01 negociacion + Flujo 02 coordinacion y entrega)
-- Incluye las columnas de las migraciones 001 y 002.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id                      SERIAL PRIMARY KEY,
    publication_id          INTEGER NOT NULL REFERENCES publications(id) ON DELETE CASCADE,
    buyer_id                INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    seller_id               INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    modality                TEXT NOT NULL,
    offer_amount            NUMERIC(10,2),
    transaction_status      TEXT NOT NULL DEFAULT 'Propuesta',
    buyer_confirmed         SMALLINT NOT NULL DEFAULT 0,
    seller_confirmed        SMALLINT NOT NULL DEFAULT 0,
    created_at              TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- 001_add_transaction_coordination.sql
    meeting_location        TEXT,
    meeting_at              TIMESTAMP(0),
    meeting_notes           TEXT,
    coordination_status     TEXT NOT NULL DEFAULT 'Pendiente',
    coordination_updated_at TIMESTAMP(0),
    -- 002_add_transaction_accepted_at.sql
    accepted_at             TIMESTAMP(0),
    CONSTRAINT transactions_modality_check
        CHECK (modality IN ('Venta', 'Trueque', 'Donacion')),
    CONSTRAINT transactions_offer_amount_check
        CHECK (offer_amount IS NULL OR offer_amount >= 0),
    CONSTRAINT transactions_status_check
        CHECK (transaction_status IN ('Propuesta', 'Aceptada', 'Rechazada', 'Completada', 'Cancelada')),
    CONSTRAINT transactions_coordination_status_check
        CHECK (coordination_status IN ('Pendiente', 'Coordinada', 'Completada', 'Cancelada')),
    CONSTRAINT transactions_buyer_confirmed_check
        CHECK (buyer_confirmed IN (0, 1)),
    CONSTRAINT transactions_seller_confirmed_check
        CHECK (seller_confirmed IN (0, 1)),
    CONSTRAINT transactions_distinct_parties_check
        CHECK (buyer_id <> seller_id)
);

-- -----------------------------------------------------------------------------
-- Valoraciones (HU-09 / Flujo 03)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
    id              SERIAL PRIMARY KEY,
    transaction_id  INTEGER NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
    rater_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rated_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    stars           SMALLINT NOT NULL,
    comment         TEXT,
    created_at      TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ratings_transaction_rater_unique UNIQUE (transaction_id, rater_id),
    CONSTRAINT ratings_stars_check CHECK (stars BETWEEN 1 AND 5)
);

-- -----------------------------------------------------------------------------
-- Reportes de moderacion (Flujo 03, flujo alterno)
-- target_id es polimorfico (usuario o publicacion), por eso no lleva FK.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    id            SERIAL PRIMARY KEY,
    reporter_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    target_type   TEXT NOT NULL,
    target_id     INTEGER NOT NULL,
    reason        TEXT NOT NULL,
    evidence      TEXT,
    report_status TEXT NOT NULL DEFAULT 'Pendiente',
    created_at    TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reports_target_type_check
        CHECK (target_type IN ('usuario', 'publicacion')),
    CONSTRAINT reports_status_check
        CHECK (report_status IN ('Pendiente', 'En revision', 'Resuelto', 'Descartado'))
);

-- -----------------------------------------------------------------------------
-- Control de migraciones: php/config/database.php consulta esta tabla al
-- arrancar y solo ejecuta los archivos de database/migrations/ no registrados.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS schema_migrations (
    version    TEXT PRIMARY KEY,
    applied_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- Indices de rendimiento
-- (users.email y tokens.token ya quedan indexados por su restriccion UNIQUE)
-- =============================================================================

-- Unicidad del correo sin importar mayusculas (defensa adicional a la normalizacion en PHP).
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email_lower
    ON users (LOWER(email));

-- Busqueda de tokens vigentes por usuario al reenviar/invalidar (HU-02, HU-05).
CREATE INDEX IF NOT EXISTS idx_tokens_user_type_used
    ON tokens (user_id, token_type, used);

-- Catalogo con filtros combinados (HU-08).
CREATE INDEX IF NOT EXISTS idx_publications_filters
    ON publications (publication_status, career_category, material_category, modality);
CREATE INDEX IF NOT EXISTS idx_publications_status_created
    ON publications (publication_status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_publications_user
    ON publications (user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_publication_images_publication
    ON publication_images (publication_id, is_cover DESC, id);

-- Transacciones por publicacion, comprador y vendedor (Flujo 01 / 02).
CREATE INDEX IF NOT EXISTS idx_transactions_publication
    ON transactions (publication_id, transaction_status);
CREATE INDEX IF NOT EXISTS idx_transactions_buyer
    ON transactions (buyer_id);
CREATE INDEX IF NOT EXISTS idx_transactions_seller
    ON transactions (seller_id);
-- Migracion 001.
CREATE INDEX IF NOT EXISTS idx_transactions_coordination
    ON transactions (coordination_status, meeting_at);
-- Migracion 002: liberacion de reservas vencidas.
CREATE INDEX IF NOT EXISTS idx_transactions_status_accepted
    ON transactions (transaction_status, accepted_at);

CREATE INDEX IF NOT EXISTS idx_ratings_rated
    ON ratings (rated_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_reports_status_created
    ON reports (report_status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_reports_reporter
    ON reports (reporter_id);

-- =============================================================================
-- Seguridad en Supabase: Row Level Security
-- La app PHP se conecta con el rol "postgres" (dueno de las tablas), que no se
-- ve afectado por RLS. Habilitarlo sin politicas impide que las claves "anon" /
-- "authenticated" de la API REST de Supabase lean o modifiquen estas tablas
-- (por ejemplo, users.password_hash o tokens.token).
-- =============================================================================
ALTER TABLE users              ENABLE ROW LEVEL SECURITY;
ALTER TABLE tokens             ENABLE ROW LEVEL SECURITY;
ALTER TABLE publications       ENABLE ROW LEVEL SECURITY;
ALTER TABLE publication_images ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions       ENABLE ROW LEVEL SECURITY;
ALTER TABLE ratings            ENABLE ROW LEVEL SECURITY;
ALTER TABLE reports            ENABLE ROW LEVEL SECURITY;
ALTER TABLE schema_migrations  ENABLE ROW LEVEL SECURITY;

-- =============================================================================
-- Migraciones ya incorporadas en este esquema: se registran como aplicadas para
-- que el arranque de la app no intente volver a ejecutarlas.
-- =============================================================================
INSERT INTO schema_migrations (version) VALUES
    ('001_add_transaction_coordination.sql'),
    ('002_add_transaction_accepted_at.sql')
ON CONFLICT (version) DO NOTHING;

COMMIT;
