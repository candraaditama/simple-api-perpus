CREATE DATABASE IF NOT EXISTS perpus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpus;

CREATE TABLE anggota (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kta VARCHAR(20) UNIQUE, 
    nik VARCHAR(16) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kta (kta),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE api_keys (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50) NOT NULL,        -- contoh. "Frontend App", "Mobile App"
    token      CHAR(64) NOT NULL UNIQUE,    -- 256-bit random token
    rate_limit INT DEFAULT 20,             -- max submissions per jam
    last_used  DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO api_keys (name, token) VALUES ('Dukcapil Smart', 'a1b2c3d4e5f678901234567890abcdef1234567890abcdef1234567890abcdef');