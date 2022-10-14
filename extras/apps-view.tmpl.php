<form action="plugins.php" method="POST" name="forms">

    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Currently Installed Applications'); ?></h2>
            </div>
        </div>
    </div>
    <div class="clear"></div>

    <?php
    $app = new Application();
    $staffapps = $app->getStaffApps();
    $page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
    $count = count($staffapps);
    $pageNav = new Pagenate($count, $page, PAGE_LIMIT);
    $pageNav->setURL('apps.php');
    $showing=$pageNav->showing().' '._N('application', 'applications', $count);
    ?>

    <form action="" method="POST" name="">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="mass_process" >
        <input type="hidden" id="action" name="a" value="" >
        <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
            <thead>
            <tr>
                <th width="55%"><?php echo __('Application Name'); ?></th>
                <th width="10%"><?php echo __('Version'); ?></th>
                <th width="20%"><?php echo __('Date Installed'); ?></th>
                <?php if($thisstaff->isAdmin()) { ?>
                    <th width="15%"><?php echo __('Plugin Settings'); ?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($staffapps as $p) {
                    foreach ($ost->plugins->allInstalled() as $a) { ?>
                    <tr>
                        <td><a href="<?php echo $p['href']; ?>">
                                <?php echo $p['desc']; ?></a></td>
                        <td><?php echo $a->getVersion(); ?></a></td>
                        <td><?php echo Format::datetime($a->getInstallDate()); ?></td>
                        <?php if($thisstaff->isAdmin()) {
                            if ($a->getName() == $p['desc']) { ?>
                            <td>
                                <a class="button" href="plugins.php?id=<?php echo $a->getId() ?>"><?php echo __('Settings'); ?></a>
                            </td>
                        <?php } } ?>
                    </tr>
            <?php } } ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="5">
                    <?php if(!$count) {
                        echo sprintf(__('No plugins installed yet &mdash; %s add one %s!'),
                            '<a href="?a=add">', '</a>');
                    } else {
                        echo $showing;
                    } ?>
                </td>
            </tr>
            </tfoot>
        </table>
        <?php
        if ($count) //Show options..
            echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
        ?>

    </form>