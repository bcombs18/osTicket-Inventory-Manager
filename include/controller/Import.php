<?php

namespace controller;

use User;

class Import {

    function importAssets() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_CREATE))
            Http::response(403, 'Permission Denied');

        $info = array(
            'title' => __('Import Assets'),
            'action' => '',
            'upload_url' => "inventory/import/bulk?do=import-assets",
        );

        if (!$_POST) {
            include INVENTORY_PLUGIN_ROOT . 'views/assetsImport.php';
        } else {
            require_once INVENTORY_INCLUDE_DIR.'model/assets.php';
        }
    }

    function importPhones() {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_CREATE))
            \Http::response(403, 'Permission Denied');

        $info = array(
            'title' => __('Import Phones'),
            'action' => '',
            'upload_url' => "inventory/import/bulk?do=import-phones",
        );

        if (!$_POST) {
            include INVENTORY_PLUGIN_ROOT . 'views/phonesImport.php';
        } else {
            require_once INVENTORY_INCLUDE_DIR.'model/phones.php';
        }
    }

    function handle() {
        require_once INVENTORY_INCLUDE_DIR.'model/assets.php';
    }
}
