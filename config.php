<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once(INCLUDE_DIR.'class.forms.php');

class InventoryConfig extends PluginConfig {

    function getOptions() {
        return array(
            'inventory_backend_enable' => new BooleanField(array(
                'id'    => 'inventory_backend_enable',
                'label' => 'Enable Backend',
                'configuration' => array(
                    'desc' => 'Staff backend interface'),
                'default' => true
            )),
            'inventory_phone_enabled' => new BooleanField(array(
                'id' => 'inventory_phone_enabled',
                'label' => 'Enable Mobile Device Tracking',
                'configuration' => array(
                    'desc' => 'Mobile device tracking'),
                'default' => false
            ))
        );
    }

    function pre_save(&$config, &$errors) {
        global $msg;

        if (!$errors)
            $msg = 'Configuration updated successfully';

        return true;
    }
}