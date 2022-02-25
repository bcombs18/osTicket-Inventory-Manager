<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($asset)) die('Invalid path');

global $cfg;
global $ost;
global $thisstaff;
global $nav;
global $org;
require(STAFFINC_DIR . 'header.inc.php');
?>

<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="50%" class="has_bottom_border">
            <h2><a href=<?php echo INVENTORY_WEB_ROOT."import/handle?id=".$asset->getId(); ?>
                   title="Reload"><i class="icon-refresh"></i> <?php echo Format::htmlchars($asset->getHostname()); ?></a></h2>
        </td>
        <td width="50%" class="right_align has_bottom_border">
            <?php
            if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                <a id="user-delete" class="red button action-button pull-right user-action"
                   href="#import/<?php echo $asset->getId(); ?>/delete"><i class="icon-trash"></i>
                    <?php echo __('Delete Asset'); ?></a>
            <?php } ?>
        </td>
    </tr>
</table>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="100%" border="0">
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <h2 style="text-align: center">Asset Information</h2>
                <tr>
                    <th width="150"><?php echo __('Hostname'); ?>:</th>
                    <td>
                        <?php
                        if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                        <b><a href="#import/<?php echo $asset->getId();
                            ?>/edit" class="user-action"><i
                                    class="icon-edit"></i>
                                <?php }
                                echo Format::htmlchars($asset->getHostname());
                                if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                            </a></b>
                    <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __("Manufacturer:"); ?></th>
                    <td><?php echo $asset->getManufacturer() ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Model:"); ?></th>
                    <td><?php echo $asset->getModel(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Operating System:"); ?></th>
                    <td><?php echo $asset->getOS(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Last Build Date:"); ?></th>
                    <td><?php echo $asset->getInstallDate(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Serial Number:"); ?></th>
                    <td><?php echo $asset->getSerialNumber(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Warranty Start:"); ?></th>
                    <td><?php echo $asset->getWarrantyStart(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Warranty End:"); ?></th>
                    <td><?php echo $asset->getWarrantyEnd(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Total Memory:"); ?></th>
                    <td><?php echo $asset->getMemory(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Domain:"); ?></th>
                    <td><?php echo $asset->getDomain(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Logon Server:"); ?></th>
                    <td><?php echo $asset->getLogonServer(); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <h2 style="text-align: center">Entry Information</h2>
                <tr>
                    <th><?php echo __("Assigned To:"); ?></th>
                    <?php $user = \User::lookup($asset->getAssigneeID());
                    if($user) {?>
                        <th>
                            <a href="<?php echo $user->getLink($asset->getAssigneeID())?>">
                                <?php echo $user->getNameById($asset->getAssigneeID())?>
                            </a>
                        </th>
                    <?php } else { ?>
                        <th><?php echo __("Unassigned"); ?></th>
                    <?php } ?>
                </tr>
                <tr>
                    <th><?php echo __("Assigned Location:"); ?></th>
                    <td><?php echo $asset->getLocation(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Created:"); ?></th>
                    <td><?php echo $asset->getCreateDate(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __("Updated:"); ?></th>
                    <td><?php echo $asset->getUpdateDate(); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<div class="clear"></div>

<script type="text/javascript">
    $(function() {
        $(document).on('click', 'a.user-action', function(e) {
            e.preventDefault();
            var url = 'ajax.php/'+$(this).attr('href').substr(1);
            $.dialog(url, [201, 204], function (xhr) {
                if (xhr.status == 204)
                    window.location.href = 'handle';
                else
                    window.location.href = window.location.href;
                return false;
            }, {
                onshow: function() { $('#user-search').focus(); }
            });
            return false;
        });
    });
</script>

<?php
include_once(STAFFINC_DIR.'footer.inc.php');
?>