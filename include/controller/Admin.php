<?php

namespace controller;

require_once INCLUDE_DIR . 'class.ajax.php';

class Admin extends \AjaxController {
    function addQueueColumn($root='\model\Asset') {
        global $ost, $thisstaff;

        if (!$thisstaff)
            \Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            \Http::response(403, 'Access denied');

        $column = new \QueueColumn();
        if ($_POST) {
            $data_form = $column->getDataConfigForm($_POST);
            if ($data_form->isValid()) {
                $column->update($_POST, $root);
                if ($column->save())
                    \Http::response(201, $this->encode(array(
                        'id' => $column->getId(),
                        'name' => (string) $column->getName(),
                    ), 'application/json'));
            }
        }

        include INVENTORY_VIEWS_DIR . 'queue-column-add.tmpl.php';

    }

}