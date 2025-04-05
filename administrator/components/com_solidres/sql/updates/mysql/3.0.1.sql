CREATE TABLE IF NOT EXISTS `#__sr_queue_watches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_total` INT UNSIGNED NULL,
  `queue_processed` INT UNSIGNED NULL,
  `status` TINYINT NULL,
  `target_id` INT NULL,
  `target_type` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sr_queues` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_key` VARCHAR(512) NULL,
  `event_value` TEXT NULL,
  `created_date` DATETIME NULL,
  `processed_date` DATETIME NULL,
  `attempts_count` TINYINT NULL,
  `last_attempt` DATETIME NULL,
  `last_response` TEXT NULL,
  `watch_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_sr_queues_sr_queue_watches1_idx` (`watch_id` ASC),
  CONSTRAINT `fk_sr_queues_sr_queue_watches1`
    FOREIGN KEY (`watch_id`)
    REFERENCES `#__sr_queue_watches` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;