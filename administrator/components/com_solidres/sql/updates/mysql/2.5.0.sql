ALTER TABLE `#__sr_extras` CHANGE `reservation_asset_id` `reservation_asset_id` INT(11) UNSIGNED NULL;
ALTER TABLE `#__sr_extras` ADD `experience_id` INT(11) UNSIGNED NULL AFTER `reservation_asset_id`;
ALTER TABLE `#__sr_extras` ADD `scope` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `params`;
ALTER TABLE `#__sr_reservation_assets` ADD `distance_from_city_centre` DECIMAL(4,2) UNSIGNED NULL DEFAULT NULL AFTER `price_includes_tax`;
ALTER TABLE `#__sr_taxes` ADD `tax_exempt_from` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `geo_state_id`;