CREATE TABLE IF NOT EXISTS `#__sr_extra_item_xref` (
  `extra_id` INT(11) UNSIGNED NOT NULL,
  `item_id` INT(11) UNSIGNED NOT NULL,
  `scope` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`extra_id`, `item_id`, `scope`))
ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__sr_reservation_notes_attachments` CHANGE `attachment_file_name` `attachment_file_name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;