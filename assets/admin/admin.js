(function ($) {
    'use strict';

    // ── Sync Now ──────────────────────────────────────────────────────────
    $('#showtime-sync-btn').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('.showtime-spinner');
        var $result  = $('#showtime-sync-result');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.html('');

        $.ajax({
            url:  ShowtimeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'showtime_manual_import',
                nonce:  ShowtimeAdmin.nonce,
            },
            success: function (res) {
                if (res.success) {
                    var d      = res.data;
                    var errors = d.errors && d.errors.length ? ' Errors: ' + d.errors.join(', ') : '';
                    $result.html(
                        '<div class="notice notice-success inline" style="margin:0;">' +
                        '<p><strong>Sync complete:</strong> ' + d.imported + ' imported, ' +
                        d.updated + ' updated.' + errors + '</p></div>'
                    );
                    $('.showtime-last-sync-display').text('Just now');
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Unknown error.';
                    $result.html(
                        '<div class="notice notice-error inline" style="margin:0;"><p>' + msg + '</p></div>'
                    );
                }
            },
            error: function () {
                $result.html(
                    '<div class="notice notice-error inline" style="margin:0;">' +
                    '<p>Request failed. Please try again.</p></div>'
                );
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            },
        });
    });

    // ── Shortcode Generator ───────────────────────────────────────────────
    function updateShortcode() {
        var limit    = parseInt($('#sc-limit').val(), 10) || 5;
        var showPast = $('#sc-past').is(':checked');
        var sc       = '[showtime limit="' + limit + '"';
        if (showPast) sc += ' show_past="true"';
        sc += ']';
        $('#showtime-shortcode-output').text(sc);
    }

    $('#sc-limit, #sc-past').on('change input', updateShortcode);

    // ── Copy to Clipboard ─────────────────────────────────────────────────
    $('#showtime-copy-btn').on('click', function () {
        var $btn  = $(this);
        var text  = $('#showtime-shortcode-output').text();
        var orig  = $btn.text();
        var label = $btn.data('copied') || 'Copied!';

        function showCopied() {
            $btn.text(label);
            setTimeout(function () { $btn.text(orig); }, 1500);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showCopied);
        } else {
            var $tmp = $('<textarea>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo('body');
            $tmp[0].select();
            document.execCommand('copy');
            $tmp.remove();
            showCopied();
        }
    });

    // ── Design System ─────────────────────────────────────────────────────
    if ($('#st-brand-form').length === 0) return;

    var debounceTimer;
    var presets = (ShowtimeAdmin && ShowtimeAdmin.presets) ? ShowtimeAdmin.presets : {};

    // Color Picker initialisieren – jetzt korrekt nach wp-color-picker geladen
    $('.st-color-picker').wpColorPicker({
        change: function () { debounceUpdate(); },
        clear:  function () { debounceUpdate(); },
    });

    // Range Slider (alle)
    $(document).on('input', '.st-range', function () {
        $(this).closest('.st-range-wrap').find('.st-range-value').text($(this).val() + 'px');
        debounceUpdate();
    });

    // Font-Felder
    $('.st-font-field').on('input', function () { debounceUpdate(); });

    // Preset-Karten
    $(document).on('click', '.st-preset-card', function () {
        var key    = $(this).data('preset');
        var preset = presets[key];
        if (!preset) return;
        loadPreset(key, preset);
    });

    function loadPreset(key, preset) {
        setColorPicker('#st_accent',     preset.accent);
        setColorPicker('#st_background', preset.background);
        setColorPicker('#st_text',       preset.text);

        $('#st_font_display').val(preset.font_display);
        $('#st_font_ui').val(preset.font_ui);

        var r = parseInt(preset.border_radius, 10) || 0;
        $('#st_border_radius').val(r);
        $('#st_border_radius').closest('.st-range-wrap').find('.st-range-value').text(r + 'px');

        $('#st-preset-key').val(key);

        $('.st-preset-card').removeClass('active').css('border-color', '');
        $('.st-preset-card[data-preset="' + key + '"]')
            .addClass('active')
            .css('border-color', preset.accent);

        debounceUpdate();
    }

    function setColorPicker(selector, color) {
        var $input = $(selector);
        // Iris-Instanz direkt über jQuery-Widget-Daten ansprechen
        var instance = $input.data('wpWpColorPicker') || $input.data('a8cIris');
        if (instance) {
            $input.wpColorPicker('color', color);
        } else {
            $input.val(color);
        }
        // Swatch-Farbe im Picker-UI aktualisieren
        $input.closest('.wp-picker-container').find('.wp-color-result').css('background-color', color);
    }

    function debounceUpdate() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runUpdate, 350);
    }

    function getBrandValues() {
        return {
            accent:           $('#st_accent').val(),
            background:       $('#st_background').val(),
            text:             $('#st_text').val(),
            font_display:     $('#st_font_display').val(),
            font_ui:          $('#st_font_ui').val(),
            border_radius:    $('#st_border_radius').val(),
            btn_bg:           $('#st_btn_bg').val(),
            btn_color:        $('#st_btn_color').val(),
            btn_hover_bg:     $('#st_btn_hover_bg').val(),
            btn_hover_color:  $('#st_btn_hover_color').val(),
            btn_border_color: $('#st_btn_border_color').val(),
            btn_border_width: $('#st_btn_border_width').val(),
            btn_padding_y:    $('#st_btn_padding_y').val(),
            btn_padding_x:    $('#st_btn_padding_x').val(),
        };
    }

    function runUpdate() {
        if (!ShowtimeAdmin.designNonce) return;
        var brand = getBrandValues();

        $.ajax({
            url:  ShowtimeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'showtime_preview_tokens',
                nonce:  ShowtimeAdmin.designNonce,
                brand:  brand,
            },
            success: function (res) {
                if (!res.success) return;
                var tokens = res.data;

                var $preview = $('#st-live-preview');
                $.each(tokens, function (prop, val) {
                    $preview[0].style.setProperty(prop, val);
                });

                var rows = '';
                $.each(tokens, function (prop, val) {
                    rows += '<tr>'
                          + '<td class="prop">' + $('<span>').text(prop).html() + '</td>'
                          + '<td class="val">'  + $('<span>').text(val).html()  + '</td>'
                          + '</tr>';
                });
                $('#st-tokens-table-wrap table tbody').html(rows);
            },
        });
    }

    // Export
    $('#st-export-btn').on('click', function () {
        var data = getBrandValues();
        var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        var url  = URL.createObjectURL(blob);
        var date = new Date().toISOString().slice(0, 10);
        var $a   = $('<a>').attr({ href: url, download: 'showtime-design-' + date + '.json' }).appendTo('body');
        $a[0].click();
        $a.remove();
        URL.revokeObjectURL(url);
    });

    // Import
    $('#st-import-file').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = JSON.parse(e.target.result);
                if (data.accent)                  setColorPicker('#st_accent',     data.accent);
                if (data.background)              setColorPicker('#st_background', data.background);
                if (data.text)                    setColorPicker('#st_text',       data.text);
                if (data.font_display)            $('#st_font_display').val(data.font_display);
                if (data.font_ui)                 $('#st_font_ui').val(data.font_ui);
                if (data.border_radius !== undefined) {
                    $('#st_border_radius').val(data.border_radius);
                    $('#st_border_radius').closest('.st-range-wrap')
                        .find('.st-range-value').text(data.border_radius + 'px');
                }
                $('#st-preset-key').val('custom');
                $('.st-preset-card').removeClass('active').css('border-color', '');
                debounceUpdate();
            } catch (err) {
                alert('Ungültige JSON-Datei.');
            }
        };
        reader.readAsText(file);
        this.value = '';
    });

}(jQuery));
