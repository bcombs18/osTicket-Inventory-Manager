<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');

use Format;
use model\AssetForm;
use Mpdf\Tag\P;
use User;

class Asset {
    function addAsset() {
        global $thisstaff;

        $info = array();

        if (!\AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if (!$thisstaff->hasPerm(User::PERM_CREATE))
                \Http::response(403, 'Permission Denied');

            $info['title'] = __('Add New User');
            $form = \model\AssetForm::getAssetForm()->getForm($_POST);
            if (($asset = \model\Asset::fromForm($form)))
                \Http::response(201, $asset->to_json(), 'application/json');

            $info['error'] = sprintf('%s - %s', __('Error adding asset'), __('Please try again!'));
        }

        return self::_lookupform($form, $info);
    }

    static function _lookupform($form=null, $info=array()) {
        global $thisstaff;

        if (!$info or !$info['title']) {
            if ($thisstaff->hasPerm(User::PERM_CREATE))
                $info += array('title' => __('Lookup or create an asset'));
            else
                $info += array('title' => __('Lookup an asset'));
        }

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'addAsset.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

}
