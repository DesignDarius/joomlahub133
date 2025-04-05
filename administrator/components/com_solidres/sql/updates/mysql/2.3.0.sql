ALTER TABLE `#__sr_tariff_details`
  ADD `min_los` INT(11) UNSIGNED NULL DEFAULT NULL  AFTER `date`,
  ADD `max_los` INT(11) UNSIGNED NULL DEFAULT NULL  AFTER `min_los`;

-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_id` `payment_method_id` VARCHAR(50) NOT NULL DEFAULT '0';

-- ALTER TABLE `#__sr_reservations` CHANGE `payment_method_txn_id` `payment_method_txn_id` VARCHAR(100) NULL DEFAULT NULL;

-- ALTER TABLE `#__sr_reservations` CHANGE `code` `code` VARCHAR(100) NOT NULL;

-- ALTER TABLE `#__sr_reservations` CHANGE `customer_vat_number` `customer_vat_number` VARCHAR(50) NULL DEFAULT NULL;

-- ALTER TABLE `#__sr_reservations` CHANGE `origin` `origin` VARCHAR(50) NULL DEFAULT NULL;