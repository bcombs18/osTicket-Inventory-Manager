<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');
require_once(INCLUDE_DIR . 'class.orm.php');
require_once(INCLUDE_DIR . 'class.ajax.php');
require_once(INVENTORY_MODEL_DIR . 'AssetSearch.php');

use Format;
use \AssetAdhocSearch;
use model\AssetForm;
use \AssetSavedSearch;
use User;

class Asset extends \AjaxController {

    function lookup() {
        global $thisstaff;

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $assets=array();
        // Bail out of query is empty
        if (!$_REQUEST['q'])
            return $this->json_encode($assets);

        $hits = \model\Asset::objects()
            ->values('host_name', 'model', 'manufacturer', 'location', 'serial_number')
            ->order_by(\SqlAggregate::SUM(new \SqlCode('Z1.relevance')), \QuerySet::DESC)
            ->distinct('asset_id')
            ->limit($limit);

        $q = $_REQUEST['q'];

        if (strlen(\Format::searchable($q)) < 3)
            return $this->encode(array());

        $searcher = new \AssetMysqlSearchBackend();
        $hits = $searcher->find($q, $hits, false);

        if (!count($hits) && preg_match('`\w$`u', $q)) {
            // Do wild-card fulltext search
            $_REQUEST['q'] = $q.'*';
            return $this->lookup();
        }

        $assets = array_values($assets);

        return $this->json_encode($assets);
    }

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

    function editAsset($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($asset = \model\Asset::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
            'title' => sprintf(__('Update %s'), Format::htmlchars($asset->getHostname()))
        );
        $forms = $asset->getForms();

        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
    }

    function updateAsset($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            \Http::response(403, 'Permission Denied');
        elseif(!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown asset');

        $errors = array();
        $form = AssetForm::getAssetForm()->getForm($_POST);

        if ($asset->updateInfo($_POST, $errors, true) && !$errors)
            \Http::response(201, $asset->to_json(),  'application/json');

        $forms = $asset->getForms();
        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
    }

    function delete($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown user');

        $info = array();
        if ($_POST) {
            if (!$info['error'] && $asset->delete())
                \Http::response(204, 'Asset deleted successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to delete asset'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'deleteAsset.tmpl.php');
    }

    function retire($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown user');

        $info = array();
        if ($_POST) {
            if (!$info['error'] && $asset->retire())
                \Http::response(204, 'Asset retired successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to retire asset'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'retireAsset.tmpl.php');
    }

    function activate($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown user');

        $info = array();
        if ($_POST) {
            if (!$info['error'] && $asset->activate())
                \Http::response(204, 'Asset activated successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to activate asset'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'activateAsset.tmpl.php');
    }

    function getAsset($id=false) {

        if(($asset=\model\Asset::lookup(($id) ? $id : $_REQUEST['id'])))
            Http::response(201, $asset->to_json(), 'application/json');

        $info = array('error' => sprintf(__('%s: Unknown or invalid ID.'), _N('asset', 'assets', 1)));

        return self::_lookupform(null, $info);
    }

    function preview($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif(!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown asset');

        $info = array(
            'title' => '',
            'assetedit' => sprintf('#asset/%d/edit', $asset->getId()),
        );
        ob_start();
        echo sprintf('<div style="width:650px; padding: 2px 2px 0 5px;"
                id="u%d">', $asset->getId());
        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
        echo '</div>';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;

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

    function handle() {
        require_once INVENTORY_INCLUDE_DIR.'model/assets.php';
    }

    function viewUser($asset_id) {
        global $thisstaff;

        if(!$thisstaff
            || !$asset= \model\Asset::lookup($asset_id))
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
            || !($asset=\model\Asset::lookup($asset_id)))
            \Http::response(404, 'No such asset');


        $user = \User::lookup($asset->getAssigneeID());

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

    function getUser($id=false) {

        if(($user=\User::lookup(($id) ? $id : $_REQUEST['id'])))
            \Http::response(201, $user->to_json(), 'application/json');

        $info = array('error' => sprintf(__('%s: Unknown or invalid ID.'), _N('end user', 'end users', 1)));

        return self::_lookupUserForm(null, $info);
    }

    function selectUser($id) {
        global $thisstaff;

        if ($id)
            $user = \User::lookup($id);

        $info = array('title' => __('Select User'));

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    static function _lookupUserForm($form=null, $info=array()) {
        global $thisstaff;

        if (!$info or !$info['title']) {
            if ($thisstaff->hasPerm(\User::PERM_CREATE))
                $info += array('title' => __('Lookup or create a user'));
            else
                $info += array('title' => __('Lookup a user'));
        }

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function lookupUser() {
        return self::addUser();
    }

    static function addUser() {
        global $thisstaff;

        $info = array();

        if (!\AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if (!$thisstaff->hasPerm(User::PERM_CREATE))
                \Http::response(403, 'Permission Denied');

            $info['title'] = __('Add New User');
            $form = \UserForm::getUserForm()->getForm($_POST);
            if (!is_string($form->getField('name')->getValue()))
                \Http::response(404, 'Invalid Data');
            if (($user = User::fromForm($form)))
                \Http::response(201, $user->to_json(), 'application/json');

            $info['error'] = sprintf('%s - %s', __('Error adding user'), __('Please try again!'));
        }

        return self::_lookupUserForm($form, $info);
    }

    function createNote($id) {
        if (!($asset = \model\Asset::lookup($id)))
            Http::response(404, 'Unknown asset');

        require_once INCLUDE_DIR . 'class.ajax.php';
        require_once INCLUDE_DIR . 'ajax.note.php';
        $ajax = new \NoteAjaxAPI();
        return $ajax->createNote('I'.$id);
    }
}
