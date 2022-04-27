SET SQL_SAFE_UPDATES=0$

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%inventory_asset` (
                                                               `asset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                               `host_name` varchar(255) DEFAULT NULL,
                                                               `manufacturer` varchar(255) NOT NULL,
                                                               `model` varchar(255) NOT NULL,
                                                               `serial_number` varchar (255) NOT NULL,
                                                               `location` varchar(255) DEFAULT NULL,
                                                               `assignee` int DEFAULT NULL,
                                                               `retired` varchar(5),
                                                               `created` date NOT NULL,
                                                               `updated` date NOT NULL,
                                                               PRIMARY KEY (`asset_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8$

DELETE FROM `%TABLE_PREFIX%form` WHERE `title`='Inventory'$
INSERT INTO `%TABLE_PREFIX%form` (`type`, `title`, `instructions`, `created`, `updated`)
    VALUES ('G', 'Inventory', 'Dynamic Asset Form: Add form fields to this form to add custom asset data. This form is used for data entry/access and is used by the CSV importer. If the CSV you are using to import data does not contain headers, the columns of the CSV must match the ordering of this form.', NOW(), NOW())$

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
         20481,
         'text',
         'Manufacturer',
         'manufacturer',
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
         20481,
         'text',
         'Model',
         'model',
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
         0,
         'text',
         'Assignee',
         'assignee',
         15,
         NOW(),
         NOW());
    END$

    CALL `%TABLE_PREFIX%CreateInventoryFormFields`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryQueue`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryQueue`()
BEGIN

INSERT INTO `%TABLE_PREFIX%queue`
(`id`,
 `parent_id`,
 `columns_id`,
 `sort_id`,
 `flags`,
 `staff_id`,
 `sort`,
 `title`,
 `config`,
 `filter`,
 `root`,
 `path`,
 `created`,
 `updated`)
VALUES
    (101,
     0,
     NULL,
     8,
     43,
     0,
     7,
     'Assets',
     '[]',
     NULL,
     'U',
     '/',
     NOW(),
     NOW());
END$

CALL `%TABLE_PREFIX%CreateInventoryQueue`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryQueueColumns`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryQueueColumns`()
BEGIN

INSERT INTO `%TABLE_PREFIX%queue_column`
(`flags`,
 `name`,
 `primary`,
 `secondary`,
 `filter`,
 `truncate`,
 `annotations`,
 `conditions`,
 `extra`)
VALUES
    (0,
     'Hostname',
     'host_name',
     NULL,
     NULL,
     '',
     '[]',
     '[]',
     NULL);
INSERT INTO `%TABLE_PREFIX%queue_column`
(`flags`,
 `name`,
 `primary`,
 `secondary`,
 `filter`,
 `truncate`,
 `annotations`,
 `conditions`,
 `extra`)
VALUES
    (0,
     'Model',
     'model',
     NULL,
     NULL,
     '',
     '[]',
     '[]',
     NULL);
INSERT INTO `%TABLE_PREFIX%queue_column`
(`flags`,
 `name`,
 `primary`,
 `secondary`,
 `filter`,
 `truncate`,
 `annotations`,
 `conditions`,
 `extra`)
VALUES
    (0,
     'Assignee',
     'assignee',
     NULL,
     NULL,
     '',
     '[]',
     '[]',
     NULL);
INSERT INTO `%TABLE_PREFIX%queue_column`
(`flags`,
 `name`,
 `primary`,
 `secondary`,
 `filter`,
 `truncate`,
 `annotations`,
 `conditions`,
 `extra`)
VALUES
    (0,
     'Location',
     'location',
     NULL,
     NULL,
     '',
     '[]',
     '[]',
     NULL);
END$

CALL `%TABLE_PREFIX%CreateInventoryQueueColumns`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryQueueColumnsTable`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryQueueColumnsTable`()
BEGIN

    SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Assets');
    SET @hostname_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Hostname');
    SET @model_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Model');
    SET @assignee_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Assignee');
    SET @location_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Location');

INSERT INTO `%TABLE_PREFIX%queue_columns`
(`queue_id`,
 `column_id`,
 `staff_id`,
 `bits`,
 `sort`,
 `heading`,
 `width`)
VALUES
    (@queue_id,
     @hostname_id,
     0,
     1,
     1,
     'Hostname',
     100);
INSERT INTO `%TABLE_PREFIX%queue_columns`
(`queue_id`,
 `column_id`,
 `staff_id`,
 `bits`,
 `sort`,
 `heading`,
 `width`)
VALUES
    (@queue_id,
     @model_id,
     0,
     1,
     2,
     'Model',
     100);
INSERT INTO `%TABLE_PREFIX%queue_columns`
(`queue_id`,
 `column_id`,
 `staff_id`,
 `bits`,
 `sort`,
 `heading`,
 `width`)
VALUES
    (@queue_id,
     @assignee_id,
     0,
     1,
     4,
     'Assignee',
     100);
INSERT INTO `%TABLE_PREFIX%queue_columns`
(`queue_id`,
 `column_id`,
 `staff_id`,
 `bits`,
 `sort`,
 `heading`,
 `width`)
VALUES
    (@queue_id,
     @location_id,
     0,
     1,
     5,
     'Location',
     100);
END$

CALL `%TABLE_PREFIX%CreateInventoryQueueColumnsTable`()$