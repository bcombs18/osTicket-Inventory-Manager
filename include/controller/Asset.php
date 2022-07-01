<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');

use Format;
use \AssetAdhocSearch;
use model\AssetForm;
use \AssetSavedSearch;
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
        return $ajax->createNote('G'.$id);
    }

    function export($id) {
        global $thisstaff;
        require INVENTORY_MODEL_DIR.'AssetSearch.php';

        if (is_numeric($id))
            $queue = AssetSavedSearch::lookup($id);
        else
            $queue = AssetAdhocSearch::load($id);

        if (!$queue)
            \Http::response(404, 'Unknown Queue');

        return $this->queueExport($queue);
    }

    function queueExport(AssetSavedSearch $queue) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Agent login is required');
        elseif (!$queue || !$queue->checkAccess($thisstaff))
            \Http::response(404, 'No such saved queue');

        $errors = array();
        if ($_POST && is_array($_POST['fields'])) {
            // Cache export preferences
            $id = $queue->getId();
            $_SESSION['Export:Q'.$id]['fields'] = $_POST['fields'];
            $_SESSION['Export:Q'.$id]['filename'] = $_POST['filename'];
            $_SESSION['Export:Q'.$id]['delimiter'] = $_POST['csv-delimiter'];
            // Save fields selection if requested
            if ($queue->isSaved() && isset($_POST['save-changes']))
                $queue->updateExports(array_flip($_POST['fields']));

            // Filename of the report
            if (isset($_POST['filename'])
                && ($parts = pathinfo($_POST['filename']))) {
                $filename = $_POST['filename'];
                if (strcasecmp($parts['extension'], 'csv'))
                    $filename ="$filename.csv";
            } else {
                $filename = sprintf('%s Assets-%s.csv',
                    $queue->getName(),
                    strftime('%Y%m%d'));
            }

            try {
                $interval = 5;
                $options = ['filename' => $filename,
                    'interval' => $interval, 'delimiter' => $_POST['csv-delimiter']];
                // Create desired exporter
                $exporter = new \CsvExporter($options);
                // Acknowledge the export
                $exporter->ack();
                // Phew... now we're free to do the export
                // Ask the queue to export to the exporter
                $queue->export($exporter);
                $exporter->finalize();
                // Email the export if it exists
                $exporter->email($thisstaff);
                // Delete the file.
                @$exporter->delete();
                exit;
            } catch (Exception $ex) {
                $errors['err'] = __('Unable to prepare the export');
            }
        }

        include INVENTORY_VIEWS_DIR . 'queue-export.tmpl.php';

    }
}
