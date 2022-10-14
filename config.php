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
        );
    }

    function pre_save(&$config, &$errors) {
        global $msg;

        if (!$errors)
            $msg = 'Configuration updated successfully';

        return true;
    }
}