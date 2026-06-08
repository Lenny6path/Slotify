-- ============================================================
-- SLOTIFY — Base de données
-- ============================================================

CREATE DATABASE IF NOT EXISTS slotify_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE slotify_db;

-- ------------------------------------------------------------
-- Table : users (professionnels)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : slots (créneaux)
-- booked_by  → FK vers users.id (client qui a réservé)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS slots (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,                 -- professionnel propriétaire
    title      VARCHAR(255) NOT NULL,
    date       DATE         NOT NULL,
    start_time TIME         NOT NULL,
    end_time   TIME         NOT NULL,
    is_booked  BOOLEAN      DEFAULT 0,
    booked_by  INT          DEFAULT NULL,             -- ← colonne manquante corrigée
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : bookings (historique des réservations)
-- On garde les deux pour l'historique complet
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slot_id     INT          NOT NULL,
    booker_id   INT          NOT NULL,               -- client
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id)   REFERENCES slots(id)   ON DELETE CASCADE,
    FOREIGN KEY (booker_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;