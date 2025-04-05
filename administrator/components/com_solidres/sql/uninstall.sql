DROP TABLE IF EXISTS `#__sr_payment_history`;

DROP TABLE IF EXISTS `#__sr_coupon_item_xref`;


DROP TABLE IF EXISTS `#__sr_reservation_extra_xref` ;


DROP TABLE IF EXISTS `#__sr_reservation_room_details` ;


DROP TABLE IF EXISTS `#__sr_config_data` ;


DROP TABLE IF EXISTS `#__sr_reservation_notes_attachments`;


DROP TABLE IF EXISTS `#__sr_reservation_notes`;


DROP TABLE IF EXISTS `#__sr_room_type_fields` ;


DROP TABLE IF EXISTS `#__sr_reservation_room_extra_xref` ;


DROP TABLE IF EXISTS `#__sr_room_type_extra_xref`;


DROP TABLE IF EXISTS `#__sr_room_type_coupon_xref`;


DROP TABLE IF EXISTS `#__sr_extra_coupon_xref`;


DROP TABLE IF EXISTS `#__sr_customer_fields`;


DROP TABLE IF EXISTS `#__sr_reservation_asset_fields` ;


DROP TABLE IF EXISTS `#__sr_media_roomtype_xref` ;


DROP TABLE IF EXISTS `#__sr_media_reservation_assets_xref` ;


DROP TABLE IF EXISTS `#__sr_reservation_room_xref` ;


DROP TABLE IF EXISTS `#__sr_rooms` ;


DROP TABLE IF EXISTS `#__sr_extra_item_xref`;


DROP TABLE IF EXISTS `#__sr_extras` ;


DROP TABLE IF EXISTS `#__sr_media` ;


DROP TABLE IF EXISTS `#__sr_reservations` ;


DROP TABLE IF EXISTS `#__sr_tariff_details` ;


DROP TABLE IF EXISTS `#__sr_tariffs` ;


DROP TABLE IF EXISTS `#__sr_coupons` ;


DROP TABLE IF EXISTS `#__sr_room_types` ;


DROP TABLE IF EXISTS `#__sr_reservation_assets` ;


DROP TABLE IF EXISTS `#__sr_origins`;


DROP TABLE IF EXISTS `#__sr_taxes`;


DROP TABLE IF EXISTS `#__sr_currencies` ;


DROP TABLE IF EXISTS `#__sr_customers`;


DROP TABLE IF EXISTS `#__sr_customer_groups`;


DROP TABLE IF EXISTS `#__sr_geo_states` ;


DROP TABLE IF EXISTS `#__sr_countries` ;


DROP TABLE IF EXISTS `#__sr_statuses` ;


DROP TABLE IF EXISTS `#__sr_wishlist`;


DROP TABLE IF EXISTS `#__sr_property_staff_xref`;


DROP TABLE IF EXISTS `#__sr_queues`;


DROP TABLE IF EXISTS `#__sr_queue_watches`;


DELETE FROM `#__users` WHERE id IN (90001, 90002, 90003, 90004, 90005);


DELETE FROM `#__user_usergroup_map` WHERE user_id IN (90001, 90002, 90003, 90004, 90005);
