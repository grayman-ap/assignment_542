-- IFT 542 Student Registration Application - schema (migration)
-- Applies to BOTH the hardened (student_reg) and starter (student_reg_vuln) DBs.
-- The starter DB stores plaintext passwords; the hardened DB stores hashes.
-- Fictitious data only. No real personal information is used.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matric_no     VARCHAR(20)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    phone         VARCHAR(20)  NULL,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until  DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(12)  NOT NULL UNIQUE,
    title         VARCHAR(120) NOT NULL,
    credit_units  TINYINT UNSIGNED NOT NULL DEFAULT 2,
    capacity      INT UNSIGNED NOT NULL DEFAULT 60,
    description   VARCHAR(500) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enrolments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    course_id     INT UNSIGNED NOT NULL,
    status        ENUM('pending','enrolled','dropped') NOT NULL DEFAULT 'pending',
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_course (user_id, course_id),
    CONSTRAINT fk_enrol_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_enrol_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name   VARCHAR(64)  NOT NULL,
    mime          VARCHAR(64)  NOT NULL,
    size_bytes    INT UNSIGNED NOT NULL,
    uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security event log used by rate limiting / lockout bookkeeping.
CREATE TABLE IF NOT EXISTS auth_attempts (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45)  NOT NULL,
    email      VARCHAR(254) NULL,
    outcome    ENUM('success','failure') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_time (ip, created_at),
    KEY idx_email_time (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
