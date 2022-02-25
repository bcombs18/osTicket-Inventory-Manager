<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');

abstract class Controller {

    public function viewAction() {
        include INVENTORY_VIEWS_DIR.'dashboard.inc.php';
    }
}