-- VERSION TEST SLOTIFY 9999
CREATE DATABASE IF NOT EXISTS slotify_db;
USE slotify_db;

CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE slots (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL,
                       date DATE NOT NULL,
                       time TIME NOT NULL,
                       status ENUM('available', 'booked') DEFAULT 'available',
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          slot_id INT NOT NULL,
                          client_name VARCHAR(100) NOT NULL,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);