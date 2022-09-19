<?php
// Calling convention (assumed global scope):
// $tickets - <QuerySet> with all columns and annotations necessary to
//      render the full page

// Make sure the cdata materialized view is available;
\model\AssetForm::ensureDynamicDataView();

// Identify columns of output
$columns = $queue->getColumns();

// Figure out REFRESH url — which might not be accurate after posting a
// response
list($path,) = explode('?', $_SERVER['REQUEST_URI'], 2);
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);

// Remove commands from query
unset($args['id']);
if (isset($args['a']) && ($args['a'] !== 'search')) unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

// Establish the selected or default sorting mechanism
if (isset($_GET['sort']) && is_numeric($_GET['sort'])) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'col' => (int) $_GET['sort'],
        'dir' => (int) $_GET['dir'],
    );
}
elseif (isset($_GET['sort'])
    // Drop the leading `qs-`
    && (strpos($_GET['sort'], 'qs-') === 0)
    && ($sort_id = substr($_GET['sort'], 3))
    && is_numeric($sort_id)
    && ($sort = QueueSort::lookup($sort_id))
) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'queuesort' => $sort,
        'dir' => (int) $_GET['dir'],
    );
}
elseif (isset($_SESSION['sort'][$queue->getId()])) {
    $sort = $_SESSION['sort'][$queue->getId()];
}
elseif ($queue_sort = $queue->getDefaultSort()) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'queuesort' => $queue_sort,
        'dir' => (int) $_GET['dir'] ?? 0,
    );
}

// Handle current sorting preferences

$sorted = false;
foreach ($columns as $C) {
    // Sort by this column ?
    if (isset($sort['col']) && $sort['col'] == $C->id) {
        $assets = $C->applySort($assets, $sort['dir']);
        $sorted = true;
    }
}

// Apply queue sort if it's not already sorted by a column
if (!$sorted) {
    // Apply queue sort-dropdown selected preference
    if (isset($sort['queuesort']))
        $sort['queuesort']->applySort($assets, $sort['dir']);
    else // otherwise sort by created DESC
        $assets->order_by('host_name');
}

$_SESSION[':Q:assets'] = $assets;

