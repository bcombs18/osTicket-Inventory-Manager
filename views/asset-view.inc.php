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
            <h2><a href=<?php echo INVENTORY_WEB_ROOT."asset/handle?id=".$asset->getId(); ?>
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
                        <b><a href="#asset/<?php echo $asset->getId();
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
                <tr><th><?php echo __('User'); ?>:</th>
                    <?php if($asset->getAssigneeID()) { ?>
                    <td><a href="#inventory/import/<?php echo $asset->getId(); ?>/user"
                           onclick="javascript:
                                   $.userLookup('inventory/import/<?php echo $asset->getId(); ?>/user',
                                   function (user) {
                                   $('#user-'+user.id+'-name').text(user.name);
                                   $('#user-'+user.id+'-email').text(user.email);
                                   $('#user-'+user.id+'-phone').text(user.phone);
                                   $('select#emailreply option[value=1]').text(user.name+' <'+user.email+'>');
                                   });
                                   return false;
                                   "><i class="icon-user"></i> <span id="user-<?php echo $asset->getAssigneeID(); ?>-name"
                            ><?php echo Format::htmlchars(User::getNameById($asset->getAssigneeID()));
                                ?></span></a>
                    </td>
                    <?php } else { ?>
                    <td><a class="change-user" href="#inventory/import/<?php echo $asset->getId(); ?>/user"
                           onclick="javascript:
                                   var aid = 0;
                                   var cid = 0;
                                   var url = $(this).attr('href').substr(1);
                                   $.userLookup(url, function(user) {
                                       if(cid!=user.id
                                           && $('.dialog#confirm-action #changeuser-confirm').length) {
                                           $('#newuser').html(user.name +' &lt;'+user.email+'&gt;');
                                           $('.dialog#confirm-action #action').val('changeuser');
                                           $('#confirm-form').append('<input type=hidden name=user_id value='+user.id+' />');
                                           $('#overlay').show();
                                           $('.dialog#confirm-action .confirm-action').hide();
                                           $('.dialog#confirm-action p#changeuser-confirm')
                                           .show()
                                           .parent('div').show().trigger('click');
                                       }
                                   });
                                   "><i class="icon-user"></i> <span id="user-<?php echo $asset->getAssigneeID(); ?>-name"
                            ><?php echo __("Unassigned"); ?></span></a>
                    </td>
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

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="changeuser-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>change</b> assignee to %s?'),
            '<b><span id="newuser">this guy</span></b>'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <form action="<?php echo INVENTORY_WEB_ROOT;?>inventory/asset/handle?id=<?php echo $asset->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $asset->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
        <hr style="margin-top:1em"/>
        <p class="full-width">
    <span class="buttons pull-left">
        <input type="button" value="<?php echo __('Cancel');?>" class="close">
    </span>
            <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('OK');?>">
    </span>
        </p>
    </form>
    <div class="clear"></div>
</div>

<script type="text/javascript">
    $(function() {
        $(document).on('click', 'a.change-user', function(e) {
            e.preventDefault();

            <?php if(!$asset->getAssigneeID()) {
                $assigneeID = 0;
            } else {
                $assigneeID = $asset->getAssigneeID();
            } ?>

            var aid = <?php echo $assigneeID; ?>;
            var cid = <?php echo $assigneeID; ?>;
            var url = $(this).attr('href').substr(1);
            $.userLookup(url, function(user) {
                if(cid!=user.id
                    && $('.dialog#confirm-action #changeuser-confirm').length) {
                    $('#newuser').html(user.name +' &lt;'+user.email+'&gt;');
                    $('.dialog#confirm-action #action').val('changeuser');
                    $('#confirm-form').append('<input type=hidden name=user_id value='+user.id+' />');
                    $('#overlay').show();
                    $('.dialog#confirm-action .confirm-action').hide();
                    $('.dialog#confirm-action p#changeuser-confirm')
                        .show()
                        .parent('div').show().trigger('click');
                }
            });
        });

        $(document).on('click', 'a.user-action', function(e) {
            e.preventDefault();
            var url = $(this).attr('href').substr(1);
            $.dialog(url, [201, 204], function (xhr) {
                if (xhr.status == 204)
                    window.location.href = 'handle';
                else
                    window.location.href = window.location.href;
                return false;
            }, {
                onshow: function() { $('#user-search').focus(); }
            }, true);
            return false;
        });
    });
</script>

<?php
include_once(STAFFINC_DIR.'footer.inc.php');
?>