<?php

namespace controller;

require_once INCLUDE_DIR . 'class.ajax.php';

class Settings extends \AjaxController {

    function formsPage() {
        require_once INVENTORY_MODEL_DIR.'forms.php';
    }

    function apiPage() {
        require_once INVENTORY_MODEL_DIR.'api.php';
    }

    function getFieldConfiguration($field_id) {
        $field = \DynamicFormField::lookup($field_id);
        include(INVENTORY_VIEWS_DIR . 'dynamic-field-config.tmpl.php');
    }

    function saveFieldConfiguration($field_id) {

        if (!($field = \DynamicFormField::lookup($field_id)))
            \Http::response(404, 'No such field');

        $DFF = 'DynamicFormField';

        // Capture flags which should remain unchanged
        $p_mask = $DFF::MASK_MASK_ALL;
        if ($field->isPrivacyForced()) {
            $p_mask |= $DFF::FLAG_CLIENT_VIEW | $DFF::FLAG_AGENT_VIEW;
        }
        if ($field->isRequirementForced()) {
            $p_mask |= $DFF::FLAG_CLIENT_REQUIRED | $DFF::FLAG_AGENT_REQUIRED;
        }
        if ($field->hasFlag($DFF::FLAG_MASK_DISABLE)) {
            $p_mask |= $DFF::FLAG_ENABLED;
        }

        // Capture current state of immutable flags
        $preserve = $field->flags & $p_mask;

        // Set admin-configured flag states
        $flags = array_reduce($_POST['flags'] ?: array(),
            function($a, $b) { return $a | $b; }, 0);
        $field->flags = $flags | $preserve;

        if ($field->setConfiguration($_POST)) {
            $field->save();
            \Http::response(201, 'Field successfully updated');
        }

        include INVENTORY_VIEWS_DIR . 'dynamic-field-config.tmpl.php';
    }

    function deleteAnswer($entry_id, $field_id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login required');

        $ent = \DynamicFormEntryAnswer::lookup(array(
            'entry_id'=>$entry_id, 'field_id'=>$field_id));
        if (!$ent)
            \Http::response(404, 'Answer not found');

        $ent->delete();
    }

    function getAllFields($id) {
        global $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Login required');
        elseif (!$form = DynamicForm::lookup($id))
            \Http::response(400, 'No such form');

        // XXX: Fetch the form via the list!
        ob_start();
        include STAFFINC_DIR . 'templates/dynamic-form-fields-view.tmpl.php';
        $html = ob_get_clean();

        return $this->encode(array(
            'success'=>true,
            'html' => $html,
        ));
    }
}
