ALTER TABLE `#__sr_extras` ADD COLUMN `mandatory` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `#__sr_extras` ADD COLUMN `charge_type` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;
DROP TABLE `#__sr_prices` IF EXISTS;
CREATE  TABLE IF NOT EXISTS `#__sr_tariffs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `currency_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 ,
  `customer_group_id` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT 'price for each user groups' ,
  `valid_from` DATE NOT NULL ,
  `valid_to` DATE NOT NULL ,
  `room_type_id` INT(11) UNSIGNED NULL ,
  `title` VARCHAR(45) NULL ,
  `description` VARCHAR(255) NULL ,
  `d_min` TINYINT NULL ,
  `d_max` TINYINT NULL ,
  `p_min` TINYINT NULL ,
  `p_max` TINYINT NULL ,
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_sr_prices_sr_currencies1_idx` (`currency_id` ASC) ,
  INDEX `fk_sr_prices_sr_room_types1_idx` (`room_type_id` ASC) ,
  INDEX `fk_sr_prices_sr_customer_groups1_idx` (`customer_group_id` ASC) ,
  CONSTRAINT `fk_sr_prices_sr_currencies1`
  FOREIGN KEY (`currency_id` )
  REFERENCES `#__sr_currencies` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_sr_prices_sr_room_types1`
  FOREIGN KEY (`room_type_id` )
  REFERENCES `#__sr_room_types` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_sr_prices_sr_customer_groups1`
  FOREIGN KEY (`customer_group_id` )
  REFERENCES `#__sr_customer_groups` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

CREATE  TABLE IF NOT EXISTS `#__sr_tariff_details` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tariff_id` INT(11) UNSIGNED NOT NULL ,
  `price` DECIMAL(12,2) UNSIGNED NULL ,
  `w_day` TINYINT UNSIGNED NULL ,
  `guest_type` VARCHAR(10) NULL COMMENT 'Adults or children' ,
  `from_age` TINYINT UNSIGNED NULL DEFAULT NULL ,
  `to_age` TINYINT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_sr_tariff_details_sr_tariffs1` (`tariff_id` ASC) ,
  CONSTRAINT `fk_sr_tariff_details_sr_tariffs1`
  FOREIGN KEY (`tariff_id` )
  REFERENCES `#__sr_tariffs` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

ALTER TABLE  `#__sr_room_types` ADD COLUMN `smoking` TINYINT( 2 ) NOT NULL DEFAULT  '0';

ALTER TABLE  `#__sr_reservations` ADD COLUMN `customer_title` VARCHAR( 45 ) NULL;

-- -----------------------------------------------------
-- Table `#__sr_reservation_room_details`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `#__sr_reservation_room_details` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `reservation_room_id` INT(11) UNSIGNED NOT NULL ,
  `key` VARCHAR(255) NULL ,
  `value` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_sr_reservation_room_details_sr_reservation_room_xr1` (`reservation_room_id` ASC) ,
  CONSTRAINT `fk_sr_reservation_room_details_sr_reservation_room_xr1`
  FOREIGN KEY (`reservation_room_id` )
  REFERENCES `#__sr_reservation_room_xref` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `#__sr_reservation_extra_xref`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `#__sr_reservation_extra_xref` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `reservation_id` INT(11) UNSIGNED NOT NULL ,
  `extra_id` INT(11) UNSIGNED NOT NULL ,
  `extra_name` VARCHAR(255) NULL ,
  `extra_quantity` INT(11) NULL ,
  `extra_price` DECIMAL(12,2) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_sr_reservation_extra_xref_sr_reservations1` (`reservation_id` ASC) ,
  INDEX `fk_sr_reservation_extra_xref_sr_extras1` (`extra_id` ASC) ,
  CONSTRAINT `fk_sr_reservation_extra_xref_sr_reservations1`
  FOREIGN KEY (`reservation_id` )
  REFERENCES `#__sr_reservations` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_sr_reservation_extra_xref_sr_sr_extras1`
  FOREIGN KEY (`extra_id` )
  REFERENCES `#__sr_extras` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

-- See 2.3.0.sql
-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_id`  `payment_method_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE `#__sr_reservations` ADD COLUMN `payment_method_txn_id` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `payment_method_id`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `payment_status` TINYINT( 2 ) NULL DEFAULT '0' AFTER `payment_method_txn_id`;
-- -----------------------------------------------------
-- Table `#__sr_reseravtions`
-- -----------------------------------------------------
ALTER TABLE `#__sr_reservations` DROP COLUMN `invoice_number`;
