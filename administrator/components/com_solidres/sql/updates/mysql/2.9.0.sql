INSERT INTO `#__content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES (0, 'Solidres Property', 'com_solidres.property', '{"special":{"dbtable":"#__sr_reservation_assets","key":"id","type":"ReservationAsset","prefix":"SolidresTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}', '', '{"common":{"core_content_item_id":"id","core_title":"name","core_state":"state","core_alias":"alias","core_created_time":"created_date","core_modified_time":"modified_date","core_body":"description", "core_hits":"hits","core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params", "core_featured":"null", "core_metadata":"metadata", "core_language":"language", "core_images":"null", "core_urls":"null", "core_version":"null", "core_ordering":"ordering", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"category_id", "core_xreference":"xreference", "asset_id":"asset_id"}, "special":{}}', 'SolidresHelperRoute::getReservationAssetRoute', '');

ALTER TABLE `#__sr_extras` ADD COLUMN `access` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `scope`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `customer_ua` VARCHAR(255) NOT NULL DEFAULT '' AFTER `customer_language`;
ALTER TABLE `#__sr_reservations` ADD COLUMN `customer_ismobile` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `customer_ua`;

CREATE TABLE IF NOT EXISTS `#__sr_wishlist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NULL DEFAULT '0',
  `scope` TEXT NULL DEFAULT NULL,
  `history` MEDIUMTEXT NULL DEFAULT NULL,
  `created_date` DATETIME NULL DEFAULT NULL,
  `modified_date` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `#__sr_property_staff_xref` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) UNSIGNED NOT NULL,
  `staff_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_property_id` (`property_id`),
  INDEX `idx_staff_id` (`staff_id`)
)
ENGINE = InnoDB;

ALTER TABLE `#__sr_payment_history` ADD UNIQUE(`payment_method_txn_id`);

ALTER TABLE `#__sr_reservations` ADD UNIQUE(`payment_method_txn_id`);

CREATE TABLE IF NOT EXISTS `#__sr_origins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `state` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `tax_id` INT(11) UNSIGNED NULL,
  `is_default` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL,
  `color` VARCHAR(7) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_sr_origins_sr_taxes1_idx` (`tax_id` ASC),
  CONSTRAINT `fk_sr_origins_sr_taxes1`
    FOREIGN KEY (`tax_id`)
    REFERENCES `#__sr_taxes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

ALTER TABLE `#__sr_reservations`
 ADD COLUMN `origin_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `origin`,
 ADD INDEX `fk_sr_reservations_sr_origins1_idx` (`origin_id` ASC),
 ADD CONSTRAINT `fk_sr_reservations_sr_origins1` FOREIGN KEY (`origin_id`) REFERENCES `#__sr_origins`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

INSERT INTO `#__sr_origins` (`id`, `scope`, `state`, `tax_id`, `is_default`, `name`, `color`) VALUES
  (1,0,1,NULL,1,'Website', '#17748b'),
  (2,0,1,NULL,0,'Walk-in', '#05386B'),
  (3,0,1,NULL,0,'Phone', '#64458c'),
  (4,0,1,NULL,0,'Email', '#41B3A3'),
  (5,1,1,NULL,1,'Website', '#17748b'),
  (6,1,1,NULL,0,'Walk-in', '#05386B'),
  (7,1,1,NULL,0,'Phone', '#64458c'),
  (8,1,1,NULL,0,'Email', '#41B3A3');
