<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function (): void {
    add_submenu_page(
        'showtime-dashboard',
        'Design Settings',
        'Design',
        'manage_options',
        'showtime-design',
        'showtime_design_page'
    );
}, 10);

add_action('admin_init', function (): void {
    register_setting('showtime_design_settings', 'showtime_design', [
        'type'              => 'array',
        'sanitize_callback' => 'showtime_sanitize_design',
    ]);
});

function showtime_sanitize_design(array $input): array {
    $out = [];

    $colors = ['accent', 'accent_hover', 'text', 'muted', 'btn_color'];
    foreach ($colors as $key) {
        if (!empty($input[$key])) {
            $out[$key] = sanitize_hex_color($input[$key]);
        }
    }

    $floats = ['row_hover_opacity', 'past_opacity'];
    foreach ($floats as $key) {
        if (isset($input[$key]) && $input[$key] !== '') {
            $out[$key] = floatval($input[$key]);
        }
    }

    $texts = ['font_display', 'font_ui'];
    foreach ($texts as $key) {
        if (!empty($input[$key])) {
            $out[$key] = sanitize_text_field($input[$key]);
        }
    }

    $ints = ['day_size', 'venue_size', 'price_size', 'btn_radius', 'label_radius', 'btn_padding_y', 'btn_padding_x'];
    foreach ($ints as $key) {
        if (isset($input[$key]) && $input[$key] !== '') {
            $out[$key] = absint($input[$key]);
        }
    }

    return $out;
}

