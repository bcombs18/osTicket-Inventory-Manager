SET SQL_SAFE_UPDATES=0$
DELETE FROM `%TABLE_PREFIX%inventory`$
DELETE FROM `%TABLE_PREFIX%inventory_category`$
DELETE FROM `%TABLE_PREFIX%inventory_status`$
DELETE FROM `%TABLE_PREFIX%inventory_config`$
DELETE FROM `%TABLE_PREFIX%list` WHERE `name`=`inventory_status`$
DELETE FROM `%TABLE_PREFIX%list` WHERE `name`=`inventory`$
DELETE FROM `%TABLE_PREFIX%form` WHERE `tilte`=`Inventory`$
SET SQL_SAFE_UPDATES = 1$