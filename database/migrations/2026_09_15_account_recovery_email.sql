-- Per-account recovery emails (AdminUser + MailUser)
ALTER TABLE mail_users
  ADD COLUMN recovery_email VARCHAR(190) NULL AFTER email;

ALTER TABLE admin_users
  ADD COLUMN recovery_email VARCHAR(190) NULL AFTER email;

-- Prefer personal recovery channel name going forward (keep legacy value for old rows)
ALTER TABLE password_reset_challenges
  MODIFY delivery_channel ENUM('recovery_email','account_email','recovery_inbox') NOT NULL;