function showtime_design_page(): void {
    if (!current_user_can('manage_options')) return;

    $d = get_option('showtime_design', []);

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'colors';

    if (isset($_POST['showtime_design_reset']) && check_admin_referer('showtime_design_reset')) {
        delete_option('showtime_design');
        $d = [];
        echo '<div class="notice notice-success"><p>Design auf Standardwerte zurückgesetzt.</p></div>';
    }

    $tabs = [
        'colors'   => 'Farben',
        'typo'     => 'Typografie',
        'buttons'  => 'Buttons &amp; Form',
        'preview'  => 'Vorschau',
    ];

    $page_url = admin_url('admin.php?page=showtime-design');
    ?>
    <div class="wrap st-design-wrap">
        <h1 class="wp-heading-inline">Showtime – Design Settings</h1>

        <nav class="nav-tab-wrapper" style="margin-bottom:0;">
            <?php foreach ($tabs as $slug => $label) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $slug, $page_url)); ?>"
                   class="nav-tab<?php echo $active_tab === $slug ? ' nav-tab-active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($active_tab !== 'preview') : ?>
        <form method="post" action="options.php" class="st-design-form">
            <?php settings_fields('showtime_design_settings'); ?>
            <input type="hidden" name="showtime_design[_tab]" value="<?php echo esc_attr($active_tab); ?>">

            <?php if ($active_tab === 'colors') : ?>
            <div class="st-design-grid">
                <div class="st-design-section">
                    <h2>Farben</h2>

                    <div class="st-color-grid">
                        <?php
                        $color_fields = [
                            'accent'            => ['label' => 'Accent Color',      'default' => '#d4550a'],
                            'accent_hover'      => ['label' => 'Accent Hover',      'default' => '#8a3506'],
                            'text'              => ['label' => 'Text Color',         'default' => '#d6cec4'],
                            'muted'             => ['label' => 'Muted Color',        'default' => '#48433d'],
                            'btn_color'         => ['label' => 'Button Text Color',  'default' => '#ffffff'],
                        ];
                        foreach ($color_fields as $key => $cfg) :
                            $val = $d[$key] ?? '';
                        ?>
                        <div class="st-color-field">
                            <input type="text"
                                   id="st_design_<?php echo esc_attr($key); ?>"
                                   name="showtime_design[<?php echo esc_attr($key); ?>]"
                                   value="<?php echo esc_attr($val); ?>"
                                   placeholder="<?php echo esc_attr($cfg['default']); ?>"
                                   class="st-color-picker"
                                   data-default-color="<?php echo esc_attr($cfg['default']); ?>">
                            <label for="st_design_<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($cfg['label']); ?>
                                <span class="st-default"><?php echo esc_html($cfg['default']); ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <table class="form-table st-number-table">
                        <tr>
                            <th><label for="st_design_row_hover_opacity">Row Hover Opacity</label></th>
                            <td>
                                <input type="number" id="st_design_row_hover_opacity"
                                       name="showtime_design[row_hover_opacity]"
                                       value="<?php echo esc_attr($d['row_hover_opacity'] ?? ''); ?>"
                                       placeholder="0.04" min="0" max="0.20" step="0.01"
                                       style="width:90px;">
                                <span class="description">0.00 – 0.20 &nbsp;(Standard: 0.04)</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_past_opacity">Past Show Opacity</label></th>
                            <td>
                                <input type="number" id="st_design_past_opacity"
                                       name="showtime_design[past_opacity]"
                                       value="<?php echo esc_attr($d['past_opacity'] ?? ''); ?>"
                                       placeholder="0.4" min="0.1" max="1.0" step="0.1"
                                       style="width:90px;">
                                <span class="description">0.1 – 1.0 &nbsp;(Standard: 0.4)</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php elseif ($active_tab === 'typo') : ?>
            <div class="st-design-grid">
                <div class="st-design-section">
                    <h2>Typografie</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="st_design_font_display">Display Font</label></th>
                            <td>
                                <input type="text" id="st_design_font_display"
                                       name="showtime_design[font_display]"
                                       value="<?php echo esc_attr($d['font_display'] ?? ''); ?>"
                                       placeholder="'Bebas Neue', sans-serif"
                                       class="regular-text">
                                <p class="description">Font-Stack für Datum und Venue-Name.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_font_ui">UI Font</label></th>
                            <td>
                                <input type="text" id="st_design_font_ui"
                                       name="showtime_design[font_ui]"
                                       value="<?php echo esc_attr($d['font_ui'] ?? ''); ?>"
                                       placeholder="'Space Mono', monospace"
                                       class="regular-text">
                                <p class="description">Font-Stack für Labels, Buttons und Meta-Informationen.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_day_size">Day Number Size</label></th>
                            <td>
                                <input type="number" id="st_design_day_size"
                                       name="showtime_design[day_size]"
                                       value="<?php echo esc_attr($d['day_size'] ?? ''); ?>"
                                       placeholder="72" min="24" max="200" step="1"
                                       style="width:90px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_venue_size">Venue Name Size</label></th>
                            <td>
                                <input type="number" id="st_design_venue_size"
                                       name="showtime_design[venue_size]"
                                       value="<?php echo esc_attr($d['venue_size'] ?? ''); ?>"
                                       placeholder="28" min="12" max="80" step="1"
                                       style="width:90px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_price_size">Price Size</label></th>
                            <td>
                                <input type="number" id="st_design_price_size"
                                       name="showtime_design[price_size]"
                                       value="<?php echo esc_attr($d['price_size'] ?? ''); ?>"
                                       placeholder="26" min="12" max="80" step="1"
                                       style="width:90px;"> px
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php elseif ($active_tab === 'buttons') : ?>
            <div class="st-design-grid">
                <div class="st-design-section">
                    <h2>Buttons &amp; Form</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="st_design_btn_radius">Button Border Radius</label></th>
                            <td>
                                <input type="number" id="st_design_btn_radius"
                                       name="showtime_design[btn_radius]"
                                       value="<?php echo esc_attr($d['btn_radius'] ?? ''); ?>"
                                       placeholder="0" min="0" max="50" step="1"
                                       style="width:90px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_label_radius">Badge Border Radius</label></th>
                            <td>
                                <input type="number" id="st_design_label_radius"
                                       name="showtime_design[label_radius]"
                                       value="<?php echo esc_attr($d['label_radius'] ?? ''); ?>"
                                       placeholder="0" min="0" max="50" step="1"
                                       style="width:90px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_btn_padding_y">Button Padding Y</label></th>
                            <td>
                                <input type="number" id="st_design_btn_padding_y"
                                       name="showtime_design[btn_padding_y]"
                                       value="<?php echo esc_attr($d['btn_padding_y'] ?? ''); ?>"
                                       placeholder="8" min="0" max="60" step="1"
                                       style="width:90px;"> px
                                <p class="description">Oben &amp; Unten (Standard: 8px / 0.5rem)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="st_design_btn_padding_x">Button Padding X</label></th>
                            <td>
                                <input type="number" id="st_design_btn_padding_x"
                                       name="showtime_design[btn_padding_x]"
                                       value="<?php echo esc_attr($d['btn_padding_x'] ?? ''); ?>"
                                       placeholder="32" min="0" max="120" step="1"
                                       style="width:90px;"> px
                                <p class="description">Links &amp; Rechts (Standard: 32px / 2rem)</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>

        <?php else : /* Preview tab */ ?>
        <div class="st-design-preview-wrap">
            <div class="st-design-preview-box">
                <?php showtime_design_preview_html($d); ?>
            </div>
        </div>

        <div style="margin-top:20px;">
            <form method="post" action="" onsubmit="return confirm('Wirklich alle Design-Einstellungen zurücksetzen?');">
                <?php wp_nonce_field('showtime_design_reset'); ?>
                <button type="submit" name="showtime_design_reset" value="1" class="st-design-reset-btn button">
                    Auf Standardwerte zurücksetzen
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
    jQuery(function($) {
        $('.st-color-picker').wpColorPicker();
    });
    </script>
    <?php
}

