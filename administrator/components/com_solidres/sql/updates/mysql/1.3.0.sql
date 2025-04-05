ALTER TABLE `#__sr_customers`
ADD COLUMN `api_key` VARCHAR(40) NULL AFTER `geo_state_id`,
ADD COLUMN `api_secret` VARCHAR(40) NULL AFTER `api_key`;