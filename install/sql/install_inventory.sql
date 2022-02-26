SET SQL_SAFE_UPDATES=0$

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%inventory_asset` (
                                                               `asset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                               `host_name` varchar(255) DEFAULT NULL,
                                                               `operating_system` varchar(255) DEFAULT NULL,
                                                               `last_build_date` date DEFAULT NULL,
                                                               `manufacturer` varchar(255) NOT NULL,
                                                               `model` varchar(255) NOT NULL,
                                                               `total_memory` int DEFAULT 0,
                                                               `domain` varchar(45) DEFAULT NULL,
                                                               `logon_server` varchar(45) DEFAULT NULL,
                                                               `serial_number` varchar (255) NOT NULL,
                                                               `warranty_end` date DEFAULT NULL,
                                                               `warranty_start` date DEFAULT NULL,
                                                               `age` int DEFAULT 0,
                                                               `image_id` int DEFAULT NULL,
                                                               `location` varchar(255) DEFAULT NULL,
                                                               `assignee` int DEFAULT NULL,
                                                               `created` date NOT NULL,
                                                               `updated` date NOT NULL,
                                                               PRIMARY KEY (`asset_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8$

DELETE FROM `%TABLE_PREFIX%form` WHERE `title`='Inventory'$
INSERT INTO `%TABLE_PREFIX%form` (`type`, `title`, `notes`, `created`, `updated`)
    VALUES ('G', 'Inventory', 'Inventory internal form', NOW(), NOW())$

INSERT INTO `%TABLE_PREFIX%list`
    (`name`,
     `name_plural`,
     `sort_mode`,
     `type`,
     `created`,
     `updated`)
     SELECT
        'Location',
         'Locations',
         'SortCol',
         'locations',
         NOW(),
         NOW()
    WHERE NOT EXISTS (SELECT `name` FROM `%TABLE_PREFIX%list` WHERE `name`='Location')$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateLocationListItems`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateLocationListItems`()
BEGIN
    SET @list_id = (SELECT `id` FROM `%TABLE_PREFIX%list` WHERE `name`='Location');

    INSERT INTO `%TABLE_PREFIX%list_items`
        (`list_id`,
        `status`,
        `value`,
        `extra`,
        `sort`)
    VALUES
        (@list_id,
         1,
         'Alamosa',
         'AL',
         1);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Boulder',
         'BO',
         2);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Broomfield',
         'BR',
         3);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Cañon City',
         'CC',
         4);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Colorado State Forest',
         'SF',
         5);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Durango',
         'DU',
         6);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Fort Collins',
         'FC',
         7);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Franktown',
         'FR',
         8);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Golder',
         'GO',
         9);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Granby',
         'GR',
         10);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Grand Junction',
         'GJ',
         11);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Gunnison',
         'GU',
         12);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'La Junta',
         'LJ',
         13);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'La Veta',
         'LV',
         14);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Montrose',
         'MO',
         15);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Salida',
         'SA',
         16);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Steamboat Springs',
         'SS',
         17);

    INSERT INTO `%TABLE_PREFIX%list_items`
    (`list_id`,
     `status`,
     `value`,
     `extra`,
     `sort`)
    VALUES
        (@list_id,
         1,
         'Woodland Park',
         'WP',
         18);
    END$

CALL `%TABLE_PREFIX%CreateLocationListItems`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryFormFields`$

CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryFormFields`()
BEGIN
    SET @form_id = (SELECT id FROM `%TABLE_PREFIX%form` WHERE `title`='Inventory');
    SET @location_list = (SELECT id FROM `%TABLE_PREFIX%list` WHERE `name`='Location');

    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         28673,
         'text',
         'Hostname',
         'hostname',
         1,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Operating System',
         'osname',
         2,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Original Install Date',
         'originalinstalldate',
         3,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         1,
         'text',
         'System Manufacturer',
         'systemmanufacturer',
         4,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         1,
         'text',
         'System Model',
         'systemmodel',
         5,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Total Physical Memory',
         'totalphysicalmemory',
         6,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Domain',
         'domain',
         7,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Logon Server',
         'logonserver',
         8,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         20481,
         'text',
         'Serial',
         'serial',
         9,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Warranty End Date',
         'warrantyenddate',
         10,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Warranty Start Date',
         'warrantystartdate',
         11,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'PC Age',
         'pcage',
         12,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Location',
         'location',
         13,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `flags`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         12289,
         'text',
         'Assignee',
         'assignee',
         15,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         'text',
         'Created',
         'created',
         16,
         NOW(),
         NOW());
    INSERT INTO `%TABLE_PREFIX%form_field`
    (`form_id`,
     `type`,
     `label`,
     `name`,
     `sort`,
     `created`,
     `updated`)
    VALUES
        (@form_id,
         'text',
         'Updated',
         'updated',
         17,
         NOW(),
         NOW());
    END$

    CALL `%TABLE_PREFIX%CreateInventoryFormFields`()$