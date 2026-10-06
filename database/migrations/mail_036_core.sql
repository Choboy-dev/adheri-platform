-- mail_036_core (MySQL) — ADHERI Metro Mail port
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS mail_migrations (
  id VARCHAR(120) NOT NULL PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  display_name VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  avatar_color VARCHAR(20) NOT NULL DEFAULT '#0071BC',
  signature_on_new TINYINT(1) NOT NULL DEFAULT 1,
  signature_on_reply TINYINT(1) NOT NULL DEFAULT 1,
  avatar_path VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mail_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  folder ENUM('inbox','sent','drafts','scheduled','archive','spam','trash') NOT NULL DEFAULT 'inbox',
  category ENUM('primary','promotions','social','updates') NOT NULL DEFAULT 'primary',
  direction ENUM('inbound','outbound') NOT NULL,
  from_email VARCHAR(190) NOT NULL,
  from_name VARCHAR(190) NULL,
  to_emails JSON NOT NULL,
  cc_emails JSON NULL,
  bcc_emails JSON NULL,
  subject VARCHAR(500) NOT NULL DEFAULT '',
  body_html MEDIUMTEXT NULL,
  body_text MEDIUMTEXT NULL,
  snippet VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  is_starred TINYINT(1) NOT NULL DEFAULT 0,
  thread_id VARCHAR(190) NULL,
  message_id VARCHAR(255) NULL,
  in_reply_to VARCHAR(255) NULL,
  resend_email_id VARCHAR(190) NULL,
  sent_at DATETIME NULL,
  received_at DATETIME NULL,
  scheduled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mail_resend_email_id (resend_email_id),
  KEY idx_mail_msg_user_folder (user_id, folder),
  KEY idx_mail_msg_user_star (user_id, is_starred),
  KEY idx_mail_msg_scheduled (user_id, scheduled_at),
  CONSTRAINT fk_mail_messages_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_attachments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  content_type VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  storage_path VARCHAR(500) NOT NULL,
  resend_attachment_id VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mail_att_message (message_id),
  CONSTRAINT fk_mail_attachments_message FOREIGN KEY (message_id) REFERENCES mail_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_labels (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  color VARCHAR(20) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mail_labels_user_name (user_id, name),
  CONSTRAINT fk_mail_labels_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_message_labels (
  message_id INT UNSIGNED NOT NULL,
  label_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (message_id, label_id),
  CONSTRAINT fk_mail_ml_message FOREIGN KEY (message_id) REFERENCES mail_messages(id) ON DELETE CASCADE,
  CONSTRAINT fk_mail_ml_label FOREIGN KEY (label_id) REFERENCES mail_labels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mail_login_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_signatures (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mail_sig_user_name (user_id, name),
  CONSTRAINT fk_mail_signatures_user FOREIGN KEY (user_id) REFERENCES mail_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
