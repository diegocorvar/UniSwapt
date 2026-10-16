-- Esquema de base de datos de UniMarket (prototipo local con SQLite)
-- Cubre RF-01 a RF-11 del documento de requerimientos.

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name       TEXT NOT NULL,
    email           TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    role            TEXT NOT NULL DEFAULT 'estudiante', -- estudiante | egresado | administrador
    career          TEXT,
    semester        TEXT,
    profile_photo   TEXT,
    whatsapp_phone  TEXT,
    account_status  TEXT NOT NULL DEFAULT 'pendiente',  -- pendiente | activo | suspendido
    reputation_avg   REAL NOT NULL DEFAULT 0,
    reputation_count INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tokens (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    token       TEXT NOT NULL UNIQUE,
    token_type  TEXT NOT NULL, -- verificacion_cuenta | restablecer_password
    expires_at  TEXT NOT NULL,
    used        INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS publications (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL,
    title            TEXT NOT NULL,
    description      TEXT NOT NULL,
    item_condition   TEXT NOT NULL, -- Nuevo | Usado - Buen Estado | Usado - Detalle
    modality         TEXT NOT NULL, -- Venta | Trueque | Donacion
    price            REAL NOT NULL DEFAULT 0,
    trade_interest   TEXT,
    career_category  TEXT NOT NULL,
    material_category TEXT NOT NULL,
    publication_status TEXT NOT NULL DEFAULT 'Disponible', -- Disponible | En Proceso de Entrega | Vendido/Intercambiado | Donado | Inactivo
    created_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS publication_images (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    publication_id  INTEGER NOT NULL,
    image_path      TEXT NOT NULL,
    is_cover        INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    publication_id   INTEGER NOT NULL,
    buyer_id         INTEGER NOT NULL,
    seller_id        INTEGER NOT NULL,
    modality         TEXT NOT NULL,
    offer_amount     REAL,
    transaction_status TEXT NOT NULL DEFAULT 'Propuesta', -- Propuesta | Aceptada | Rechazada | Completada | Cancelada
    buyer_confirmed  INTEGER NOT NULL DEFAULT 0,
    seller_confirmed INTEGER NOT NULL DEFAULT 0,
    created_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ratings (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id  INTEGER NOT NULL,
    rater_id        INTEGER NOT NULL,
    rated_id        INTEGER NOT NULL,
    stars           INTEGER NOT NULL,
    comment         TEXT,
    created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    UNIQUE (transaction_id, rater_id)
);

CREATE TABLE IF NOT EXISTS reports (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    reporter_id   INTEGER NOT NULL,
    target_type   TEXT NOT NULL, -- usuario | publicacion
    target_id     INTEGER NOT NULL,
    reason        TEXT NOT NULL,
    evidence      TEXT,
    report_status TEXT NOT NULL DEFAULT 'Pendiente',
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
