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
            require_once 'model\assets.php';
        }
    }

    function handle() {
        require_once 'model\assets.php';
    }
}
