UPDATE `%TABLE_PREFIX%plugin` SET version = '1.1.3' WHERE `name`='Inventory Manager'$

ALTER `%TABLE_PREFIX%inventory_asset` ALTER COLUMN `assignee` varchar(255)$

UPDATE `%TABLE_PREFIX%form` SET `type` = 'I' WHERE `title`='INVENTORY'$

UPDATE `%TABLE_PREFIX%form_entry` SET `object_type` = 'I' WHERE `object_type` = 'G'$

ALTER TABLE `%TABLE_PREFIX%api_key` ADD `can_create_assets` int(1)$