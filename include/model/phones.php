<?php
global $ost;
global $cfg;
require('staff.inc.php');

if (!$thisstaff->hasPerm(\User::PERM_DIRECTORY))
    \Http::redirect('index.php');

require_once INCLUDE_DIR.'class.note.php';
require_once INVENTORY_INCLUDE_DIR.'model/PhoneSearch.php';

$phone = null;
if ($_REQUEST['id'] && !($phone=\model\Phone::lookup($_REQUEST['id'])))
    $errors['err'] = sprintf(__('%s: Unknown or invalid'), _N('phone', 'phones', 1));

// Fetch ticket queues organized by root and sub-queues
$queues = \PhoneSavedQueue::getHierarchicalQueues($thisstaff);

$page='';
$redirect = false;

if (!$phone) {
    $queue_id = null;

    // Basic search (click on 🔍 )
    if (isset($_GET['a']) && $_GET['a'] === 'search'
        && ($_GET['query'])
    ) {
        $wc = mb_str_wc($_GET['query']);
        if ($wc < 4) {
            $key = substr(md5($_GET['query']), -10);
            if ($_GET['search-type'] == 'typeahead') {
                // Use a faster index
                $criteria = ['phone_model', 'equal', $_GET['query']];
            } else {
                $criteria = [':keywords', null, $_GET['query']];
            }
            $_SESSION['advsearch'][$key] = [$criteria];
            $queue_id = "adhoc,{$key}";
        } else {
            $errors['err'] = sprintf(
                __('Search term cannot have more than %d keywords', 4));
        }
    }

    $queue_key = sprintf('::Q:%s', 'P');
    $queue_id = $queue_id ?: @$_GET['queue'] ?: $_SESSION[$queue_key]
        ?? 105 ?: 105;

    // Recover advanced search, if requested
    if (isset($_SESSION['advsearch'])
        && strpos($queue_id, 'adhoc') === 0
    ) {
        list(,$key) = explode(',', $queue_id, 2);
        // For queue=queue, use the most recent search
        if (!$key) {
            reset($_SESSION['advsearch']);
            $key = key($_SESSION['advsearch']);
        }

        $queue = \PhoneAdhocSearch::load($key);
    }

    if ((int) $queue_id && !isset($queue))
        $queue = \PhoneSavedQueue::lookup($queue_id);

    if (!$queue && ($qid=$cfg->getDefaultTicketQueueId()))
        $queue = \PhoneSavedQueue::lookup($qid);

    if (!$queue && $queues)
        list($queue,) = $queues[0];

    if ($queue) {
        // Set the queue_id for navigation to turn a top-level item bold
        $_REQUEST['queue'] = $queue->getId();
        // Make the current queue sticky
        $_SESSION[$queue_key] = $queue->getId();
    }
}

