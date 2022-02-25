DROP TABLE IF EXISTS `%TABLE_PREFIX%inventory_asset`$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%update_version`$
DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryFormFields`$

SET @form_id = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type`='G')$
DELETE FROM `%TABLE_PREFIX%form_entry_values` WHERE `entry_id` IN (SELECT `id` FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id)$
DELETE FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form_field` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form` WHERE `id`=@form_id$
