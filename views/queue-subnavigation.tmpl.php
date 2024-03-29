<?php
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $children - <Array<CustomQueue>> all direct children of this queue
$queue = $q;
$hasChildren = $children && (count($children) > 0);
$selected = $_REQUEST['queue'] == $q->getId();

if($queue->get('root') == 'P') {
    $model = "phone/handlePhone";
} elseif ($queue->get('root') == 'U') {
    $model = "asset/handleAsset";
}
$link = INVENTORY_WEB_ROOT.$model."?queue=".$queue->getId();

global $thisstaff;
?>
<!-- SubQ class: only if top level Q has subQ -->
<li <?php if ($hasChildren)  echo 'class="subQ"'; ?>>

  <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $link;
    ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
      <?php
        echo Format::htmlchars($q->getName()); ?>
      <?php
        if ($hasChildren) { ?>
            <i class="icon-caret-down"></i>
      <?php } ?>
    </a>

    <?php
    $closure_include = function($q, $children) {
        global $thisstaff, $ost, $cfg;
        include __FILE__;
    };
    if ($hasChildren) { ?>
    <ul class="subMenuQ">
    <?php
    foreach ($children as $_) {
        list($q, $childz) = $_;
        if (!$q->isPrivate())
          $closure_include($q, $childz);
    }

    // Include personal sub-queues
    $first_child = true;
    foreach ($children as $_) {
      list($q, $childz) = $_;
      if ($q->isPrivate()) {
        if ($first_child) {
          $first_child = false;
          echo '<li class="personalQ"></li>';
        }
        $closure_include($q, $childz);
      }
    } ?>
    </ul>
<?php
} ?>
</li>
