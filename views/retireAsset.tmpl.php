<?php

if (!$info['title'])
    $info['title'] = sprintf('%s: %s', __('Retire Asset'), $asset->getHostname());

$info['warn'] = __('Retired assets will be hidden. You can reactivate the assets at any time.');

?>
<h3 class="drag-handle"><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php

if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>

<div id="asset-info" style="margin:5px;">
    <div class="clear"></div>
    <hr>
    <form method="post" class="asset"
          action="#asset/<?php echo $asset->getId(); ?>/retire">
        <input type="hidden" name="id" value="<?php echo $asset->getId(); ?>" />
        <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="close"
                   value="<?php echo __('No, Cancel'); ?>">
        </span>
            <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Yes, Retire Asset'); ?>">
        </span>
        </p>
    </form>
</div>
<div class="clear"></div>
<script type="text/javascript">
    $(function() {
        $(document).on('click', 'form.asset input.cancel', function (e) {
            e.preventDefault();
            $('div#asset-info').fadeIn();
            return false;
        });
    });
</script>
