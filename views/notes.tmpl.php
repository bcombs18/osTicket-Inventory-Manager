<div id="quick-notes">
<?php
$show_options = true;
foreach ($notes as $note) {
    include INVENTORY_VIEWS_DIR."note.tmpl.php";
} ?>
</div>
<div id="new-note-box">
<div class="quicknote" id="assetnew-note" data-url="<?php echo $create_note_url; ?>">
<div class="body">
    <a href="#"><i class="icon-plus icon-large"></i> &nbsp;
    <?php echo __('Click to create a new note'); ?></a>
</div>
</div>
</div>
