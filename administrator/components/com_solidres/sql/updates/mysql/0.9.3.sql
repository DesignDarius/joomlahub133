ALTER TABLE `#__sr_reservations` ADD COLUMN `accessed_date` DATETIME NULL DEFAULT NULL AFTER `origin`;
ALTER TABLE `#__sr_reservation_assets` CHANGE `approved` `approved` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
UPDATE `#__sr_reservation_assets` SET approved = NULL WHERE approved = 0;