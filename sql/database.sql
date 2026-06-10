-- ============================================================
-- SLOTIFY — Base de données v3 (prix + type + localisation)
-- ============================================================

CREATE DATABASE IF NOT EXISTS slotify_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE slotify_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    bio        TEXT          DEFAULT NULL,
    plan       ENUM('free','pro') DEFAULT 'free',     -- plan d'abonnement
    plan_since TIMESTAMP     NULL DEFAULT NULL,       -- date de passage en Pro
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS slots (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    title        VARCHAR(255) NOT NULL,
    date         DATE         NOT NULL,
    start_time   TIME         NOT NULL,
    end_time     TIME         NOT NULL,
    price        DECIMAL(8,2) DEFAULT NULL,                          -- prix affiché (NULL = gratuit / non précisé)
    type         ENUM('presentiel','visio') DEFAULT 'presentiel',    -- type de rendez-vous
    location     VARCHAR(255) DEFAULT NULL,                          -- adresse si présentiel
    meeting_link VARCHAR(500) DEFAULT NULL,                          -- lien Zoom/Meet si visio
    is_booked    BOOLEAN      DEFAULT 0,
    booked_by    INT          DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slot_id     INT          NOT NULL,
    booker_id   INT          NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id)   REFERENCES slots(id)   ON DELETE CASCADE,
    FOREIGN KEY (booker_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MIGRATION : si ta base existe DÉJÀ, exécute SEULEMENT ces
-- lignes dans phpMyAdmin (onglet SQL) :
-- ============================================================
-- ALTER TABLE slots
--   ADD COLUMN price        DECIMAL(8,2) DEFAULT NULL AFTER end_time,
--   ADD COLUMN type         ENUM('presentiel','visio') DEFAULT 'presentiel' AFTER price,
--   ADD COLUMN location     VARCHAR(255) DEFAULT NULL AFTER type,
--   ADD COLUMN meeting_link VARCHAR(500) DEFAULT NULL AFTER location;
--
-- Migration Phase 2 (plans Free/Pro) :
-- ALTER TABLE users
--   ADD COLUMN plan       ENUM('free','pro') DEFAULT 'free' AFTER bio,
--   ADD COLUMN plan_since TIMESTAMP NULL DEFAULT NULL AFTER plan;