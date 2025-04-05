ALTER TABLE `#__sr_reservation_room_xref` CHANGE `tariff_title` `tariff_title` TEXT NULL DEFAULT NULL;
ALTER TABLE `#__sr_reservation_room_xref` CHANGE `tariff_description` `tariff_description` TEXT NULL DEFAULT NULL;
ALTER TABLE `#__sr_reservations` ADD COLUMN `cm_id` VARCHAR(45) NULL  AFTER `target`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `cm_creation_date` DATETIME NULL;
ALTER TABLE `#__sr_reservations` ADD COLUMN `cm_modification_date` DATETIME NULL;
ALTER TABLE `#__sr_reservations` ADD COLUMN `cm_channel_order_id` VARCHAR(45) NULL;
-- See 2.7.0.sql
-- ALTER TABLE `#__sr_reservations` CHANGE `tax_amount` `tax_amount` DECIMAL(12,2) UNSIGNED NULL;