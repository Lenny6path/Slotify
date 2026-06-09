-- ============================================================
-- SLOTIFY — Base de données complète v2
-- ============================================================

CREATE DATABASE IF NOT EXISTS slotify_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE slotify_db;

-- ------------------------------------------------------------
-- Table : users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
                                     id         INT AUTO_INCREMENT PRIMARY KEY,
                                     name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    bio        TEXT          DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : slots
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS slots (
                                     id         INT AUTO_INCREMENT PRIMARY KEY,
                                     user_id    INT          NOT NULL,
                                     title      VARCHAR(255) NOT NULL,
    date       DATE         NOT NULL,
    start_time TIME         NOT NULL,
    end_time   TIME         NOT NULL,
    is_booked  BOOLEAN      DEFAULT 0,
    booked_by  INT          DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : bookings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
                                        id          INT AUTO_INCREMENT PRIMARY KEY,
                                        slot_id     INT          NOT NULL,
                                        booker_id   INT          NOT NULL,
                                        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (slot_id)   REFERENCES slots(id)   ON DELETE CASCADE,
    FOREIGN KEY (booker_id) REFERENCES users(id)   ON DELETE CASCADE
    ) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Ajout colonne bio si migration depuis v1
-- (ignorer si table vient d'être créée)
-- ------------------------------------------------------------
-- ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL AFTER password;