<?php
global $ost;
global $cfg;
require('staff.inc.php');

if (!$thisstaff->hasPerm(User::PERM_DIRECTORY))
    Http::redirect('index.php');

require_once INCLUDE_DIR.'class.note.php';

$asset = null;
if ($_REQUEST['id'] && !($asset=\model\Asset::lookup($_REQUEST['id'])))
    $errors['err'] = sprintf(__('%s: Unknown or invalid'), _N('asset', 'assets', 1));

if ($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$asset) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), _N('end user', 'end users', 1));
            } elseif (!$thisstaff->hasPerm(User::PERM_EDIT)) {
                $errors['err'] = __('Action denied. Contact admin for access');
            } elseif(($acct = $asset->getAccount())
                && !$acct->update($_POST, $errors)) {
                $errors['err']=__('Unable to update user account information');
            } elseif($asset->updateInfo($_POST, $errors)) {
                $msg=sprintf(__('Successfully updated %s.'), __('this end user'));
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this end user')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':
            $form = \model\AssetForm::getUserForm()->getForm($_POST);
            if (($asset = \model\Asset::fromForm($form))) {
                $msg = Format::htmlchars(sprintf(__('Successfully added %s.'), $asset->getHostname()));
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this asset')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'changeuser':
            if (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                $errors['err'] = __('Unknown user selected');
            } elseif ($asset->changeAssignee($user)) {
                $msg = sprintf(__('Asset assigned to %s'),
                    Format::htmlchars($user->getName()));
            } else {
                $errors['err'] = sprintf('%s %s', __('Unable to assign asset.'), __('Please try again!'));
            }
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one end user'));
            } else {
                $assets = \model\Asset::objects()->filter(
                    array('asset_id__in' => $_POST['ids'])
                );
                $count = 0;
                switch (strtolower($_POST['a'])) {
                    case 'delete':
                        foreach ($assets as $A) {
                            if ($A->delete())
                                $count++;
                        }
                        break;

                    case 'retire':
                        foreach ($assets as $A) {
                            if ($A->retire())
                                $count++;
                        }
                        break;

                    case 'activate':
                        foreach ($assets as $A) {
                            if ($A->activate())
                                $count++;
                        }
                        break;

                    default:
                        $errors['err']=sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
                if (!$errors['err'] && !$count) {
                    $errors['err'] = __('Unable to manage any of the selected end users');
                }
                elseif ($_POST['count'] && $count != $_POST['count']) {
                    $warn = __('Not all selected items were updated');
                }
                elseif ($count) {
                    $msg = __('Successfully managed selected assets');
                }


            }
            break;
        case 'import-assets':
            $assetImport = new model\Asset();
            $status = $assetImport->importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if (is_numeric($status))
                $msg = sprintf(__('Successfully imported %1$d %2$s'), $status,
                    _N('asset', 'assets', $status));
            else
                $errors['err'] = $status;
            break;
        default:
            $errors['err'] = __('Unknown action');
            break;
    }
} elseif(!$asset && $_REQUEST['a'] == 'export') {
    require_once(INCLUDE_DIR.'class.export.php');
    $ts = strftime('%Y%m%d');
    if (!($query=$_SESSION[':Q:assets']))
        $errors['err'] = __('Query token not found');
    elseif (!\model\Asset::saveAssets($query, __("assets")."-$ts.csv", 'csv'))
        $errors['err'] = __('Unable to dump query results.')
            .' '.__('Internal error occurred');
}

if($asset) {
    $page = INVENTORY_VIEWS_DIR.'asset-view.inc.php';
} else {
    $page = INVENTORY_VIEWS_DIR.'dashboard.inc.php';
}

$nav->setTabActive('apps');
require($page);
?>
