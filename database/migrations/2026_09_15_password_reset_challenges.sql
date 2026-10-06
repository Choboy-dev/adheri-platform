-- Password recovery challenges (Admin CMS + ADHERI Mail)
CREATE TABLE IF NOT EXISTS password_reset_challenges (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  public_id CHAR(36) NOT NULL,
  realm ENUM('admin','mail') NOT NULL,
  account_email VARCHAR(190) NOT NULL,
  account_user_id INT UNSIGNED NOT NULL,
  delivery_channel ENUM('recovery_inbox','account_email') NOT NULL,
  delivery_email VARCHAR(190) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  reset_token CHAR(64) NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME NULL,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_reset_public_id (public_id),
  UNIQUE KEY uq_password_reset_token (reset_token),
  KEY idx_password_reset_lookup (realm, account_email, created_at),
  KEY idx_password_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
