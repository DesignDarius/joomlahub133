CREATE TABLE IF NOT EXISTS `#__sr_extra_coupon_xref` (
  `extra_id` INT(11) UNSIGNED NOT NULL,
  `coupon_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`extra_id`, `coupon_id`),
  INDEX `fk_extra_coupon_xref_coupons1_idx` (`coupon_id` ASC),
  CONSTRAINT `fk_extra_coupon_xref_coupons1`
  FOREIGN KEY (`coupon_id`)
  REFERENCES `#__sr_coupons` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_extra_coupon_xref_extras1`
  FOREIGN KEY (`extra_id`)
  REFERENCES `#__sr_extras` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

ALTER TABLE `#__sr_reservation_assets`
  ADD COLUMN `deposit_include_extra_cost` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1 AFTER `deposit_by_stay_length`;
