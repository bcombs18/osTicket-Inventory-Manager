<?php

namespace model;

use function MongoDB\BSON\toJSON;

class AssetModel extends \VerySimpleModel {

    static $meta = array(
        'table' => TABLE_PREFIX.'inventory_asset',
        'pk' => 'asset_id',
        'joins' => array(
            'cdata' => array(
                'constraint' => array('asset_id' => '\model\AssetCdata.asset_id'),
                'list' => true
            ),
            'entries' => array(
                'constraint' => array(
                    "'I'" => 'DynamicFormEntry.object_type',
                    'asset_id' => 'DynamicFormEntry.object_id',
                ),
                'list' => true,
            ),
        )
    );

    function getId() {
        return $this->asset_id;
    }

    public function setFlag($flag, $val) {
        if ($val)
            $this->status |= $flag;
        else
            $this->status &= ~$flag;
    }
}

class AssetCdata extends \VerySimpleModel {
    static $meta = array(
        'table' => TABLE_PREFIX.'inventory__cdata',
        'pk' => array('asset_id'),
        'joins' => array(
            'asset' => array(
                'constraint' => array('asset_id' => '\model\AssetModel.asset_id'),
            ),
        ),
    );
}

class Asset extends AssetModel
    implements \TemplateVariable, \Searchable {

    var $_entries;
    var $_forms;

    static function fromVars($vars, $create=true, $update=false) {
        // Try and lookup by Serial Number
        $asset = static::lookupBySerial($vars['serial_number']);
        $user = \User::lookupByEmail($vars['assignee']);
        if($user) {
            $user = json_encode(array('name' => $user->getFullName(), 'id' => $user->getId()));
        } else {
            $user = null;
        }
        if (!$asset && $create) {
            $asset = new Asset(array(
                'host_name' => \Format::htmldecode(\Format::sanitize($vars['host_name'])),
                'manufacturer' => \Format::htmldecode(\Format::sanitize($vars['manufacturer'])),
                'model' => \Format::htmldecode(\Format::sanitize($vars['model'])),
                'serial_number' => \Format::htmldecode(\Format::sanitize($vars['serial_number'])),
                'location' => $vars['location'],
                'assignee' => $user,
                'retired' => 'false',
                'created' => new \SqlFunction('NOW'),
                'updated' => new \SqlFunction('NOW')
            ));

            try {
                $asset->save(true);
                // Attach initial custom fields
                $asset->addDynamicData($vars);
            }
            catch (OrmException $e) {
                return null;
            }
            $type = array('type' => 'created');
            \Signal::send('object.created', $asset, $type);
        }
        elseif ($update) {
            $errors = array();
            $asset->updateInfo($vars, $errors, true);
        }

        return $asset;
    }

    static function fromForm($form, $create=true) {
        global $thisstaff;

        if(!$form) return null;

        //Validate the form
        $valid = true;
        $filter = function($f) use ($thisstaff) {
            return !isset($thisstaff) || $f->isRequiredForStaff() || $f->isVisibleToStaff();
        };

        if (!$form->isValid($filter))
            $valid  = false;

        return $valid ? self::fromVars($form->getClean(), $create) : null;
    }

    function getHostname() {
        return $this->host_name;
    }

    function getModel() {
        return $this->model;
    }

    function getManufacturer() {
        return $this->manufacturer;
    }

    function getSerialNumber() {
        return $this->serial_number;
    }

    function getAssignee() {
        $assignee = json_decode($this->assignee, true);
        return $assignee['name'];
    }

    function getAssigneeID() {
        $assignee = json_decode($this->assignee, true);
        return $assignee['id'];
    }

    function getLocation() {
        if($this->location) {
            return $this->location;
        } else {
            return "Location Not Assigned";
        }
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function activate() {
        $this->retired = 'false';
        return $this->save(true);
    }

    function retire() {
        $this->retired = 'true';
        return $this->save(true);
    }

    function isRetired() {
        if($this->retired == 'false') {
            return false;
        }
        return true;
    }

    function addForm($form, $sort=1, $data=null) {
        $entry = $form->instanciate($sort, $data);
        $entry->set('object_type', 'I');
        $entry->set('object_id', $this->getId());
        $entry->save();
        return $entry;
    }

    function to_json() {
        $info = array(
            'asset_id'  => $this->getId(),
            'host_name' => $this->getHostname(),
            'manufacturer' => $this->getManufacturer(),
            'model' => $this->getModel(),
            'serial_number' => $this->getSerialNumber(),
            'location' => $this->getLocation(),
            'assignee' => \User::lookup($this->getAssigneeID())
        );

        return \Format::json_encode($info);
    }

    function __toString() {
        return $this->asVar();
    }

    function asVar() {
        return (string) $this->getName();
    }

    function getVar($tag) {
        $tag = mb_strtolower($tag);
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer($tag))
                return $a;
    }

    static function supportsCustomData() {
        return true;
    }

    function addDynamicData($data) {
        return $this->addForm(AssetForm::objects()->one(), 1, $data);
    }

    function getDynamicData($create=true) {
        if (!isset($this->_entries)) {
            $this->_entries = \DynamicFormEntry::forObject($this->asset_id, 'I')->all();
            if (!$this->_entries && $create) {
                $g = \model\AssetForm::getNewInstance();
                $g->setClientId($this->asset_id);
                $g->save();
                $this->_entries[] = $g;
            }
        }

        return $this->_entries ?: array();
    }

    function getForms($data=null, $cb=null) {

        if (!isset($this->_forms)) {
            $this->_forms = array();
            $cb = $cb ?: function ($f) use($data) { return ($data); };
            foreach ($this->getDynamicData() as $entry) {
                $entry->addMissingFields();

                $this->_forms[] = $entry;
            }
        }

        return $this->_forms;
    }

    static function importCsv($stream, $defaults=array()) {
        require_once INCLUDE_DIR . 'class.import.php';

        $importer = new \CsvImporter($stream);
        $imported = 0;
        try {
            db_autocommit(false);
            $records = $importer->importCsv(AssetForm::getAssetForm()->getFields(), $defaults);
            foreach ($records as $data) {
                if (!($asset = static::fromVars($data, true, true)))
                    throw new \ImportError(sprintf(__('Unable to import asset: %s'),
                        print_r(Format::htmlchars($data), true)));
                $imported++;
            }
            db_autocommit(true);
        }
        catch (\Exception $ex) {
            db_rollback();
            return $ex->getMessage();
        }
        return $imported;
    }

    function importFromPost($stream, $extra=array()) {
        if (!is_array($stream))
            $stream = sprintf('name, email%s %s',PHP_EOL, $stream);

        return Asset::importCsv($stream, $extra);
    }

    function updateInfo($vars, &$errors, $staff=false) {
        $isEditable = function ($f) use($staff) {
            return ($staff ? $f->isEditableToStaff() :
                $f->isEditableToUsers());
        };
        $valid = true;
        $forms = $this->getForms($vars, $isEditable);
        foreach ($forms as $entry) {
            $entry->setSource($vars);
            if ($staff && !$entry->isValidForStaff(true))
                $valid = false;
            elseif (!$staff && !$entry->isValidForClient(true))
                $valid = false;

            if (!$valid)
                $errors = array_merge($errors, $entry->errors());
        }


        if (!$valid)
            return false;

        // Save the entries
        foreach ($forms as $entry) {
            $fields = $entry->getFields();
            foreach ($fields as $field) {
                $changes = $field->getChanges();
                if ((is_array($changes) && $changes[0]) || $changes && !is_array($changes)) {
                    $type = array('type' => 'edited', 'key' => $field->getLabel());
                    \Signal::send('object.edited', $this, $type);
                }
            }

            if ($entry->getDynamicForm()->get('type') == 'I') {
                //  Name field
                if (($hostname = $entry->getField('host_name')) && $isEditable($hostname) ) {
                    $hostname = $hostname->getClean();
                    if ($this->host_name != $hostname) {
                        $type = array('type' => 'edited', 'key' => 'Hostname');
                        \Signal::send('object.edited', $this, $type);
                    }
                    $this->host_name = $hostname;
                }

                if (($location = $entry->getField('location')) && $isEditable($location) ) {
                    $location = $location->getClean();
                    if ($this->location != $location) {
                        $type = array('type' => 'edited', 'key' => 'Location');
                        \Signal::send('object.edited', $this, $type);
                    }
                    $this->location = $location;
                }
            }

            // DynamicFormEntry::saveAnswers returns the number of answers updated
            if ($entry->saveAnswers($isEditable)) {
                $this->updated = \SqlFunction::NOW();
            }
        }

        return $this->save();
    }


    function save($refetch=false) {
        return parent::save($refetch);
    }

    function delete() {
        $type = array('type' => 'deleted');
        \Signal::send('object.deleted', $this, $type);

        // Delete asset
        return parent::delete();
    }

    static function lookupByID($asset_id) {
        return static::lookup(array('asset_id'=>$asset_id));
    }

    static function lookupBySerial($serial) {
        return static::lookup(array('serial_number'=>$serial));
    }

    function changeAssignee($user) {
        global $thisstaff;

        if (!$user || ($user->getId() == $this->getAssigneeID())) {
            return false;
        }
        $errors = array();
        $this->assignee = json_encode(array('name' => $user->getFullname(), 'id' => $user->getId()));
        if (!$this->save())
            return false;
        unset($this->user);
        return true;
    }

    static function getSearchableFields() {
        global $thisstaff;

        $base = array(
            'host_name' => new \TextboxField(array(
                'label' => __('Hostname')
            )),
            'manufacturer' => new \TextboxField(array(
                'label' => __('Manufacturer')
            )),
            'model' => new \TextboxField(array(
                'label' => __('Model')
            )),
            'assignee' => new \TextboxField(array(
                'label' => __('Assignee')
            )),
            'location' => new \TextboxField(array(
                'label' => __('Location')
            )),
            'serial_number' => new \TextboxField(array(
                'label' => __('Serial Number')
            )),
            'retired' => new \BooleanField(array(
                'label' => __('Is Retired')
            )),
            'created' => new \DatetimeField(array(
                'label' => __('Create Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'lastupdate' => new \DatetimeField(array(
                'label' => __('Last Update'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
        );
        $aform = AssetForm::getInstance();
        foreach ($aform->getFields() as $F) {
            $fname = $F->get('name') ?: ('field_'.$F->get('id'));
            if (!$F->hasData() || $F->isPresentationOnly() || !$F->isEnabled())
                continue;
            if (!$F->isStorable())
                $base[$fname] = $F;
            else
                $base["cdata__{$fname}"] = $F;
        }
        return $base;
    }

    static function getVarScope()
    {
        // TODO: Implement getVarScope() method.
    }

    static function getLink($id) {
        global $thisstaff;

        switch (true) {
            case ($thisstaff instanceof \Staff):
                return sprintf(INVENTORY_WEB_ROOT.'asset/handle?id=%s', $id);
        }
    }
}