// Apply pagination
$total = $assets->count();
$page = (isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav = new Pagenate($total, $page, PAGE_LIMIT);
$assets = $pageNav->paginateSimple($assets);

$Q = $queue->getBasicQuery();

if ($Q->constraints) {
    if (count($Q->constraints) > 1) {
        foreach ($Q->constraints as $value) {
            if (!$value->constraints)
                $empty = true;
        }
    }
}

if (($Q->extra && isset($Q->extra['tables'])) || !$Q->constraints || $empty) {
    $skipCount = true;
    $count = '-';
}

$pageNav->setTotal($total, true);
$pageNav->setURL('handle', $args);
?>

<!-- SEARCH FORM START -->
<div id='basic_search'>
    <form action="handle" method="get" onsubmit="javascript:
  $.pjax({
    url:$(this).attr('action') + '?' + $(this).serialize(),
    container:'#pjax-container',
    timeout: 2000
  });
return false;">
    <input type="hidden" name="a" value="search">
    <input type="hidden" name="search-type" value=""/>
    <div class="attached input">
      <input type="text" id="basic-asset-search" class="basic-search" data-url="lookup" name="query"
        autofocus size="30" value="<?php echo Format::htmlchars($_REQUEST['query'] ?? null, true); ?>"
        autocomplete="off" autocorrect="off" autocapitalize="off">
      <button type="submit" class="attached button"><i class="icon-search"></i>
      </button>
    </div>
    <a href="#" onclick="javascript:
        $.dialog('search', 201);"
        >[<?php echo __('advanced'); ?>]</a>
    </form>
</div>
<!-- SEARCH FORM END -->

<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><a href="<?php echo $refresh_url; ?>"
                    title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> <?php echo
                    $queue->getName(); ?></a>
                    <?php
                    if (($crit=$queue->getSupplementalCriteria()))
                        echo sprintf('<i class="icon-filter"
                                data-placement="bottom" data-toggle="tooltip"
                                title="%s"></i>&nbsp;',
                                Format::htmlchars($queue->describeCriteria($crit)));
                    ?>
                </h2>
            </div>
            <div class="configureQ">
                <i class="icon-cog"></i>
                <div class="noclick-dropdown anchor-left">
                    <ul>
                        <li>
                            <a class="no-pjax" href="#"
                              data-dialog="asset/search/<?php echo
                              urlencode($queue->getId()); ?>"><i
                            class="icon-fixed-width icon-pencil"></i>
                            <?php echo __('Edit'); ?></a>
                        </li>
                        <li>
                            <a class="no-pjax" href="#"
                              data-dialog="asset/search/create?pid=<?php
                              echo $queue->getId(); ?>"><i
                            class="icon-fixed-width icon-plus-sign"></i>
                            <?php echo __('Add Sub Queue'); ?></a>
                        </li>
<?php

if ($queue->id > 0 && $queue->isOwner($thisstaff)) { ?>
                        <li class="danger">
                            <a class="no-pjax confirm-action" href="#"
                                data-dialog="asset/queue/<?php
                                echo $queue->id; ?>/delete"><i
                            class="icon-fixed-width icon-trash"></i>
                            <?php echo __('Delete'); ?></a>
                        </li>
<?php } ?>
                    </ul>
                </div>
            </div>

          <div class="pull-right flush-right">
              <?php if ($thisstaff->hasPerm(User::PERM_CREATE)) { ?>
                  <a class="green button action-button popup-dialog"
                     href="#asset/add/">
                      <i class="icon-plus-sign"></i>
                      <?php echo __('New Asset'); ?>
                  </a>
                  <a class="action-button popup-dialog"
                     href="#import/bulk/">
                      <i class="icon-upload"></i>
                      <?php echo __('Import'); ?>
                  </a>
              <?php }
              if($thisstaff->isAdmin()) { ?>
                  <a class="action-button" href="<?php echo INVENTORY_WEB_ROOT; ?>settings/forms">
                      <i class="icon-cogs"></i>
                      <?php echo __('Settings'); ?>
                  </a>
              <?php } ?>
              <span class="action-button" data-dropdown="#action-dropdown-more"
                    style="/*DELME*/ vertical-align:top; margin-bottom:0">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
              <div id="action-dropdown-more" class="action-dropdown anchor-right">
                  <ul>
                      <?php
                      if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                          <li class="danger"><a class="assets-action" href="#delete">
                                  <i class="icon-trash icon-fixed-width"></i>
                                  <?php echo __('Delete'); ?></a></li>
                      <?php } ?>
                      <li>
                          <a class="assets-action" href="#retire">
                              <i class="icon-archive icon-fixed-width"></i>
                              <?php echo __('Retire'); ?>
                          </a>
                      </li>
                  </ul>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
    <?php
    $showing = $search ? __('Search Results').': ' : '';
    if($assets->exists(true))
        $showing .= $pageNav->showing();
    else
        $showing .= __('No assets found!');
    ?>
<form action="?" method="POST" name='assets' id="assets-list">
<?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" name="a" id="action" value="" >

<table class="list queue tickets" border="0" cellspacing="1" cellpadding="2" width="940">
  <thead>
    <tr>
        <th style="width:12px"></th>
<?php

foreach ($columns as $C) {
    $heading = Format::htmlchars($C->getLocalHeading());
    if ($C->isSortable()) {
        $args = $_GET;
        $dir = $sort['col'] != $C->id ?: ($sort['dir'] ? 'desc' : 'asc');
        $args['dir'] = $sort['col'] != $C->id ?: (int) !$sort['dir'];
        $args['sort'] = $C->id;
        $heading = sprintf('<a href="?%s" class="%s">%s</a>',
            Http::build_query($args), $dir, $heading);
    }
    echo sprintf('<th width="%s" data-id="%d">%s</th>',
        $C->getWidth(), $C->id, $heading);
}
?>
    </tr>
  </thead>
  <tbody>
