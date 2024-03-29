<?php

namespace model;

class AssetForm extends \DynamicForm {
    static $instance;
    static $form;

    static $meta = array(
        'table' => TABLE_PREFIX.'form',
        'ordering' => array('title'),
        'pk' => array('id'),
    );

    static $cdata = array(
        'table' => TABLE_PREFIX.'inventory__cdata',
        'object_id' => 'asset_id',
        'object_type' => 'I',
    );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'I'));
    }

    static function getAssetForm() {
        if (!isset(static::$form)) {
            static::$form = static::objects()->one();
        }
        return static::$form;
    }

    static function getInstance() {
        if (!isset(static::$instance))
            static::$instance = static::getAssetForm()->instanciate();
        return static::$instance;
    }

    static function getNewInstance() {
        $o = static::objects()->one();
        static::$instance = $o->instanciate();
        return static::$instance;
    }

    // ensure cdata tables exists
    static function ensureDynamicDataViews($build=true, $force=false) {
        if ($force && $build)
            self::dropDynamicDataView(false);
        self::ensureDynamicDataView($build);
    }

    static function updateDynamicFormEntryAnswer($answer, $data) {
        if (!$answer
            || !($e = $answer->getEntry())
            || !$e->form)
            return;

        switch ($e->form->get('type')) {
            case 'T':
                return TicketForm::updateDynamicDataView($answer, $data);
            case 'A':
                return TaskForm::updateDynamicDataView($answer, $data);
            case 'U':
                return UserForm::updateDynamicDataView($answer, $data);
            case 'O':
                return OrganizationForm::updateDynamicDataView($answer, $data);
            case 'I':
                return AssetForm::updateDynamicDataView($answer, $data);
            case 'P':
                return PhoneForm::updateDynamicDataView($answer, $data);
        }
    }

    static function updateDynamicFormField($field, $data) {
        if (!$field || !$field->form)
            return;

        switch ($field->form->get('type')) {
            case 'T':
                return TicketForm::dropDynamicDataView();
            case 'A':
                return TaskForm::dropDynamicDataView();
            case 'U':
                return UserForm::dropDynamicDataView();
            case 'O':
                return OrganizationForm::dropDynamicDataView();
            case 'I':
                return AssetForm::dropDynamicDataView();
            case 'P':
                return PhoneForm::dropDynamicDataView();
        }
    }
}

// Manage materialized view on custom data updates
\Signal::connect('model.created',
    array('\model\AssetForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');
\Signal::connect('model.updated',
    array('\model\AssetForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');

\Signal::connect('model.created',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField');
\Signal::connect('model.deleted',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField');
// If the `name` column is in the dirty list, we would be renaming as
// column. Delete the view instead.
\Signal::connect('model.updated',
    array('\model\AssetForm', 'updateDynamicFormField'),
    'DynamicFormField',
    function($o, $d) { return isset($d['dirty'])
        && (isset($d['dirty']['name']) || isset($d['dirty']['type'])); });

\Filter::addSupportedMatches(/* @trans */ 'Asset Data', function() {
    $matches = array();
    foreach (AssetForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = __('Asset').' / '.$f->getLabel();
        if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
            foreach ($fi->getSubFields() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = __('Asset').' / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
}, 20);