DROP TABLE IF EXISTS `%TABLE_PREFIX%inventory_asset`$
DROP TABLE IF EXISTS `%TABLE_PREFIX%inventory__cdata`$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%update_version`$
DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryFormFields`$

DELETE FROM `%TABLE_PREFIX%_search` WHERE `object_type`='I'$

SET @form_id = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type`='I')$
DELETE FROM `%TABLE_PREFIX%form_entry_values` WHERE `entry_id` IN (SELECT `id` FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id)$
DELETE FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form_field` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form` WHERE `id`=@form_id$

SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Assets')$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `id`=@queue_id$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `parent_id`=@queue_id$

DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Hostname'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Model'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Manufacturer'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Assignee'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Location'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Serial'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Create Date'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Last Update'$

DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Hostname'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Model'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Manufacturer'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Assignee'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Location'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Serial Number'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Create Date'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Last Update'$

DROP TABLE IF EXISTS `%TABLE_PREFIX%inventory_phone`$
DROP TABLE IF EXISTS `%TABLE_PREFIX%inventory_phone__cdata`$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneFormFields`$

DELETE FROM `%TABLE_PREFIX%_search` WHERE `object_type`='IP'$

SET @form_id = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type`='IP')$
DELETE FROM `%TABLE_PREFIX%form_entry_values` WHERE `entry_id` IN (SELECT `id` FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id)$
DELETE FROM `%TABLE_PREFIX%form_entry` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form_field` WHERE `form_id`=@form_id$
DELETE FROM `%TABLE_PREFIX%form` WHERE `id`=@form_id$

SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Phones')$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `id`=@queue_id$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `parent_id`=@queue_id$

SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Active Phones')$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `id`=@queue_id$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `parent_id`=@queue_id$

SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Retired Phones')$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `id`=@queue_id$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `parent_id`=@queue_id$

SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Unassigned Phones')$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `id`=@queue_id$
DELETE FROM `%TABLE_PREFIX%queue` WHERE `parent_id`=@queue_id$

DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Phone Model'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='IMEI'$
DELETE FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Phone Assignee'$

DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Phone Model'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='IMEI'$
DELETE FROM `%TABLE_PREFIX%queue_columns` WHERE `heading`='Phone Assignee'$

ALTER TABLE `%TABLE_PREFIX%api_key` DROP COLUMN `can_create_assets`$
