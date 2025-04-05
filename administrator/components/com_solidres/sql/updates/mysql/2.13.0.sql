ALTER TABLE `#__sr_reservation_assets` ADD `alternative_name` VARCHAR(255) NOT NULL AFTER `name`;
ALTER TABLE `#__sr_payment_history` ADD `payment_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `currency_id`;
ALTER TABLE `#__sr_reservations` ADD `total_fee` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL AFTER `total_single_supplement`;
ALTER TABLE `#__sr_statuses` ADD `email_text` TEXT NULL AFTER `type`;