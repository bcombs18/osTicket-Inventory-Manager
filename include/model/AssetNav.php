<?php

class AssetSettingsNav extends \StaffNav {
    function getSubMenus(){ //Private.
        global $cfg;

        $staff = $this->staff;
        $submenus=array();
        foreach($this->getTabs() as $k=>$tab){
            $subnav=array();
            switch(strtolower($k)){
                case 'tasks':
                    $subnav[]=array('desc'=>__('Tasks'), 'href'=>'tasks.php', 'iconclass'=>'Ticket', 'droponly'=>true);
                    break;
                case 'dashboard':
                    $subnav[]=array('desc'=>__('Dashboard'),'href'=>'dashboard.php','iconclass'=>'logs');
                    $subnav[]=array('desc'=>__('Agent Directory'),'href'=>'directory.php','iconclass'=>'teams');
                    $subnav[]=array('desc'=>__('My Profile'),'href'=>'profile.php','iconclass'=>'users');
                    break;
                case 'users':
                    $subnav[] = array('desc' => __('User Directory'), 'href' => 'users.php', 'iconclass' => 'teams');
                    $subnav[] = array('desc' => __('Organizations'), 'href' => 'orgs.php', 'iconclass' => 'departments');
                    break;
                case 'kbase':
                    $subnav[]=array('desc'=>__('FAQs'),'href'=>'kb.php', 'urls'=>array('faq.php'), 'iconclass'=>'kb');
                    if($staff) {
                        if ($staff->hasPerm(FAQ::PERM_MANAGE))
                            $subnav[]=array('desc'=>__('Categories'),'href'=>'categories.php','iconclass'=>'faq-categories');
                        if ($cfg->isCannedResponseEnabled() && $staff->hasPerm(Canned::PERM_MANAGE, false))
                            $subnav[]=array('desc'=>__('Canned Responses'),'href'=>'canned.php','iconclass'=>'canned');
                    }
                    break;
                case 'apps':
                    $subnav[] = array('desc' => __('Inventory Manager'), 'href' => INVENTORY_WEB_ROOT.'asset/handleAsset');
                    $subnav[] = array('desc' => __('Forms'), 'href' => INVENTORY_WEB_ROOT.'settings/forms', 'iconclass'=>'forms');
                    $subnav[] = array('desc' => __('API'), 'href' => INVENTORY_WEB_ROOT.'settings/api', 'iconclass'=>'api');
                    break;
            }
            if($subnav)
                $submenus[$this->getPanel().'.'.strtolower($k)]=$subnav;
        }

        return $submenus;
    }
}