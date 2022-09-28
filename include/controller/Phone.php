<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');
require_once(INCLUDE_DIR . 'class.orm.php');
require_once(INCLUDE_DIR . 'class.ajax.php');
require_once(INVENTORY_MODEL_DIR . 'PhoneSearch.php');

use Format;
use User;

class Phone extends \AjaxController {

    function lookup() {
        global $thisstaff;

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $phones=array();
        // Bail out of query is empty
        if (!$_REQUEST['q'])
            return $this->json_encode($phones);

        $hits = \model\Phone::objects()
            ->values('phone_model', 'phone_number', 'sim', 'imei', 'color')
            ->order_by(\SqlAggregate::SUM(new \SqlCode('Z1.relevance')), \QuerySet::DESC)
            ->distinct('phone_id')
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

        $phones = array_values($phones);

        return $this->json_encode($phones);
    }

    function addPhone() {
        global $thisstaff;

        $info = array();

        if (!\AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if (!$thisstaff->hasPerm(\User::PERM_CREATE))
                \Http::response(403, 'Permission Denied');

            $info['title'] = __('Add New Phone');
            $form = \model\PhoneForm::getPhoneForm()->getForm($_POST);
            if (($phone = \model\Phone::fromForm($form)))
                \Http::response(201, $phone->to_json(), 'application/json');

            $info['error'] = sprintf('%s - %s', __('Error adding phone'), __('Please try again!'));
        }

        return self::_lookupform($form, $info);
    }

    function editPhone($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            \Http::response(403, 'Permission Denied');
        elseif(!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $info = array(
            'title' => $phone->getModel(),
            'edit_url' => sprintf('#phone/%d/edit', $phone->getId()),
            'post_url' => '#phone/'. $phone->getId(),
            'object' => $phone,
            'object_type' => 'Phone'
        );
        $forms = $phone->getForms();

        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
    }

    function updatePhone($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            \Http::response(403, 'Permission Denied');
        elseif(!($phone = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $errors = array();
        $form = PhoneForm::getPhoneForm()->getForm($_POST);

        if ($phone->updateInfo($_POST, $errors, true) && !$errors)
            \Http::response(201, $phone->to_json(),  'application/json');

        $forms = $phone->getForms();
        include(INVENTORY_VIEWS_DIR . 'phone.tmpl.php');
    }

    function delete($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $info = array(
            'title' => sprintf('%s: %s', __('Delete Phone'), $phone->getModel()),
            'warn' => 'Deleted assets CANNOT be recovered',
            'action' => '#phone/'.$phone->getId().'/delete',
            'id' => $phone->getId(),
            'submit_message' => 'Yes, Delete Phone'
        );
        if ($_POST) {
            if (!$info['error'] && $phone->delete())
                \Http::response(204, 'Phone deleted successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to delete phone'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'deleteAsset.tmpl.php');
    }

    function retire($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $info = array(
            'title' => sprintf('%s: %s', __('Retire Phone'), $phone->getModel()),
            'warn' => 'Retired phones will be hidden. You can reactivate the phones at any time.',
            'action' => '#phone/'.$phone->getId().'/retire',
            'id' => $phone->getId(),
            'submit_message' => 'Yes, Retire Phone'
        );
        if ($_POST) {
            if (!$info['error'] && $phone->retire())
                \Http::response(204, 'Phone retired successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to retire phone'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'retireAsset.tmpl.php');
    }

    function activate($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            \Http::response(403, 'Permission Denied');
        elseif (!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $info = array(
            'title' => sprintf('%s: %s', __('Activate Asset'), $phone->getModel()),
            'action' => '#phone/'.$phone->getId().'/activate',
            'id' => $phone->getId(),
            'submit_message' => 'Yes, Activate Asset'
        );
        if ($_POST) {
            if (!$info['error'] && $phone->activate())
                \Http::response(204, 'Phone activated successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to activate phone'), __('Please try again!'));
        }

        include(INVENTORY_VIEWS_DIR . 'activateAsset.tmpl.php');
    }

    function getPhone($id=false) {

        if(($phone=\model\Phone::lookup(($id) ? $id : $_REQUEST['id'])))
            \Http::response(201, $phone->to_json(), 'application/json');

        $info = array('error' => sprintf(__('%s: Unknown or invalid ID.'), _N('phone', 'phones', 1)));

        return self::_lookupform(null, $info);
    }

    function preview($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif(!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        $info = array(
            'edit_url' => sprintf('#phone/%d/edit', $phone->getId()),
            'post_url' => '#phone/'. $phone->getId(),
            'object' => $phone,
            'object_type' => 'Phone'
        );
        ob_start();
        echo sprintf('<div style="width:650px; padding: 2px 2px 0 5px;"
                id="u%d">', $phone->getId());
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
                $info += array('title' => __('Lookup or create a phone'));
            else
                $info += array('title' => __('Lookup a phone'));
        }

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'addPhone.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function handleAsset() {
        require_once INVENTORY_INCLUDE_DIR.'model/assets.php';
    }

    function handlePhone() {
        require_once INVENTORY_INCLUDE_DIR.'model/phones.php';
    }

    function viewUser($phone_id) {
        global $thisstaff;

        if(!$thisstaff
            || !$phone= \model\Phone::lookup($phone_id))
            \Http::response(404, 'No such asset');

        $user = \User::lookup($phone->getAssigneeID());

        if($user) {
            $file = 'user.tmpl.php';
            $info = array(
                'title' => sprintf(__('%s: %s'), $phone->getModel(),
                    Format::htmlchars($user->getName())),
                'submit_url' => '#phone/users/lookup',
                'object' => $phone,
                'change_url' => '#phone/'.$phone->getId().'/change-user'
            );
        } else {
            $file = 'user-lookup.tmpl.php';
            $info = array(
                'title' => sprintf(__('%s: Unassigned'), $phone->getModel()),
                'onselect' => INVENTORY_WEB_ROOT.'phone/users/select/',
            );
        }

        ob_start();
        include('class.note.php');
        include(INVENTORY_VIEWS_DIR.$file);
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function changeUserForm($phone_id) {
        global $thisstaff;

        if(!$thisstaff
            || !($phone=\model\Phone::lookup($phone_id)))
            \Http::response(404, 'No such phone');


        $user = \User::lookup($phone->getAssigneeID());

        $info = array(
            'title' => sprintf(__('Change user for asset %s'), $phone->getModel()),
            'lookup_url' => 'phone/users/lookup',
            'onselect' => INVENTORY_WEB_ROOT.'phone/users/select/',
            'add_url' => '#phone/users/lookup/form',
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

        $info = array(
            'title' => __('Select User'),
            'submit_url' => '#phone/users/lookup',
            'add_url' => '#asset/users/lookup/form',
        );

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
        if (!($phone = \model\Phone::lookup($id)))
            \Http::response(404, 'Unknown phone');

        require_once INCLUDE_DIR . 'class.ajax.php';
        require_once INCLUDE_DIR . 'ajax.note.php';
        $ajax = new \NoteAjaxAPI();
        return $ajax->createNote('IP'.$id);
    }
}
