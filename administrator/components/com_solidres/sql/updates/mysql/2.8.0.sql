ALTER TABLE `#__sr_tariffs` ADD `cm_id` VARCHAR(45) NULL AFTER `ordering`;

ALTER TABLE `#__sr_reservations` ADD `cm_provider` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `target`;

ALTER TABLE `#__sr_payment_history` ADD `title` VARCHAR(255) NOT NULL AFTER `scope`;

ALTER TABLE `#__sr_payment_history` ADD `description` TEXT NOT NULL AFTER `title`;

ALTER TABLE `#__sr_room_types` ADD `is_master` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0 AFTER `is_private`;

ALTER TABLE `#__sr_reservations` ADD `customer_language` VARCHAR(7) NULL AFTER `customer_coordinates`;

ALTER TABLE `#__sr_reservations` ADD `reservation_meta` TEXT NULL AFTER `checked_out_date`;