<form action="<?php echo INVENTORY_WEB_ROOT; ?>settings/forms" method="POST" name="forms">

    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Custom Asset Form'); ?></h2>
            </div>
        </div>
    </div>
    <div class="clear"></div>

    <?php
    $other_forms = DynamicForm::objects()
        ->filter(array('type'=>'I'))
        ->exclude(array('flags__hasbit' => DynamicForm::FLAG_DELETED));

    $page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
    $count = $other_forms->count();
    $pageNav = new Pagenate($count, $page, PAGE_LIMIT);
    $pageNav->setURL(INVENTORY_WEB_ROOT.'settings');
    $showing=$pageNav->showing().' '._N('form','forms',$count);
    ?>

    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="mass_process" >
    <input type="hidden" id="action" name="a" value="" >
    <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
        <tbody>
        <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th><?php echo __('Custom Forms'); ?></th>
            <th><?php echo __('Last Updated'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($other_forms->order_by('title')
                           ->limit($pageNav->getLimit())
                           ->offset($pageNav->getStart()) as $form) {
            $sel=false;
            if($ids && in_array($form->get('id'),$ids))
                $sel=true; ?>
            <tr>
                <td align="center"><?php if ($form->isDeletable()) { ?>
                        <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $form->get('id'); ?>"
                            <?php echo $sel?'checked="checked"':''; ?>>
                    <?php } ?></td>
                <td><a href="?id=<?php echo $form->get('id'); ?>"><?php echo $form->get('title'); ?></a></td>
                <td><?php echo $form->get('updated'); ?></td>
            </tr>
        <?php }
        ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3">
                <?php if($count){ ?>
                    <?php echo __('Select'); ?>:&nbsp;
                    <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
                    <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
                    <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
                <?php } ?>
            </td>
        </tr>
        </tfoot>
    </table>
    <?php
    if ($count) //Show options..
        echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
    ?>

</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__(
                    'Are you sure you want to DELETE %s?'),
                    _N('selected custom form', 'selected custom forms', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
    </p>
    <div class="clear"></div>
</div>