<?php
foreach ($assets as $A) {
    echo '<tr>';
    ?>
        <td><input type="checkbox" class="ckb mass nowarn" name="tids[]"
            value="<?php echo $A['asset_id']; ?>" /></td>
<?php
    foreach ($columns as $C) {
        list($contents, $styles) = $C->render($A);
        if ($style = $styles ? 'style="'.$styles.'"' : '') {
            echo "<td $style><div $style>$contents</div></td>";
        }
        else {
            echo "<td>$contents</td>";
        }
    }
    echo '</tr>';
}
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="<?php echo count($columns)+1; ?>">
          <?php if ($total) { ?>
              <?php echo __('Select');?>:&nbsp;
              <a id="selectAll" href="#ckb"><?php echo __('All');?></a>
              <a id="selectNone" href="#ckb"><?php echo __('None');?></a>
              <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>
          <?php }else{
              echo '<i>';
              echo __('Query returned 0 results.');
              echo '</i>';
          } ?>
      </td>
    </tr>
  </tfoot>
</table>
    <?php
    if ($total > 0) { //if we actually had any tickets returned.
        ?>  <div>
            <span class="faded pull-right"><?php echo $pageNav->showing(); ?></span>
            <?php
            echo __('Page').':'.$pageNav->getPageLinks().'&nbsp;';
            ?>
            <a href="<?php echo INVENTORY_WEB_ROOT; ?>asset/handle?a=export"
               id="" class="no-pjax"
            ><?php echo __('Export'); ?></a>
        </div>
        <?php
    } ?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-asset-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "<?php echo INVENTORY_WEB_ROOT.'asset/handle?q=';?>"+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = '<?php echo INVENTORY_WEB_ROOT.'asset/handle?id='?>'+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.popup-dialog', function(e) {
        e.preventDefault();
        $.assetLookup($(this).attr('href').substr(1));
        return false;
    });

    var goBaby = function(action, confirmed) {
        var ids = [],
            $form = $('form#assets-list');
        $(':checkbox.mass:checked', $form).each(function() {
            ids.push($(this).val());
        });
        if (ids.length) {
            var submit = function(data) {
                $form.find('#action').val(action);
                $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
                if (data)
                    $.each(data, function() { $form.append($('<input type="hidden">').attr('name', this.name).val(this.value)); });
                $form.find('#selected-count').val(ids.length);
                $form.submit();
            };
            var options = {};

            if (!confirmed)
                $.confirm(__('You sure?'), undefined, options).then(function(data) {
                    if (data === false)
                        return false;
                    submit(data);
                });
            else
                submit();
        }
        else {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.assets-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });

    $.assetLookup = function (url, cb) {
        $.dialog(url, 201, function (xhr, asset) {
            if ($.type(asset) == 'string')
                asset = $.parseJSON(asset);
            if (cb) return cb(asset);
        }, {
            onshow: function() { $('#user-search').focus(); }
        }, true);
    };

    let root_url = '<?php echo OST_WEB_ROOT; ?>';
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

                console.log('URL BEING USED: ' + root_url + 'scp/dispatcher.php/inventory/'+$form.attr('action').substr(1));
                $.ajax({
                    type:  $form.attr('method'),
                    url: root_url + 'scp/dispatcher.php/inventory/'+$form.attr('action').substr(1),
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
                    .fail(function() { console.log('AJAX failed')});
                return false;
            });
        });
        if (options.onload) { options.onload(); }
    };

    $(document).on('click', 'a.asset-export', function(e) {
        e.preventDefault();
        var url = '<?php echo INVENTORY_WEB_ROOT; ?>' + $(this).attr('href').substr(1)
        $.dialog(url, 201, function (xhr) {
            var resp = $.parseJSON(xhr.responseText);
            var checker = '<?php echo INVENTORY_WEB_ROOT; ?>' + 'export/'+resp.eid+'/check';
            $.dialog(checker, 201, function (xhr) { });
            return false;
        });
        return false;
    });

    $(document).on('change', 'select[data-quick-add]', function() {
        var $select = $(this),
            selected = $select.find('option:selected'),
            type = selected.parent().closest('[data-quick-add]').data('quickAdd');
        if (!type || (selected.data('quickAdd') === undefined && selected.val() !== ':new:'))
            return;
        $.dialog('<?php echo INVENTORY_WEB_ROOT.'admin/quick-add/'; ?>' + type, 201,
            function(xhr, data) {
                data = JSON.parse(data);
                if (data && data.id && data.name) {
                    var id = data.id;
                    if (selected.data('idPrefix'))
                        id = selected.data('idPrefix') + id;
                    $('<option>')
                        .attr('value', id)
                        .text(data.name)
                        .insertBefore(selected)
                    $select.val(id);
                }
            });
    });
});
</script>
