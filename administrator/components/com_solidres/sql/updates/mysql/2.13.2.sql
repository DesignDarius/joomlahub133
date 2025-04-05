CREATE TABLE IF NOT EXISTS `#__sr_reservation_notes_attachments` (
  `note_id` INT UNSIGNED NOT NULL,
  `attachment_file_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`note_id`, `attachment_file_name`),
  CONSTRAINT `fk_sr_notes_attachments_sr_reservation_no1`
    FOREIGN KEY (`note_id`)
    REFERENCES `#__sr_reservation_notes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__sr_reservations` CHANGE `total_single_supplement` `total_single_supplement` DECIMAL(20,6) NULL DEFAULT NULL;