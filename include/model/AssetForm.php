<?php

namespace model;

class AssetForm extends \DynamicForm {
    static $instance;
    static $form;

    static $meta = array(
        'table' => 'ost_form',
        'ordering' => array('title'),
        'pk' => array('id'),
    );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'G'));
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
}
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