function showtime_design_preview_html(array $d): void {
    $accent   = $d['accent']    ?? '#d4550a';
    $text     = $d['text']      ?? '#d6cec4';
    $muted    = $d['muted']     ?? '#48433d';
    $btn_bg   = $d['accent']    ?? '#d4550a';
    $btn_col  = $d['btn_color'] ?? '#ffffff';
    $font_d   = $d['font_display'] ?? "'Bebas Neue', sans-serif";
    $font_ui  = $d['font_ui']      ?? "'Space Mono', monospace";
    $day_sz   = isset($d['day_size'])   ? absint($d['day_size'])   . 'px' : '72px';
    $venue_sz = isset($d['venue_size']) ? absint($d['venue_size']) . 'px' : '28px';
    $radius   = isset($d['btn_radius']) ? absint($d['btn_radius']) . 'px' : '0px';
    $pad_y    = isset($d['btn_padding_y']) ? absint($d['btn_padding_y']) . 'px' : '0.5rem';
    $pad_x    = isset($d['btn_padding_x']) ? absint($d['btn_padding_x']) . 'px' : '2rem';
    ?>
    <div style="display:grid;grid-template-columns:140px 1fr auto;align-items:center;gap:20px;padding:20px 0;border-bottom:1px solid rgba(255,255,255,.06);">
        <div style="display:flex;flex-direction:column;line-height:1;">
            <span style="font-family:<?php echo esc_attr($font_d); ?>;font-size:<?php echo esc_attr($day_sz); ?>;letter-spacing:.02em;color:<?php echo esc_attr($accent); ?>;line-height:.9;">14</span>
            <span style="font-family:<?php echo esc_attr($font_ui); ?>;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:<?php echo esc_attr($muted); ?>;margin-top:6px;">JUN 2026</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <span style="font-family:<?php echo esc_attr($font_d); ?>;font-size:<?php echo esc_attr($venue_sz); ?>;letter-spacing:.06em;color:<?php echo esc_attr($text); ?>;line-height:1;text-transform:uppercase;">Gasometer Wien</span>
            <span style="font-family:<?php echo esc_attr($font_ui); ?>;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:<?php echo esc_attr($muted); ?>;">Wien, AT</span>
            <span style="display:inline-block;width:fit-content;margin-top:4px;padding:3px 10px;border:1px solid rgba(212,85,10,.4);background:rgba(212,85,10,.08);color:<?php echo esc_attr($accent); ?>;font-size:9px;letter-spacing:.14em;text-transform:uppercase;">Headline</span>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
            <a href="#" style="display:inline-block;background-color:<?php echo esc_attr($btn_bg); ?>;color:<?php echo esc_attr($btn_col); ?>;border:1px solid <?php echo esc_attr($btn_bg); ?>;font-family:<?php echo esc_attr($font_ui); ?>;font-size:0.9rem;text-transform:uppercase;text-decoration:none;padding:<?php echo esc_attr($pad_y); ?> <?php echo esc_attr($pad_x); ?>;border-radius:<?php echo esc_attr($radius); ?>;">Buy Tickets</a>
        </div>
    </div>
    <div style="display:flex;margin-top:20px;">
        <button style="display:inline-block;background:transparent;border:1px solid <?php echo esc_attr($accent); ?>;color:<?php echo esc_attr($accent); ?>;font-family:<?php echo esc_attr($font_ui); ?>;font-size:0.9rem;text-transform:uppercase;padding:<?php echo esc_attr($pad_y); ?> <?php echo esc_attr($pad_x); ?>;cursor:pointer;border-radius:<?php echo esc_attr($radius); ?>;">All Dates</button>
    </div>
    <?php
}
