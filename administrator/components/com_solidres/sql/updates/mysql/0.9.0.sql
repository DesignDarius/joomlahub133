ALTER TABLE  `#__sr_reservation_assets` ADD COLUMN `booking_type` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `#__sr_reservations` ADD COLUMN `booking_type` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE `#__sr_reservation_assets` ADD COLUMN `deposit_by_stay_length` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `deposit_amount`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `total_single_supplement` DECIMAL(12,2) UNSIGNED NULL ;
ALTER TABLE `#__sr_reservations` ADD COLUMN `token` VARCHAR(40) NULL DEFAULT NULL ;