<?php
if (!isset($info['title']))
    $info['title'] = Format::htmlchars($asset->getHostname());

if ($info['title']) { ?>
    <h3 class="drag-handle"><?php echo $info['title']; ?></h3>
    <b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
    <hr>
    <?php
} else {
    echo '<div class="clear"></div>';
}
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<div id="user-profile" style="display:<?php echo $forms ? 'none' : 'block'; ?>;margin:5px;">
    <div><b><?php
            echo Format::htmlchars($asset->getHostname()); ?></b></div>

    <div class="clear"></div>
    <ul class="tabs" id="user_tabs" style="margin-top:5px">
        <li class="active"><a href="#info-tab"
            ><i class="icon-info-sign"></i>&nbsp;<?php echo __('Asset'); ?></a></li>
    </ul>

    <div id="user_tabs_container">
        <div class="tab_content" id="info-tab">
            <div class="floating-options">
                <?php if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                    <a href="<?php echo $info['useredit'] ?: '#'; ?>" id="edituser" class="action" title="<?php echo __('Edit'); ?>"><i class="icon-edit"></i></a>
                <?php } ?>
            </div>
            <table class="custom-info" width="100%">
                <?php foreach ($asset->getDynamicData() as $entry) {
                    ?>
                    <tr><th colspan="2"><strong><?php
                                echo $entry->getTitle(); ?></strong></td></tr>
                    <?php foreach ($entry->getAnswers() as $a) { ?>
                        <tr><td style="width:30%;"><?php echo Format::htmlchars($a->getField()->get('label'));
                                ?>:</td>
                            <td><?php echo $a->display(); ?></td>
                        </tr>
                    <?php }
                }
                ?>
            </table>
        </div>
    </div>

</div>
<div id="user-form" style="display:<?php echo $forms ? 'block' : 'none'; ?>;">
    <div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp; <?php echo __(
                'Please note that updates will be reflected system-wide.'
            ); ?></p></div>
    <form method="post" class="user" action="#asset/<?php echo $asset->getId();?>">
        <input type="hidden" name="uid" value="<?php echo $asset->getId(); ?>" />
        <table width="100%">
            <?php
            if (!$forms) $forms = $asset->getForms();
            foreach ($forms as $form)
                $form->render();
            ?>
        </table>
        <hr>
        <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="<?php
            echo ($asset) ? 'cancel' : 'close' ?>"  value="<?php echo __('Cancel'); ?>">
        </span>
            <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Update Asset'); ?>">
        </span>
        </p>
    </form>
</div>
<div class="clear"></div>
<script type="text/javascript">
    $(function() {
        $('a#edituser').click( function(e) {
            e.preventDefault();
            if ($(this).attr('href').length > 1) {
                var url = $(this).attr('href').substr(1);
                $.dialog(url, [201, 204], function (xhr) {
                    window.location.href = window.location.href;
                }, {
                    onshow: function() { $('#user-search').focus(); }
                }, true);
            } else {
                $('div#user-profile').hide();
                $('div#user-form').fadeIn();
            }

            return false;
        });

        $(document).on('click', 'form.user input.cancel', function (e) {
            e.preventDefault();
            $('div#user-form').hide();
            $('div#user-profile').fadeIn();
            return false;
        });
    });
</script>
