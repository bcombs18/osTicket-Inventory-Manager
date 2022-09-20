<?php

namespace model;

class PhoneForm extends \DynamicForm {
    static $instance;
    static $form;

    static $meta = array(
        'table' => TABLE_PREFIX.'form',
        'ordering' => array('title'),
        'pk' => array('id'),
    );

    static $cdata = array(
        'table' => TABLE_PREFIX.'inventory_phone__cdata',
        'object_id' => 'phone_id',
        'object_type' => 'IP',
    );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'IP'));
    }

    static function getPhoneForm() {
        if (!isset(static::$form)) {
            static::$form = static::objects()->one();
        }
        return static::$form;
    }

    static function getInstance() {
        if (!isset(static::$instance))
            static::$instance = static::getPhoneForm()->instanciate();
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

        return self::updateDynamicDataView($answer, $data);
    }

    static function updateDynamicFormField($field, $data) {
        if (!$field || !$field->form)
            return;

        return self::dropDynamicDataView();
    }
}

// Manage materialized view on custom data updates
\Signal::connect('model.created',
    array('\model\PhoneForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');
\Signal::connect('model.updated',
    array('\model\PhoneForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');

\Filter::addSupportedMatches(/* @trans */ 'Phone Data', function() {
    $matches = array();
    foreach (PhoneForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = __('Phone').' / '.$f->getLabel();
        if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
            foreach ($fi->getSubFields() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = __('Phone').' / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
}, 20);