ALTER TABLE `#__sr_countries` ADD `is_default` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `modified_date`;
-- See 2.12.7.sql
-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_surcharge` `payment_method_surcharge` DECIMAL(20,6) UNSIGNED NOT NULL DEFAULT '0';
-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_discount` `payment_method_discount` DECIMAL(20,6) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `#__sr_tariffs` ADD `q_min` SMALLINT NULL AFTER `cm_id`, ADD `q_max` SMALLINT NULL AFTER `q_min`;