if ($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$phone) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), _N('phone', 'phones', 1));
            } elseif (!$thisstaff->hasPerm(\User::PERM_EDIT)) {
                $errors['err'] = __('Action denied. Contact admin for access');
            } elseif(($acct = $phone->getAccount())
                && !$acct->update($_POST, $errors)) {
                $errors['err']=__('Unable to update phone information');
            } elseif($phone->updateInfo($_POST, $errors)) {
                $msg=sprintf(__('Successfully updated %s.'), __('this phone'));
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this phone')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':
            $form = \model\PhoneForm::getPhoneForm()->getForm($_POST);
            if (($phone = \model\Phone::fromForm($form))) {
                $msg = Format::htmlchars(sprintf(__('Successfully added %s.'), $phone->getModel()));
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this phone')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'changeuser':
            if (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                $errors['err'] = __('Unknown user selected');
            } elseif ($phone->changeAssignee($user)) {
                $msg = sprintf(__('Phone assigned to %s'),
                    Format::htmlchars($user->getName()));
            } else {
                $errors['err'] = sprintf('%s %s', __('Unable to assign phone.'), __('Please try again!'));
            }
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('phone'));
            } else {
                $phones = \model\Phone::objects()->filter(
                    array('phone_id__in' => $_POST['ids'])
                );
                $count = 0;
                switch (strtolower($_POST['a'])) {
                    case 'delete':
                        foreach ($phones as $A) {
                            if ($A->delete())
                                $count++;
                        }
                        break;

                    case 'retire':
                        foreach ($phones as $A) {
                            if ($A->retire())
                                $count++;
                        }
                        break;

                    case 'activate':
                        foreach ($phones as $A) {
                            if ($A->activate())
                                $count++;
                        }
                        break;

                    default:
                        $errors['err']=sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
                if (!$errors['err'] && !$count) {
                    $errors['err'] = __('Unable to manage any of the selected phones');
                }
                elseif ($_POST['count'] && $count != $_POST['count']) {
                    $warn = __('Not all selected items were updated');
                }
                elseif ($count) {
                    $msg = __('Successfully managed selected phones');
                }


            }
            break;
        case 'import-phones':
            $phoneImport = new model\Phone();
            $status = $phoneImport->importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if (is_numeric($status))
                $msg = sprintf(__('Successfully imported %1$d %2$s'), $status,
                    _N('phone', 'phones', $status));
            else
                $errors['err'] = $status;
            break;
        default:
            $errors['err'] = __('Unknown action');
            break;
    }
} elseif(!$phone && $_REQUEST['a'] == 'export') {
    require_once(INCLUDE_DIR.'class.export.php');
    $ts = strftime('%Y%m%d');
    if (!($query=$_SESSION[':Q:phones']))
        $errors['err'] = __('Query token not found');
    elseif (!\model\PhoneExport::savePhones($query, __("phones")."-$ts.csv", 'csv'))
        $errors['err'] = __('Unable to dump query results.')
            .' '.__('Internal error occurred');
}

// Clear advanced search upon request
if (isset($_GET['clear_filter']))
    unset($_SESSION['advsearch']);

$nav->setTabActive('apps');
$nav->addSubNavInfo('jb-overflowmenu', 'customQ_nav');

// Start with all the top-level (container) queues
foreach ($queues as $_) {
    list($q, $children) = $_;
    if ($q->getStatus() != 'Disabled' && $q->getName() != 'Phones')
        continue;
    $nav->addSubMenu(function() use ($q, $queue, $children) {
        // A queue is selected if it is the one being displayed. It is
        // "child" selected if its ID is in the path of the one selected
        $_selected = ($queue && $queue->getId() == $q->getId());
        $child_selected = $queue
            && ($queue->parent_id == $q->getId()
                || false !== strpos($queue->getPath(), "/{$q->getId()}/"));
        $url = 'phone/handlePhone?queue=';
        include INVENTORY_VIEWS_DIR . 'queue-navigation.tmpl.php';

        return ($child_selected || $_selected);
    });
}

// Add my advanced searches
$nav->addSubMenu(function() use ($queue) {
    global $thisstaff;
    $selected = false;
    // A queue is selected if it is the one being displayed. It is
    // "child" selected if its ID is in the path of the one selected
    $child_selected = $queue instanceof SavedSearch;
    $url = 'phone/handlePhone?queue=';
    include INVENTORY_VIEWS_DIR . 'queue-savedsearches-nav.tmpl.php';
    return ($child_selected || $selected);
});

if($phone) {
    $page = INVENTORY_VIEWS_DIR.'phone-view.inc.php';
} else {
    if ($queue) {
        // XXX: Check staff access?
        $page = INVENTORY_VIEWS_DIR.'queue-phones.tmpl.php';
        $quick_filter = @$_REQUEST['filter'];
        $phones = $queue->getQuery(false, $quick_filter);
    }
}

require STAFFINC_DIR.'header.inc.php';
require($page);
require STAFFINC_DIR.'footer.inc.php';
?>
