ALTER TABLE `#__sr_tariffs` ADD COLUMN `mode` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `state`;
ALTER TABLE `#__sr_tariff_details` ADD COLUMN `date` DATE NULL DEFAULT NULL AFTER `to_age`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `target` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `accessed_date`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `customer_ip` VARCHAR(50) NULL DEFAULT NULL AFTER `customer_vat_number`;