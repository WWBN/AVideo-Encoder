-- Per-job state for the encoder monitor cron (install/cron.php): how long a job has been in its
-- current state and which alerts were already sent, so owners get at most one reminder per day.
-- The foreign key is unnamed on purpose: update.php does not prefix constraint names, and two
-- prefixed encoders sharing one database would otherwise collide on the same constraint name.
CREATE TABLE IF NOT EXISTS `encoder_queue_monitor` (
  `encoder_queue_id` INT NOT NULL,
  `state` VARCHAR(20) NOT NULL,
  `state_since` DATETIME NOT NULL,
  `alerts_sent` INT NOT NULL DEFAULT 0,
  `last_alert_type` VARCHAR(32) NULL DEFAULT NULL,
  `last_alert_at` DATETIME NULL DEFAULT NULL,
  `last_attempt_at` DATETIME NULL DEFAULT NULL,
  `last_result` VARCHAR(255) NULL DEFAULT NULL,
  `dead_since` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`encoder_queue_id`),
  FOREIGN KEY (`encoder_queue_id`)
    REFERENCES `encoder_queue` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

-- Encoder-wide monitor values: cron heartbeat and the last time each system alert was sent.
CREATE TABLE IF NOT EXISTS `encoder_monitor_state` (
  `name` VARCHAR(64) NOT NULL,
  `value` TEXT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`name`))
ENGINE = InnoDB;

UPDATE configurations_encoder SET  version = '8.3', modified = now() WHERE id = 1;
