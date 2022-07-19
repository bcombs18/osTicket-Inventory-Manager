<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once(INCLUDE_DIR.'class.forms.php');

class InventoryConfig extends PluginConfig {

    function getOptions() {
        $form_choices = array();
        foreach (DynamicForm::objects()->filter(array('type'=>'I')) as $group)
        {
            $form_choices[$group->get('id')] = $group->get('title');
        }
        return array(
            'inventory_backend_enable' => new BooleanField(array(
                'id'    => 'inventory_backend_enable',
                'label' => 'Enable Backend',
                'configuration' => array(
                    'desc' => 'Staff backend interface')
            )),
            'inventory_custom_form' => new ChoiceField(array(
                'id'    => 'inventory_custom_form',
                'label' => 'Custom Form Name',
                'choices' => $form_choices,
                'configuration' => array(
                    'desc' => 'Custom form to use for equipment')
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        global $msg;

        if (!$errors)
            $msg = 'Configuration updated successfully';

        return true;
    }
}