<?php

require('staff.inc.php');

$nav->setActiveTab('apps');

global $thisstaff;

require_once STAFFINC_DIR.'header.inc.php';
require_once STAFFINC_DIR.'templates/apps-view.tmpl.php';
require_once STAFFINC_DIR.'footer.inc.php';