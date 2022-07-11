<div id="the-lookup-form">
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
    <ul class="tabs" id="user-import-tabs">
        <li class="active">
            <a href="#upload">
                <i class="icon-fixed-width icon-cloud-upload"></i>&nbsp;
                <?php echo __('Upload'); ?>
            </a>
        </li>
    </ul>
    <form action="<?php echo $info['action']; ?>" method="post" enctype="multipart/form-data"
          onsubmit="javascript:
              if ($(this).find('[name=import]').val()) {
              $(this).attr('action', '<?php echo $info['upload_url']; ?>');
              $(document).unbind('submit.dialog');
              }">
        <?php echo csrf_token(); ?>
        <div id="asset-import-tabs_container">
            <div class="tab_content" id="upload" style="margin:5px;">
                <h2 style="margin-bottom:10px"><?php echo __('Import a CSV File'); ?></h2>
                <p>
                    <em><?php echo sprintf(__(
                            'Use the columns shown in the table below. To add more fields, visit the Admin Panel -&gt; Manage -&gt; Forms -&gt; %s page to edit the available fields.  Only fields with `variable` defined can be imported.'),
                            \model\AssetForm::getAssetForm()->get('title')
                        ); ?>
                    <strong><br><br><?php echo 'Headers in the CSV MUST match the columns below verbatim to import correctly!'; ?></strong>
                    <strong><br><?php echo 'Assignee must be a user email in order for assets to be linked to user profiles.'; ?></strong>
                </p>
                <table class="list"><tr>
                        <?php
                        $fields = array();
                        $data = array(
                            array('host_name' => __('ExamplePC-1'), 'manufacturer' => __('HP'), 'model' => __('Example Model'), 'serial_number' => __('ABCD123'), 'location' => __('Room 102'), 'assignee' => __('john.doe@example.com'))
                        );
                        foreach (\model\AssetForm::getAssetForm()->getFields() as $f)
                            if ($f->get('name'))
                                $fields[] = $f->get('name');
                        foreach ($fields as $f) { ?>
                            <th><?php echo $f ?></th>
                        <?php } ?>
                    </tr>
                    <?php
                    foreach ($data as $d) {
                        foreach ($fields as $f) {
                            ?><td><?php
                            if (isset($d[$f])) echo $d[$f];
                            ?></td><?php
                        }
                    } ?>
                    </tr></table>
                <br/>
                <input type="file" name="import"/>
            </div>
        </div>
        <hr>
        <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"  value="<?php
            echo __('Cancel'); ?>">
        </span>
            <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Import Assets'); ?>">
        </span>
        </p>
    </form>