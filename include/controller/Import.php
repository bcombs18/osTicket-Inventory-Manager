<?php

namespace controller;

use Format;
use model\Asset;
use model\AssetForm;
use Mpdf\Tag\P;
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

    function viewUser($asset_id) {
        global $thisstaff;

        if(!$thisstaff
            || !$asset=Asset::lookup($asset_id))
            \Http::response(404, 'No such asset');

        $user = \User::lookup($asset->getAssigneeID());

        if($user) {
            $file = 'user.tmpl.php';
            $info = array(
                'title' => sprintf(__('%s: %s'), $asset->getHostname(),
                    Format::htmlchars($user->getName()))
            );
        } else {
            $file = 'user-lookup.tmpl.php';
            $info = array(
                'title' => sprintf(__('%s: Unassigned'), $asset->getHostname())
            );
        }

        ob_start();
        include('class.note.php');
        include(INVENTORY_VIEWS_DIR.$file);
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function changeUserForm($asset_id) {
        global $thisstaff;

        if(!$thisstaff
            || !($asset=Asset::lookup($asset_id)))
            \Http::response(404, 'No such asset');


        $user = User::lookup($asset->getAssigneeID());

        $info = array(
            'title' => sprintf(__('Change user for asset %s'), $asset->getHostname())
        );

        return self::_userlookup($user, null, $info);
    }

    static function _userlookup($user, $form, $info) {
        global $thisstaff;

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }
}
