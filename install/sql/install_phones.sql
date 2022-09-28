SET SQL_SAFE_UPDATES=0$

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%inventory_phone` (
                                                               `phone_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                               `phone_number` varchar(255) DEFAULT NULL,
                                                               `phone_model` varchar(255) NOT NULL,
                                                               `sim` varchar(255) NOT NULL,
                                                               `imei` varchar (255) NOT NULL,
                                                               `color` varchar(255) DEFAULT NULL,
                                                               `phone_assignee` varchar(255) DEFAULT NULL,
                                                               `retired` varchar(5),
                                                               `created` date NOT NULL,
                                                               `updated` date NOT NULL,
                                                               PRIMARY KEY (`phone_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8$

DELETE FROM `%TABLE_PREFIX%form` WHERE `title`='Inventory Phone'$
INSERT INTO `%TABLE_PREFIX%form` (`type`, `title`, `instructions`, `created`, `updated`)
    VALUES ('P', 'Inventory Phone', 'Dynamic Asset Form: Add form fields to this form to add custom phone data. This form is used for data entry/access and is used by the CSV importer. If the CSV you are using to import data does not contain headers, the columns of the CSV must match the ordering of this form.', NOW(), NOW())$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneFormFields`$

CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneFormFields`()
BEGIN
    SET @form_id = (SELECT id FROM `%TABLE_PREFIX%form` WHERE `title`='Inventory Phone');

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
         'Phone Model',
         'phone_model',
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
         'Phone Number',
         'phone_number',
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
         20481,
         'text',
         'SIM/ICCID',
         'sim',
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
         20481,
         'text',
         'IMEI',
         'imei',
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
         12289,
         'text',
         'Color',
         'color',
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
         0,
         'text',
         'Phone Assignee',
         'phone_assignee',
         6,
         NOW(),
         NOW());
    END$

    CALL `%TABLE_PREFIX%CreateInventoryPhoneFormFields`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneQueue`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneQueue`()
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
    (105,
     0,
     NULL,
     8,
     47,
     0,
     7,
     'Phones',
     '[]',
     NULL,
     'P',
     '/',
     NOW(),
     NOW());
END$

CALL `%TABLE_PREFIX%CreateInventoryPhoneQueue`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneQueueColumns`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneQueueColumns`()
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
     'Phone Model',
     'phone_model',
     NULL,
     'link:phoneP',
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
     'IMEI',
     'imei',
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
     'Phone Assignee',
     'phone_assignee',
     NULL,
     'link:assignee',
     '',
     '[]',
     '[]',
     NULL);
END$

CALL `%TABLE_PREFIX%CreateInventoryPhoneQueueColumns`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneQueueColumnsTable`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneQueueColumnsTable`()
BEGIN

    SET @queue_id = (SELECT `id` FROM `%TABLE_PREFIX%queue` WHERE `title`='Phones');
    SET @model_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Phone Model');
    SET @imei_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='IMEI');
    SET @assignee_id = (SELECT `id` FROM `%TABLE_PREFIX%queue_column` WHERE `name`='Phone Assignee');

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
     1,
     'Phone Model',
     230);
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
     @imei_id,
     0,
     1,
     2,
     'IMEI',
     230);
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
     3,
     'Phone Assignee',
     230);
END$

CALL `%TABLE_PREFIX%CreateInventoryPhoneQueueColumnsTable`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneActiveQueue`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneActiveQueue`()
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
    (106,
     105,
     NULL,
     8,
     43,
     0,
     7,
     'Active Phones',
     '{"criteria": [["retired", "equal", "false"]], "conditions":[]}',
     NULL,
     'P',
     '/',
     NOW(),
     NOW());
END$

CALL `%TABLE_PREFIX%CreateInventoryPhoneActiveQueue`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneRetiredQueue`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneRetiredQueue`()
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
    (107,
     105,
     NULL,
     8,
     43,
     0,
     7,
     'Retired Phones',
     '{"criteria": [["retired", "equal", "true"]], "conditions":[]}',
     NULL,
     'P',
     '/',
     NOW(),
     NOW());
END$

CALL `%TABLE_PREFIX%CreateInventoryPhoneRetiredQueue`()$

DROP PROCEDURE IF EXISTS `%TABLE_PREFIX%CreateInventoryPhoneUnassignedQueue`$
CREATE PROCEDURE `%TABLE_PREFIX%CreateInventoryPhoneUnassignedQueue`()
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
    (108,
     105,
     NULL,
     8,
     43,
     0,
     7,
     'Unassigned Phones',
     '{"criteria": [["assignee", "equal", null]], "conditions":[]}',
     NULL,
     'P',
     '/',
     NOW(),
     NOW());
END$
CALL `%TABLE_PREFIX%CreateInventoryPhoneUnassignedQueue`()$