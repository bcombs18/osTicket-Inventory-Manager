<?php

global $cfg;
global $ost;
global $thisstaff;
global $nav;
global $org;
require(STAFFINC_DIR . 'header.inc.php');

$qs = array();
$assets = \model\Asset::objects();

if ($_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $filter = Q::any(array(
        'host_name__contains' => $search,
        'model__contains' => $search,
        'assignee__contains' => $search,
        'location__contains' => $search
    ));

    $assets->filter($filter);
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('host_name' => 'host_name',
    'model' => 'model',
    'assignee' => 'assignee',
    'location' => 'location'
);
$orderWays = array('DESC'=>'-','ASC'=>'');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'host_name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'host_name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.($order == '' ? 'asc' : 'desc').'" ';

$total = $assets->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$pageNav->paginate($assets);

$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('handle', $qs);
$qstr.='&amp;order='.($order=='-' ? 'ASC' : 'DESC');

//echo $query;
$_SESSION[':Q:assets'] = $assets;

$assets->values('asset_id', 'host_name', 'model', 'assignee', 'location', 'retired');
$assets->order_by($order . $order_column);
?>

<div id="basic_search">
    <div style="min-height:25px;">
        <form action=<?php echo INVENTORY_WEB_ROOT."asset/handle"; ?> method="get">
            <?php csrf_token(); ?>
            <input type="hidden" name="a" value="search">
            <div class="attached input">
                <input type="text" class="basic-search" id="basic-asset-search" name="query"
                         size="30" value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                        autocomplete="off" autocorrect="off" autocapitalize="off">
                <button type="submit" class="attached button"><i class="icon-search"></i>
                </button>
            </div>
        </form>
    </div>
 </div>
<form id="assets-list" action=<?php echo INVENTORY_WEB_ROOT . "asset/handle"; ?> method="POST" name="staff" >

<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Retired Assets'); ?></h2>
            </div>
            <div class="pull-right">
                <?php if ($thisstaff->hasPerm(User::PERM_CREATE)) { ?>
                <a class="green button action-button popup-dialog"
                   href="#asset/add/">
                    <i class="icon-plus-sign"></i>
                    <?php echo __('New Asset'); ?>
                </a>
                <a class="action-button popup-dialog"
                   href="#import/bulk/">
                    <i class="icon-upload"></i>
                    <?php echo __('Import'); ?>
                </a>
                <?php } ?>
                <a class="action-button" href="<?php echo INVENTORY_WEB_ROOT.'dashboard/active'; ?>">
                    <i class="icon-eye-open icon-fixed-width"></i>
                    <?php echo __('View Active'); ?>
                </a>
                <span class="action-button" data-dropdown="#action-dropdown-more"
                      style="/*DELME*/ vertical-align:top; margin-bottom:0">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul>
                        <?php
                        if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                        <li class="danger"><a class="assets-action" href="#delete">
                            <i class="icon-trash icon-fixed-width"></i>
                            <?php echo __('Delete'); ?></a></li>
                        <?php } ?>
                        <li>
                           <a class="assets-action" href="#activate">
                               <i class="icon-archive icon-fixed-width"></i>
                               <?php echo __('Activate'); ?>
                           </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
    <?php
    $showing = $search ? __('Search Results').': ' : '';
    if($assets->exists(true))
        $showing .= $pageNav->showing();
    else
        $showing .= __('No assets found!');
    ?>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <input type="hidden" id="org_id" name="org_id" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th nowrap width="4%">&nbsp;</th>
            <th><a <?php echo $name_sort; ?> href="<?php echo INVENTORY_WEB_ROOT . "asset/handle"; ?>?<?php
                echo $qstr; ?>&sort=host_name"><?php echo __('Hostname'); ?></a></th>
            <th width="22%"><a  <?php echo $status_sort; ?> href="<?php echo INVENTORY_WEB_ROOT . "asset/handle"; ?>?<?php
                echo $qstr; ?>&sort=model"><?php echo __('Model'); ?></a></th>
            <th width="20%"><a <?php echo $create_sort; ?> href="<?php echo INVENTORY_WEB_ROOT . "asset/handle"; ?>?<?php
                echo $qstr; ?>&sort=assignee"><?php echo __('Assignee'); ?></a></th>
            <th width="20%"><a <?php echo $update_sort; ?> href="<?php echo INVENTORY_WEB_ROOT . "asset/handle"; ?>?<?php
                echo $qstr; ?>&sort=location"><?php echo __('Location'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
    $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
    foreach ($assets as $A) {
        $sel=false;
        if($ids && in_array($A['asset_id'], $ids))
            $sel=true;

        if($A['retired'] != 'true')
            continue;
        ?>
        <tr id="<?php echo $A['asset_id']; ?>">
            <td nowrap align="center">
                <input type="checkbox" value="<?php echo $A['asset_id']; ?>" class="ckb mass nowarn"/>
            </td>
            <td>&nbsp;
                <a class="preview"
                   href="<?php echo INVENTORY_WEB_ROOT.'asset/handle'; ?>?id=<?php echo $A['asset_id']; ?>"
                   data-preview="#asset/<?php
                    echo $A['asset_id']; ?>/preview"><?php
                    echo \Format::htmlchars($A['host_name']); ?></a>
                &nbsp;
            </td>
            <td><?php echo \Format::htmlchars($A['model']); ?></td>
            <td><?php echo \Format::htmlchars(\User::getNameById($A['assignee'])); ?></td>
            <td><?php echo \Format::htmlchars($A['location']); ?>&nbsp;</td>
        </tr>
    <?php   } //end of foreach. ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($total) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>
            <?php }else{
                echo '<i>';
                echo __('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($total) {
    echo '<div>';
    echo '<span class="faded pull-right">'.$showing.'</span>';
    echo sprintf('&nbsp;'.__('Page').': %s &nbsp; <a class="no-pjax"
            href="asset/handle?a=export&qh=%s">'.__('Export').'</a></div>',
            $pageNav->getPageLinks(),
            $qhash);
}
?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-asset-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "<?php echo INVENTORY_WEB_ROOT.'/asset/handle?q=';?>"+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = '<?php echo INVENTORY_WEB_ROOT.'asset/handle?id='?>'+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.popup-dialog', function(e) {
        e.preventDefault();
        $.assetLookup($(this).attr('href').substr(1));
        return false;
    });
    var goBaby = function(action, confirmed) {
        var ids = [],
            $form = $('form#assets-list');
        $(':checkbox.mass:checked', $form).each(function() {
            ids.push($(this).val());
        });
        if (ids.length) {
          var submit = function(data) {
            $form.find('#action').val(action);
            $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
            if (data)
              $.each(data, function() { $form.append($('<input type="hidden">').attr('name', this.name).val(this.value)); });
            $form.find('#selected-count').val(ids.length);
            $form.submit();
          };
          var options = {};

          if (!confirmed)
              $.confirm(__('You sure?'), undefined, options).then(function(data) {
                if (data === false)
                  return false;
                submit(data);
              });
          else
              submit();
        }
        else {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.assets-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });

    // Remove CSRF Token From GET Request
    document.querySelector("form[action='<?php echo INVENTORY_WEB_ROOT."asset/handle"; ?>']").onsubmit = function() {
        document.getElementsByName("__CSRFToken__")[0].remove();
    };
});
</script>

<?php
include_once(STAFFINC_DIR.'footer.inc.php');
?>