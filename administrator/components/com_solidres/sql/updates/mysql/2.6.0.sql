ALTER TABLE `#__sr_coupons` ADD `scope` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `params`;
ALTER TABLE `#__sr_coupons` CHANGE `reservation_asset_id` `reservation_asset_id` INT(11) UNSIGNED NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS `#__sr_coupon_item_xref` (
  `coupon_id` INT(11) UNSIGNED NOT NULL,
  `item_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `coupon_id`)
)
  ENGINE = InnoDB;
INSERT INTO `#__sr_coupon_item_xref` (coupon_id, item_id) SELECT id, reservation_asset_id FROM `#__sr_coupons`;
UPDATE `#__sr_coupons` SET reservation_asset_id = NULL;