/*
   assets.js

   osTicket SCP
   Copyright (c) osTicket.com

 */

//modal---------//
$.assetDialog = function (url, codes, cb, options, useDispatcher=false) {
    options = options||{};

    if (codes && !$.isArray(codes))
        codes = [codes];

    let urlDispatcher = null;
    if(useDispatcher === true) {
        urlDispatcher = root_url + 'scp/dispatcher.php/inventory/';
    } else {
        urlDispatcher = root_url + 'scp/ajax.php/';
    }

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
                url: urlDispatcher+$form.attr('action').substr(1),
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

$.assetLookup = function (url, cb) {
    $.assetDialog(url, 201, function (xhr, asset) {
        if ($.type(asset) == 'string')
            asset = $.parseJSON(asset);
        if (cb) return cb(asset);
    }, {
        onshow: function() { $('#user-search').focus(); }
    }, true);
};
