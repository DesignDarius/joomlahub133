ALTER TABLE `#__sr_reservations` ADD `is_approved` TINYINT(2) UNSIGNED NOT NULL DEFAULT 1 AFTER `cm_channel_order_id`;
ALTER TABLE `#__sr_tariffs` CHANGE `d_min` `d_min` SMALLINT NULL DEFAULT NULL;
ALTER TABLE `#__sr_tariffs` CHANGE `d_max` `d_max` SMALLINT NULL DEFAULT NULL;
ALTER TABLE `#__sr_reservations` ADD `tourist_tax_amount` DECIMAL(12,2) UNSIGNED NOT NULL AFTER `tax_amount`;
ALTER TABLE `#__sr_reservations` ADD `payment_method_surcharge` DECIMAL(12,2) UNSIGNED NOT NULL AFTER `payment_method_txn_id`;
ALTER TABLE `#__sr_reservations` ADD `payment_method_discount` DECIMAL(12,2) UNSIGNED NOT NULL AFTER `payment_method_surcharge`;
ALTER TABLE `#__sr_reservation_assets` ADD `price_includes_tax` TINYINT(1) UNSIGNED NOT NULL DEFAULT  0 AFTER `booking_type`;