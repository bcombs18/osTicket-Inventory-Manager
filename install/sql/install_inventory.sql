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