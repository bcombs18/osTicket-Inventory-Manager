UPDATE `%TABLE_PREFIX%plugin` SET version = '1.1.6' WHERE `name`='Inventory Manager'$

ALTER TABLE `%TABLE_PREFIX%api_key` ADD `can_create_assets` int(1)$