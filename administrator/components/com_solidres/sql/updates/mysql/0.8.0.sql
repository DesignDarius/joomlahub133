ALTER TABLE `#__sr_room_types` ADD COLUMN `occupancy_max` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `ordering` ;

ALTER TABLE `#__sr_tariffs` CHANGE  `title`  `title` TEXT NULL DEFAULT NULL ;

ALTER TABLE `#__sr_tariffs` CHANGE  `description`  `description` TEXT NULL DEFAULT NULL ;

ALTER TABLE `#__sr_tariffs` ADD COLUMN `state` TINYINT( 3 ) NOT NULL DEFAULT  '1';

ALTER TABLE `#__sr_reservations` ADD COLUMN `customer_mobilephone` VARCHAR( 45 ) NOT NULL AFTER  `customer_phonenumber` ;

ALTER TABLE `#__sr_customers` ADD COLUMN `mobilephone` VARCHAR( 45 ) NOT NULL AFTER  `phonenumber` ;

ALTER TABLE `#__sr_reservations` ADD COLUMN `payment_data` TEXT NULL AFTER  `payment_status` ;

ALTER TABLE `#__sr_extras` ADD COLUMN `price_adult` DECIMAL( 12, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' AFTER  `price` ;

ALTER TABLE `#__sr_extras` ADD COLUMN `price_child` DECIMAL( 12, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' AFTER  `price_adult` ;

ALTER TABLE `#__sr_reservations` ADD COLUMN `discount_pre_tax` TINYINT( 3 ) UNSIGNED NULL DEFAULT NULL ;

ALTER TABLE `#__sr_reservations` ADD COLUMN `tax_amount` DECIMAL( 12, 2 ) UNSIGNED NOT NULL ;