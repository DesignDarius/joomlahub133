ALTER TABLE `#__sr_tariff_details` ADD `price_unoccupied` DECIMAL(20, 6) UNSIGNED NULL AFTER `price_extras`;
ALTER TABLE `#__sr_tariffs` ADD `ad_min` TINYINT NULL AFTER `p_max`;
ALTER TABLE `#__sr_tariffs` ADD `ad_max` TINYINT NULL AFTER `ad_min`;
ALTER TABLE `#__sr_tariffs` ADD `ch_min` TINYINT NULL AFTER `ad_max`;
ALTER TABLE `#__sr_tariffs` ADD `ch_max` TINYINT NULL AFTER `ch_min`;
ALTER TABLE `#__sr_tariffs` ADD `occupancy_restriction_type` TINYINT UNSIGNED NULL DEFAULT 0;
ALTER TABLE `#__sr_tariffs` ADD `pricing_type` TINYINT UNSIGNED NULL DEFAULT 0;
ALTER TABLE `#__sr_reservations` CHANGE `customer_phonenumber` `customer_phonenumber` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `#__sr_reservations` CHANGE `customer_mobilephone` `customer_mobilephone` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
