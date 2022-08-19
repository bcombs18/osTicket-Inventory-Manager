<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($asset)) die('Invalid path');

global $cfg;
global $ost;
global $thisstaff;
global $nav;
global $org;
?>

<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="50%" class="has_bottom_border">
            <h2><a href=<?php echo INVENTORY_WEB_ROOT."asset/handle?id=".$asset->getId(); ?>
                   title="Reload"><i class="icon-refresh"></i> <?php echo Format::htmlchars($asset->getHostname()); ?></a>
                <?php
                    if($asset->isRetired()) {
                        echo '<span style="color:red;">(Retired)</span>';
                    } else {
                        echo '<span style="color:limegreen;">(Active)</span>';
                    }
                ?>
            </h2>
        </td>
        <td width="50%" class="right_align has_bottom_border">
            <?php
            if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                <a id="user-delete" class="red button action-button pull-right user-action"
                   href="#asset/<?php echo $asset->getId(); ?>/delete"><i class="icon-trash"></i>
                    <?php echo __('Delete Asset'); ?></a>
            <?php } ?>
            <?php
            if($asset->isRetired()) { ?>
                <a id="user-delete" class="red button action-button pull-right user-action"
                   href="#asset/<?php echo $asset->getId(); ?>/activate">
                    <i class="icon-archive"></i>
                    <?php echo __('Activate Asset'); ?>
                </a>
            <?php } else { ?>
                <a id="user-delete" class="action-button pull-right user-action"
                   href="#asset/<?php echo $asset->getId(); ?>/retire">
                    <i class="icon-archive"></i>
                    <?php echo __('Retire Asset'); ?>
                </a>
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
                    <th><?php echo __("Serial Number:"); ?></th>
                    <td><?php echo $asset->getSerialNumber(); ?></td>
                </tr>
                <?php foreach ($asset->getDynamicData() as $entry) {
                    $presets = ['Hostname', 'Manufacturer', 'Model', 'Serial', 'Assignee', 'Location'];
                    foreach ($entry->getAnswers() as $a) {
                        if(!in_array($a->getField()->get('label'), $presets)) { ?>
                        <tr><td style="width:30%;"><strong><?php echo Format::htmlchars($a->getField()->get('label'));
                                    ?>:</strong></td>
                            <td><?php echo $a->display(); ?></td>
                        </tr>
                    <?php }
                    }
                }
                ?>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <h2 style="text-align: center">Entry Information</h2>
                <tr><th><?php echo __('User'); ?>:</th>
                    <?php if($asset->getAssignee()) { ?>
                    <td><a href="#asset/<?php echo $asset->getId(); ?>/user"
                           onclick="javascript:
                                   $.userLookup('asset/<?php echo $asset->getId(); ?>/user',
                                   function (user) {
                                   $('#user-'+user.id+'-name').text(user.name);
                                   $('#user-'+user.id+'-email').text(user.email);
                                   $('#user-'+user.id+'-phone').text(user.phone);
                                   $('select#emailreply option[value=1]').text(user.name+' <'+user.email+'>');
                                   });
                                   return false;
                                   "><i class="icon-user"></i> <span id="user-<?php echo $asset->getAssigneeID(); ?>-name"
                            ><?php echo Format::htmlchars($asset->getAssignee());
                                ?></span></a>
                    </td>
                    <?php } else { ?>
                    <td><a class="change-user" href="#asset/<?php echo $asset->getId(); ?>/user"
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
<ul class="clean tabs" id="user-view-tabs">
    <li class="active"><a href="#notes"><i
                    class="icon-pushpin"></i>&nbsp;<?php echo __('Notes'); ?></a></li>
</ul>
<div id="user-view-tabs_container">
    <div class="tab_content" id="notes">
        <?php
        $notes = \model\AssetNote::forAsset($asset);
        $create_note_url = $asset->getId().'/note';
        include INVENTORY_VIEWS_DIR . 'notes.tmpl.php';
        ?>
    </div>
</div>
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

        $(document).on('click.note', '.quicknote .action.assetedit-note', function(e) {
            // Prevent Auto-Scroll to top of page
            e.preventDefault();
            var note = $(this).closest('.quicknote'),
                body = note.find('.body'),
                T = $('<textarea>').text(body.html());
            if (note.closest('.dialog, .tip_box').length)
                T.addClass('no-bar small');
            body.replaceWith(T);
            T.redactor({ focusEnd: true });
            note.find('.action.assetedit-note').hide();
            note.find('.action.assetsave-note').show();
            note.find('.action.assetcancel-edit').show();
            $('#new-note-box').hide();
            return false;
        });
        $(document).on('click.note', '.quicknote .action.assetcancel-edit', function() {
            var note = $(this).closest('.quicknote'),
                T = note.find('textarea'),
                body = $('<div class="body">');
            body.load('asset/note/' + note.data('id'), function() {
                try { T.redactor('stop'); } catch (e) {}
                T.replaceWith(body);
                note.find('.action.assetsave-note').hide();
                note.find('.action.assetcancel-edit').hide();
                note.find('.action.assetedit-note').show();
                $('#new-note-box').show();
            });
            return false;
        });
        $(document).on('click.note', '.quicknote .action.assetsave-note', function() {
            var note = $(this).closest('.quicknote'),
                T = note.find('textarea');
            $.post('note/' + note.data('id'),
                { note: T.redactor('source.getCode') },
                function(html) {
                    var body = $('<div class="body">').html(html);
                    try { T.redactor('stop'); } catch (e) {}
                    T.replaceWith(body);
                    note.find('.action.assetsave-note').hide();
                    note.find('.action.assetcancel-edit').hide();
                    note.find('.action.assetedit-note').show();
                    $('#new-note-box').show();
                },
                'html'
            );
            return false;
        });
        $(document).on('click.note', '.quicknote .assetdelete', function() {
            if (!window.confirm(__('Confirm Deletion')))
                return;
            var that = $(this),
                id = $(this).closest('.quicknote').data('id');
            $.ajax('asset/note/' + id, {
                type: 'delete',
                success: function() {
                    that.closest('.quicknote').animate(
                        {height: 0, opacity: 0}, 'slow', function() {
                            $(this).remove();
                        });
                }
            });
            return false;
        });
        $(document).on('click', '#assetnew-note', function() {
            var note = $(this).closest('.quicknote'),
                T = $('<textarea>'),
                button = $('<input type="button">').val(__('Create'));
            button.click(function() {
                $.post('asset/' + note.data('url'),
                    { note: T.redactor('source.getCode'), no_options: note.hasClass('no-options') },
                    function(response) {
                        T.redactor('stop');
                        T.replaceWith(note);
                        $(response).show('highlight').insertBefore(note.parent());
                        $('.submit', note.parent()).remove();
                    },
                    'html'
                );
            });
            if (note.closest('.dialog, .tip_box').length)
                T.addClass('no-bar small');
            note.replaceWith(T);
            $('<p>').addClass('submit').css('text-align', 'center')
                .append(button).appendTo(T.parent());
            T.redactor({ focusEnd: true });
            return false;
        });
    });
</script>