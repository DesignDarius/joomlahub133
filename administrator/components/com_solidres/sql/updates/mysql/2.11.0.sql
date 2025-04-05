ALTER TABLE `#__sr_tariff_details`
ADD `d_interval` TINYINT UNSIGNED NULL DEFAULT '0' AFTER `max_los`,
ADD `limit_checkin` VARCHAR(45) NOT NULL AFTER `d_interval`;

ALTER TABLE `#__sr_tariffs`
CHANGE `limit_checkin` `limit_checkin` VARCHAR(45) NOT NULL DEFAULT '';