ALTER TABLE `#__sr_reservation_assets` CHANGE `lat` `lat` DECIMAL(10,6) NULL DEFAULT '0.000000';
ALTER TABLE `#__sr_reservation_assets` CHANGE `lng` `lng` DECIMAL(10,6) NULL DEFAULT '0.000000';
ALTER TABLE `#__sr_reservation_assets` CHANGE `metatitle` `metatitle` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__sr_reservation_assets` CHANGE `metakey` `metakey` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__sr_reservation_assets` CHANGE `metadesc` `metadesc` VARCHAR(255) NOT NULL DEFAULT '';