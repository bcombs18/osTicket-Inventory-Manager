<?php
/*********************************************************************
    settings.php

    Handles all admin settings.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
global $ost;
global $cfg;
require('staff.inc.php');
require(INVENTORY_MODEL_DIR.'AssetNav.php');
$errors=array();
$settingOptions=array(
    'form' =>
        array(__('Form Settings'), 'settings.form')
);
//Handle a POST.
$target=(isset($_REQUEST['t']) && $settingOptions[$_REQUEST['t']])?$_REQUEST['t']:'form';
$page = false;
if (isset($settingOptions[$target]))
    $page = $settingOptions[$target];

if($page && $_POST && !$errors) {
    if($cfg && $cfg->updateSettings($_POST,$errors)) {
        $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($page[0]));
    } elseif(!$errors['err']) {
        $errors['err'] = sprintf('%s %s',
            __('Unable to update settings.'),
            __('Correct any errors below and try again.'));
    }
}

$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfigInfo());
$ost->addExtraHeader('<meta name="tip-namespace" content="'.$page[1].'" />',
    "$('#content').data('tipNamespace', '".$page[1]."');");

$nav = new \AssetNav($thisstaff);

$nav->setTabActive('apps', (INVENTORY_WEB_ROOT.'settings?t='.$target));
require_once(STAFFINC_DIR.'header.inc.php');
include_once(INVENTORY_VIEWS_DIR."settings-$target.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
?>
