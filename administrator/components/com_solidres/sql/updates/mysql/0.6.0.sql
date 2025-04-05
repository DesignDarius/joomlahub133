ALTER TABLE  `#__sr_reservations` ADD COLUMN `deposit_amount` DECIMAL( 12, 2 ) UNSIGNED NULL DEFAULT NULL;

-- See 2.7.0.sql
-- ALTER TABLE  `#__sr_reservation_assets` CHANGE `deposit_amount` `deposit_amount` DECIMAL( 12, 2 ) UNSIGNED NULL DEFAULT NULL;

-- ALTER TABLE `#__sr_reservations` DROP `invoice_number`;

ALTER TABLE `#__sr_reservation_assets` DROP FOREIGN KEY `fk_sr_reservation_assets_sr_categories1`;

ALTER TABLE `#__sr_reservation_assets` DROP INDEX `fk_sr_reservation_assets_sr_categories1_idx` ;

ALTER TABLE `#__sr_room_types` CHANGE `ordering` `ordering` INT(11) NOT NULL DEFAULT 0 ;

ALTER TABLE `#__sr_reservation_room_extra_xref`
  DROP INDEX `fk_sr_reservation_room_extra_xref_sr_reservations1` ,
  ADD INDEX `fk_sr_reservation_room_extra_xref_sr_reservations1_idx` (`reservation_id` ASC),
  DROP INDEX `fk_sr_reservation_room_extra_xref_sr_rooms1` ,
  ADD INDEX `fk_sr_reservation_room_extra_xref_sr_rooms1_idx` (`room_id` ASC),
  DROP INDEX `fk_sr_reservation_room_extra_xref_sr_extras1` ,
  ADD INDEX `fk_sr_reservation_room_extra_xref_sr_extras1_idx` (`extra_id` ASC);

ALTER TABLE `#__sr_tariff_details`
  DROP INDEX `fk_sr_tariff_details_sr_tariffs1` ,
  ADD INDEX `fk_sr_tariff_details_sr_tariffs1_idx` (`tariff_id` ASC);

ALTER TABLE `#__sr_reservation_room_details`
  DROP INDEX `fk_sr_reservation_room_details_sr_reservation_room_xr1` ,
  ADD INDEX `fk_sr_reservation_room_details_sr_reservation_room_xr1_idx` (`reservation_room_id` ASC);

ALTER TABLE `#__sr_reservation_extra_xref`
  DROP INDEX `fk_sr_reservation_extra_xref_sr_reservations1` ,
  ADD INDEX `fk_sr_reservation_extra_xref_sr_reservations1_idx` (`reservation_id` ASC);

DROP TABLE IF EXISTS `#__sr_categories` ;
ALTER TABLE  `#__sr_currencies` ADD COLUMN `filter_range` VARCHAR( 255 ) NOT NULL ;

ALTER TABLE  `#__sr_reservation_extra_xref` CHANGE `extra_id`  `extra_id` INT( 11 ) UNSIGNED NULL ;

ALTER TABLE `#__sr_reservations` 
ADD COLUMN `total_paid` DECIMAL(12,2) UNSIGNED NULL DEFAULT NULL AFTER `deposit_amount`;

ALTER TABLE `#__sr_extras` 
ADD COLUMN `tax_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `charge_type`,
ADD INDEX `fk_sr_extras_sr_taxes1_idx` (`tax_id` ASC);

ALTER TABLE `#__sr_extras` 
ADD CONSTRAINT `fk_sr_extras_sr_taxes1`
  FOREIGN KEY (`tax_id`)
  REFERENCES `#__sr_taxes` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `#__sr_reservation_assets` 
ADD COLUMN `tax_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `currency_id`,
ADD INDEX `fk_jos_sr_reservation_assets_jos_sr_taxes1_idx` (`tax_id` ASC);
ALTER TABLE `#__sr_reservation_assets` 
ADD CONSTRAINT `fk_jos_sr_reservation_assets_jos_sr_taxes1`
  FOREIGN KEY (`tax_id`)
  REFERENCES `#__sr_taxes` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
