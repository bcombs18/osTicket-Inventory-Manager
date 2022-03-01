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
                <tr><th><?php echo __('User'); ?>:</th>
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
    <form action="<?php echo INVENTORY_WEB_ROOT;?>inventory/import/handle?id=<?php echo $asset->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
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

<?php
include_once(STAFFINC_DIR.'footer.inc.php');
?>

<script type="text/javascript">
    $(function() {
        $(document).on('click', 'a.change-user', function(e) {
            e.preventDefault();
            var aid = <?php echo $asset->getAssigneeID(); ?>;
            var cid = <?php echo $asset->getAssigneeID(); ?>;
            var url = 'ajax.php/'+$(this).attr('href').substr(1);
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

        $.dialog = function (url, codes, cb, options) {
            options = options||{};

            if (codes && !$.isArray(codes))
                codes = [codes];

            var $popup = $('.dialog#popup');

            $popup.attr('class',
                function(pos, classes) {
                    return classes.replace(/\bsize-\S+/g, '');
                });

            $popup.addClass(options.size ? ('size-'+options.size) : 'size-normal');

            $.toggleOverlay(true);
            $('div.body', $popup).empty().hide();
            $('div#popup-loading', $popup).show()
                .find('h1').css({'margin-top':function() { return $popup.height()/3-$(this).height()/3}});
            $popup.resize().show();
            $('div.body', $popup).load(url, options.data, function () {
                $('div#popup-loading', $popup).hide();
                $('div.body', $popup).slideDown({
                    duration: 300,
                    queue: false,
                    complete: function() {
                        if (options.onshow) options.onshow();
                        $(this).removeAttr('style');
                    }
                });
                $("input[autofocus]:visible:enabled:first", $popup).focus();
                var submit_button = null;
                $(document).off('.dialog');
                $(document).on('click.dialog',
                    '#popup input[type=submit], #popup button[type=submit]',
                    function(e) { submit_button = $(this); });
                $(document).on('submit.dialog', '.dialog#popup form', function(e) {
                    e.preventDefault();
                    var $form = $(this),
                        data = $form.serialize();
                    if (submit_button) {
                        data += '&' + escape(submit_button.attr('name')) + '='
                            + escape(submit_button.attr('value'));
                    }
                    $('div#popup-loading', $popup).show()
                        .find('h1').css({'margin-top':function() { return $popup.height()/3-$(this).height()/3}});
                    $.ajax({
                        type:  $form.attr('method'),
                        url: '<?php echo OST_WEB_ROOT; ?>scp/ajax.php/'+$form.attr('action').substr(1),
                        data: data,
                        cache: false,
                        success: function(resp, status, xhr) {
                            if (xhr && xhr.status && codes
                                && $.inArray(xhr.status, codes) != -1) {
                                $.toggleOverlay(false);
                                $popup.hide();
                                $('div.body', $popup).empty();
                                if (cb && (false === cb(xhr, resp)))
                                    // Don't fire event if callback returns false
                                    return;
                                var done = $.Event('dialog:close');
                                $popup.trigger(done, [resp, status, xhr]);
                            } else {
                                try {
                                    var json = $.parseJSON(resp);
                                    if (json.redirect) return window.location.href = json.redirect;
                                }
                                catch (e) { }
                                $('div.body', $popup).html(resp);
                                if ($('#msg_error, .error-banner', $popup).length) {
                                    $popup.effect('shake');
                                }
                                $('#msg_notice, #msg_error', $popup).delay(5000).slideUp();
                                $('div.tab_content[id] div.error:not(:empty)', $popup).each(function() {
                                    var div = $(this).closest('.tab_content');
                                    $('a[href^="#'+div.attr('id')+'"]').parent().addClass('error');
                                });
                            }
                        }
                    })
                        .done(function() {
                            $('div#popup-loading', $popup).hide();
                        })
                        .fail(function() { });
                    return false;
                });
            });
            if (options.onload) { options.onload(); }
        };
    });
</script>