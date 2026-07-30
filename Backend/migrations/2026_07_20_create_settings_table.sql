CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(64) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_by    INT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

INSERT INTO settings (setting_key, setting_value)
VALUES ('current_school_year', '2026-2027'), ('current_semester', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
