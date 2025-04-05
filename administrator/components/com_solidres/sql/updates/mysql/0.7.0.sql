ALTER TABLE `#__sr_reservation_room_xref`
ADD COLUMN `tariff_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `room_price`,
ADD COLUMN `tariff_title` VARCHAR(45) NULL DEFAULT NULL AFTER `tariff_id`,
ADD COLUMN `tariff_description` VARCHAR(255) NULL DEFAULT NULL AFTER `tariff_title`,
ADD INDEX `fk_sr_reservation_room_xref_sr_tariffs1_idx` (`tariff_id` ASC);


ALTER TABLE `#__sr_reservation_room_xref`
ADD CONSTRAINT `fk_sr_reservation_room_xref_sr_tariffs1`
  FOREIGN KEY (`tariff_id`)
  REFERENCES `#__sr_tariffs` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `#__sr_tariffs`
  ADD COLUMN `limit_checkin` VARCHAR(45) NOT NULL AFTER `type`;

ALTER TABLE  `#__sr_extras` ADD COLUMN `params` TEXT NOT NULL ;

ALTER TABLE  `#__sr_coupons` ADD COLUMN `params` TEXT NOT NULL ;

ALTER TABLE  `#__sr_customers` ADD COLUMN `vat_number` VARCHAR( 255 ) NOT NULL ;

ALTER TABLE  `#__sr_reservations` ADD COLUMN `customer_vat_number` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `customer_geo_state_id` ;

ALTER TABLE `#__sr_customers`
  ADD COLUMN `company` VARCHAR(255) NULL DEFAULT NULL AFTER `vat_number`,
  ADD COLUMN `phonenumber` VARCHAR(45) NULL DEFAULT NULL AFTER `company`,
  ADD COLUMN `address1` VARCHAR(255) NULL DEFAULT NULL AFTER `phonenumber`,
  ADD COLUMN `address2` VARCHAR(255) NULL DEFAULT NULL AFTER `address1`,
  ADD COLUMN `city` VARCHAR(45) NULL DEFAULT NULL AFTER `address2`,
  ADD COLUMN `zipcode` VARCHAR(45) NULL DEFAULT NULL AFTER `city`,
  ADD COLUMN `country_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `zipcode`,
  ADD COLUMN `geo_state_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `country_id`,
  ADD INDEX `fk_sr_customers_sr_countries1_idx` (`country_id` ASC),
  ADD INDEX `fk_sr_customers_sr_geo_states1_idx` (`geo_state_id` ASC);

ALTER TABLE `#__sr_customers`
ADD CONSTRAINT `fk_sr_customers_sr_countries1`
  FOREIGN KEY (`country_id`)
  REFERENCES `#__sr_countries` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `#__sr_customers`
ADD CONSTRAINT `fk_sr_customers_sr_geo_states1`
FOREIGN KEY (`geo_state_id`)
REFERENCES `#__sr_geo_states` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `#__sr_reservation_room_xref`
  ADD COLUMN `room_price_tax_incl` DECIMAL( 12, 2 ) UNSIGNED NULL AFTER  `room_price` ,
  ADD COLUMN `room_price_tax_excl` DECIMAL( 12, 2 ) UNSIGNED NULL AFTER  `room_price_tax_incl` ;

-- See 2.7.0.sql
-- ALTER TABLE `#__sr_reservation_room_xref` CHANGE `room_price` `room_price` DECIMAL( 12, 2 ) UNSIGNED NULL DEFAULT NULL ;