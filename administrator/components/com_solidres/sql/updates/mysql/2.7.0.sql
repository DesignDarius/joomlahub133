CREATE TABLE IF NOT EXISTS `#__sr_statuses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(255) NOT NULL,
  `code` INT(11) NOT NULL,
  `state` TINYINT(1) NOT NULL DEFAULT 0,
  `color_code` CHAR(7) NOT NULL,
  `scope` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `ordering` INT(11) NOT NULL DEFAULT 0,
  `readonly` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
  ENGINE = InnoDB;

INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (1,'Pending',3,1,'#f57a27',1,1,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (2,'Confirmed',1,1,'#3c763d',1,2,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (3,'Cancelled',2,1,'#a94442',1,3,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (4,'Closed',0,1,'#777777',1,4,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (5,'Trashed',-2,1,'#000000',1,5,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (6,'Unpaid',0,1,'#ff0000',1,1,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (7,'Paid',1,1,'#3c763d',1,2,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (8,'Cancelled',2,0,'#a94442',1,3,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (9,'Pending',3,1,'#3a62d1',1,4,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (10,'Pending',0,1,'#8a6d3b',0,1,1,0);
-- INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (11,'Checkin',1,1,'#31708f',0,2,1,0);
-- INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (12,'Checkout',2,1,'#333333',0,3,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (13,'Closed',3,1,'#999999',0,4,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (14,'Cancelled',4,1,'#f89406',0,5,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (15,'Confirmed',5,1,'#3c763d',0,6,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (16,'Trashed',-2,1,'#000000',0,7,1,0);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (17,'Unpaid',0,1,'#a94442',0,1,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (18,'Completed',1,1,'#3c763d',0,2,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (19,'Cancelled',2,1,'#a94442',0,3,1,1);
INSERT INTO `#__sr_statuses` (`id`, `label`, `code`, `state`, `color_code`, `scope`, `ordering`, `readonly`, `type`) VALUES (20,'Pending',3,1,'#8a6d3b',0,4,1,1);

ALTER TABLE `#__sr_reservation_assets`
	CHANGE `deposit_amount` `deposit_amount` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_surcharge` `payment_method_surcharge` DECIMAL(20,6) UNSIGNED NOT NULL;

-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_discount` `payment_method_discount` DECIMAL(20,6) UNSIGNED NOT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_price` `total_price` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_price_tax_incl` `total_price_tax_incl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_price_tax_excl` `total_price_tax_excl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_extra_price` `total_extra_price` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_extra_price_tax_incl` `total_extra_price_tax_incl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_extra_price_tax_excl` `total_extra_price_tax_excl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_discount` `total_discount` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `deposit_amount` `deposit_amount` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `total_paid` `total_paid` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations`
	CHANGE `tax_amount` `tax_amount` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

-- ALTER TABLE `#__sr_reservations`
--	CHANGE `tourist_tax_amount` `tourist_tax_amount` DECIMAL(20,6) UNSIGNED NOT NULL;

-- ALTER TABLE `#__sr_reservations`
-- 	CHANGE `total_single_supplement` `total_single_supplement` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_extras`
	CHANGE `price` `price` DECIMAL(20,6) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `#__sr_extras`
	CHANGE `price_adult` `price_adult` DECIMAL(20,6) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `#__sr_extras`
	CHANGE `price_child` `price_child` DECIMAL(20,6) UNSIGNED NOT NULL DEFAULT 0;


ALTER TABLE `#__sr_reservation_room_xref`
	CHANGE `room_price` `room_price` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservation_room_xref`
	CHANGE `room_price_tax_incl` `room_price_tax_incl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservation_room_xref`
	CHANGE `room_price_tax_excl` `room_price_tax_excl` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservation_room_extra_xref`
	CHANGE `extra_price` `extra_price` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

--ALTER TABLE `#__sr_tariff_details`
--	CHANGE `price` `price` DECIMAL(20,6) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservation_extra_xref`
	CHANGE `extra_price` `extra_price` DECIMAL(20,6) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `#__sr_payment_history` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT(11) UNSIGNED NOT NULL,
  `scope` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `payment_date` DATETIME NULL,
  `payment_status` TINYINT(2) DEFAULT 0,
  `payment_method_id` VARCHAR(50) NULL,
  `payment_method_txn_id` VARCHAR(100) NULL,
  `payment_method_surcharge` DECIMAL(20,6) UNSIGNED NOT NULL,
  `payment_method_discount` DECIMAL(20,6) UNSIGNED NOT NULL,
  `payment_data` TEXT NULL,
  `payment_amount` DECIMAL(20,6) UNSIGNED NOT NULL,
  `currency_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

ALTER TABLE `#__sr_reservations` ADD `cm_payment_collect` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `cm_channel_order_id`;

ALTER TABLE `#__sr_reservations` ADD `checkinout_status` TINYINT(1) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `#__sr_reservations` ADD `checked_in_date` DATETIME NULL;

ALTER TABLE `#__sr_reservations` ADD `checked_out_date` DATETIME NULL;

UPDATE `#__sr_reservations` SET checkinout_status = 1, state = 5 WHERE state = 1;

UPDATE `#__sr_reservations` SET checkinout_status = 0, state = 5 WHERE state = 2;

ALTER TABLE `#__sr_reservation_assets` ADD `metatitle` VARCHAR(255) NOT NULL AFTER `hits`;

ALTER TABLE `#__sr_reservation_assets` CHANGE `metakey` `metakey` VARCHAR(255) NOT NULL;

ALTER TABLE `#__sr_reservation_assets` CHANGE `metadesc` `metadesc` VARCHAR(255) NOT NULL;

ALTER TABLE `#__sr_reservations` ADD `checked_out` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `created_by`;

ALTER TABLE `#__sr_reservations` ADD `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `checked_out`;
