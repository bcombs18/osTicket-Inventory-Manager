<?php

namespace model;

use function MongoDB\BSON\toJSON;

class PhoneModel extends \VerySimpleModel {

    static $meta = array(
        'table' => TABLE_PREFIX.'inventory_phone',
        'pk' => 'phone_id',
        'joins' => array(
            'cdata' => array(
                'constraint' => array('phone_id' => '\model\PhoneCdata.phone_id'),
                'list' => true
            ),
            'entries' => array(
                'constraint' => array(
                    "'IP'" => 'DynamicFormEntry.object_type',
                    'phone_id' => 'DynamicFormEntry.object_id',
                ),
                'list' => true,
            ),
        )
    );

    function getId() {
        return $this->phone_id;
    }

    public function setFlag($flag, $val) {
        if ($val)
            $this->status |= $flag;
        else
            $this->status &= ~$flag;
    }
}

class PhoneCdata extends \VerySimpleModel {
    static $meta = array(
        'table' => TABLE_PREFIX.'inventory_phone__cdata',
        'pk' => array('phone_id'),
        'joins' => array(
            'phone' => array(
                'constraint' => array('phone_id' => '\model\PhoneModel.phone_id'),
            ),
        ),
    );
}

class Phone extends PhoneModel
    implements \TemplateVariable, \Searchable {

    var $_entries;
    var $_forms;

    static function fromVars($vars, $create=true, $update=false) {
        // Try and lookup by Serial Number
        $phone = static::lookupByIMEI($vars['imei']);
        $user = \User::lookupByEmail($vars['assignee']);
        if($user) {
            $user = json_encode(array('name' => $user->getFullName(), 'id' => $user->getId()));
        } else {
            $user = null;
        }
        if (!$phone && $create) {
            $phone = new Asset(array(
                'phone_number' => \Format::htmldecode(\Format::sanitize($vars['phone_number'])),
                'phone_model' => \Format::htmldecode(\Format::sanitize($vars['phone_model'])),
                'sim' => \Format::htmldecode(\Format::sanitize($vars['sim'])),
                'imei' => \Format::htmldecode(\Format::sanitize($vars['imei'])),
                'color' => \Format::htmldecode(\Format::sanitize($vars['location'])),
                'assignee' => $user,
                'retired' => 'false',
                'created' => new \SqlFunction('NOW'),
                'updated' => new \SqlFunction('NOW')
            ));

            try {
                $phone->save(true);
                // Attach initial custom fields
                $phone->addDynamicData($vars);
            }
            catch (OrmException $e) {
                return null;
            }
            $type = array('type' => 'created');
            \Signal::send('object.created', $phone, $type);
        }
        elseif ($update) {
            $errors = array();
            $phone->updateInfo($vars, $errors, true);
        }

        return $phone;
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

    function getPhoneNumber() {
        return $this->phone_number;
    }

    function getModel() {
        return $this->phone_model;
    }

    function getSIM() {
        return $this->sim;
    }

    function getIMEI() {
        return $this->imei;
    }

    function getColor() {
        return $this->color;
    }

    function getAssignee() {
        $assignee = json_decode($this->assignee, true);
        return $assignee['name'];
    }

    function getAssigneeID() {
        $assignee = json_decode($this->assignee, true);
        return $assignee['id'];
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
        $entry->set('object_type', 'IP');
        $entry->set('object_id', $this->getId());
        $entry->save();
        return $entry;
    }

    function to_json() {
        $info = array(
            'phone_id'  => $this->getId(),
            'phone_number' => $this->getPhoneNumber(),
            'phone_model' => $this->getModel(),
            'sim' => $this->getSIM(),
            'imei' => $this->getIMEI(),
            'color' => $this->getColor(),
            'assignee' => \User::lookup($this->getAssigneeID())
        );

        return \Format::json_encode($info);
    }

    function __toString() {
        return $this->asVar();
    }

    function asVar() {
        return (string) $this->getModel();
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
        return $this->addForm(PhoneForm::objects()->one(), 1, $data);
    }

    function getDynamicData($create=true) {
        if (!isset($this->_entries)) {
            $this->_entries = \DynamicFormEntry::forObject($this->phone_id, 'IP')->all();
            if (!$this->_entries && $create) {
                $g = \model\PhoneForm::getNewInstance();
                $g->setClientId($this->phone_id);
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
            $records = $importer->importCsv(PhoneForm::getPhoneForm()->getFields(), $defaults);
            foreach ($records as $data) {
                if (!($phone = static::fromVars($data, true, true)))
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

        return Phone::importCsv($stream, $extra);
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

            if ($entry->getDynamicForm()->get('type') == 'IP') {
                //  Name field
                if (($model = $entry->getField('phone_model')) && $isEditable($model) ) {
                    $model = $model->getClean();
                    if ($this->model != $model) {
                        $type = array('type' => 'edited', 'key' => 'Phone Model');
                        \Signal::send('object.edited', $this, $type);
                    }
                    $this->phone_model = $model;
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

    static function lookupByID($phone_id) {
        return static::lookup(array('phone_id'=>$phone_id));
    }

    static function lookupByIMEI($imei) {
        return static::lookup(array('imei'=>$imei));
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
            'phone_model' => new \TextboxField(array(
                'label' => __('Phone Model')
            )),
            'phone_number' => new \TextboxField(array(
                'label' => __('Phone Number')
            )),
            'sim' => new \TextboxField(array(
                'label' => __('SIM')
            )),
            'assignee' => new \TextboxField(array(
                'label' => __('Assignee')
            )),
            'color' => new \TextboxField(array(
                'label' => __('Color')
            )),
            'imei' => new \TextboxField(array(
                'label' => __('IMEI')
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
        $pform = PhoneForm::getInstance();
        foreach ($pform->getFields() as $F) {
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
                return sprintf(INVENTORY_WEB_ROOT.'phone/handle?id=%s', $id);
        }
    }
}