<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function (): void {
    add_submenu_page(
        'showtime-dashboard',
        'Design System',
        'Design',
        'manage_options',
        'showtime-design',
        'showtime_design_page'
    );
}, 10);

add_action('admin_init', function (): void {
    register_setting('showtime_brand_settings', 'showtime_brand_tokens', [
        'type'              => 'array',
        'sanitize_callback' => 'showtime_sanitize_brand_tokens',
    ]);
});

function showtime_sanitize_brand_tokens(array $input): array {
    $out = [];

    $colors = ['accent', 'background', 'text', 'btn_bg', 'btn_color', 'btn_hover_bg', 'btn_hover_color', 'btn_border_color'];
    foreach ($colors as $key) {
        if (!empty($input[$key])) {
            $out[$key] = sanitize_hex_color($input[$key]) ?: '';
        }
    }

    foreach (['font_display', 'font_ui'] as $key) {
        if (!empty($input[$key])) {
            $out[$key] = sanitize_text_field($input[$key]);
        }
    }

    $ints = ['border_radius', 'btn_padding_y', 'btn_padding_x', 'btn_border_width'];
    foreach ($ints as $key) {
        $out[$key] = absint($input[$key] ?? 0);
    }

    $out['preset'] = sanitize_key($input['preset'] ?? 'custom');

    return $out;
}

function showtime_design_page(): void {
    if (!current_user_can('manage_options')) return;

    $brand    = get_option('showtime_brand_tokens', showtime_brand_token_defaults());
    $presets  = showtime_design_presets();
    $defaults = showtime_brand_token_defaults();

    // Reset-Handler
    if (isset($_POST['showtime_brand_reset']) && check_admin_referer('showtime_brand_reset')) {
        update_option('showtime_brand_tokens', $defaults);
        $brand = $defaults;
        echo '<div class="notice notice-success is-dismissible"><p>Design auf <strong>Dark Metal</strong> zurückgesetzt.</p></div>';
    }

    $active_preset = $brand['preset'] ?? 'custom';
    ?>
    <div class="wrap st-design-page">
        <h1>Showtime – Design System</h1>

        <?php settings_errors('showtime_brand_tokens'); ?>

        <!-- ── Sektion 1: Presets ─────────────────────────────── -->
        <div class="st-section">
            <h2 class="st-section-title">Preset</h2>
            <div class="st-presets-grid">
                <?php foreach ($presets as $key => $preset) : ?>
                <div class="st-preset-card<?php echo $active_preset === $key ? ' active' : ''; ?>"
                     data-preset="<?php echo esc_attr($key); ?>"
                     style="<?php echo $active_preset === $key ? 'border-color:' . esc_attr($preset['accent']) . ';' : ''; ?>">
                    <div class="st-preset-swatches">
                        <span style="background:<?php echo esc_attr($preset['accent']); ?>"></span>
                        <span style="background:<?php echo esc_attr($preset['text']); ?>"></span>
                        <span style="background:<?php echo esc_attr($preset['background']); ?>;border:1px solid #444;"></span>
                    </div>
                    <span class="st-preset-name"><?php echo esc_html($preset['label']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Sektion 2: Brand Tokens Formular ──────────────── -->
        <form method="post" action="options.php" id="st-brand-form">
            <?php settings_fields('showtime_brand_settings'); ?>
            <input type="hidden" name="showtime_brand_tokens[preset]" id="st-preset-key"
                   value="<?php echo esc_attr($active_preset); ?>">

            <div class="st-section">
                <h2 class="st-section-title">Brand Tokens</h2>
                <div class="st-tokens-grid">

                    <div class="st-field">
                        <label for="st_accent">Accent Color</label>
                        <p class="st-field-desc">Primärfarbe: Buttons, Datum, Labels</p>
                        <input type="text" id="st_accent" name="showtime_brand_tokens[accent]"
                               value="<?php echo esc_attr($brand['accent'] ?? $defaults['accent']); ?>"
                               class="st-color-picker"
                               data-default-color="<?php echo esc_attr($defaults['accent']); ?>">
                    </div>

                    <div class="st-field">
                        <label for="st_background">Background Color</label>
                        <p class="st-field-desc">Seiten-Hintergrund (für Surface-Berechnung)</p>
                        <input type="text" id="st_background" name="showtime_brand_tokens[background]"
                               value="<?php echo esc_attr($brand['background'] ?? $defaults['background']); ?>"
                               class="st-color-picker"
                               data-default-color="<?php echo esc_attr($defaults['background']); ?>">
                    </div>

                    <div class="st-field">
                        <label for="st_text">Text Color</label>
                        <p class="st-field-desc">Primärer Text (Venue-Name, Basis für Muted)</p>
                        <input type="text" id="st_text" name="showtime_brand_tokens[text]"
                               value="<?php echo esc_attr($brand['text'] ?? $defaults['text']); ?>"
                               class="st-color-picker"
                               data-default-color="<?php echo esc_attr($defaults['text']); ?>">
                    </div>

                    <div class="st-field">
                        <label for="st_font_display">Display Font</label>
                        <p class="st-field-desc">CSS Font-Stack für Datum &amp; Venue</p>
                        <input type="text" id="st_font_display" name="showtime_brand_tokens[font_display]"
                               value="<?php echo esc_attr($brand['font_display'] ?? $defaults['font_display']); ?>"
                               class="regular-text st-font-field"
                               placeholder="<?php echo esc_attr($defaults['font_display']); ?>">
                    </div>

                    <div class="st-field">
                        <label for="st_font_ui">UI Font</label>
                        <p class="st-field-desc">CSS Font-Stack für Labels &amp; Buttons</p>
                        <input type="text" id="st_font_ui" name="showtime_brand_tokens[font_ui]"
                               value="<?php echo esc_attr($brand['font_ui'] ?? $defaults['font_ui']); ?>"
                               class="regular-text st-font-field"
                               placeholder="<?php echo esc_attr($defaults['font_ui']); ?>">
                    </div>

                    <div class="st-field">
                        <label for="st_border_radius">Border Radius</label>
                        <p class="st-field-desc">0 = eckig, 16 = rund</p>
                        <div class="st-range-wrap">
                            <input type="range" id="st_border_radius" name="showtime_brand_tokens[border_radius]"
                                   value="<?php echo esc_attr($brand['border_radius'] ?? 0); ?>"
                                   min="0" max="16" step="1" class="st-range">
                            <span class="st-range-value"><?php echo esc_html($brand['border_radius'] ?? 0); ?>px</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Button-Einstellungen ────────────────────────── -->
            <div class="st-section">
                <h2 class="st-section-title">Buttons</h2>
                <div class="st-tokens-grid">

                    <div class="st-field">
                        <label for="st_btn_bg">Button Hintergrund</label>
                        <p class="st-field-desc">Normal-Zustand (leer = Accent)</p>
                        <input type="text" id="st_btn_bg" name="showtime_brand_tokens[btn_bg]"
                               value="<?php echo esc_attr($brand['btn_bg'] ?? ''); ?>"
                               class="st-color-picker"
                               data-default-color="#d4550a">
                    </div>

                    <div class="st-field">
                        <label for="st_btn_color">Button Textfarbe</label>
                        <p class="st-field-desc">Normal-Zustand</p>
                        <input type="text" id="st_btn_color" name="showtime_brand_tokens[btn_color]"
                               value="<?php echo esc_attr($brand['btn_color'] ?? '#ffffff'); ?>"
                               class="st-color-picker"
                               data-default-color="#ffffff">
                    </div>

                    <div class="st-field">
                        <label for="st_btn_hover_bg">Button Hover Hintergrund</label>
                        <p class="st-field-desc">Leer = 35 % dunkler als Hintergrund</p>
                        <input type="text" id="st_btn_hover_bg" name="showtime_brand_tokens[btn_hover_bg]"
                               value="<?php echo esc_attr($brand['btn_hover_bg'] ?? ''); ?>"
                               class="st-color-picker"
                               data-default-color="#8a3506">
                    </div>

                    <div class="st-field">
                        <label for="st_btn_hover_color">Button Hover Textfarbe</label>
                        <p class="st-field-desc">Hover-Zustand</p>
                        <input type="text" id="st_btn_hover_color" name="showtime_brand_tokens[btn_hover_color]"
                               value="<?php echo esc_attr($brand['btn_hover_color'] ?? '#ffffff'); ?>"
                               class="st-color-picker"
                               data-default-color="#ffffff">
                    </div>

                    <div class="st-field">
                        <label for="st_btn_border_color">Border Farbe</label>
                        <p class="st-field-desc">Leer = gleich wie Hintergrund</p>
                        <input type="text" id="st_btn_border_color" name="showtime_brand_tokens[btn_border_color]"
                               value="<?php echo esc_attr($brand['btn_border_color'] ?? ''); ?>"
                               class="st-color-picker"
                               data-default-color="#d4550a">
                    </div>

                    <div class="st-field">
                        <label for="st_btn_border_width">Border Dicke</label>
                        <p class="st-field-desc">0 = kein Rahmen</p>
                        <div class="st-range-wrap">
                            <input type="range" id="st_btn_border_width" name="showtime_brand_tokens[btn_border_width]"
                                   value="<?php echo esc_attr($brand['btn_border_width'] ?? 1); ?>"
                                   min="0" max="6" step="1" class="st-range st-range-number">
                            <span class="st-range-value"><?php echo esc_html($brand['btn_border_width'] ?? 1); ?>px</span>
                        </div>
                    </div>

                    <div class="st-field">
                        <label for="st_btn_padding_y">Padding oben / unten</label>
                        <p class="st-field-desc">Vertikal (Standard: 8 px)</p>
                        <div class="st-range-wrap">
                            <input type="range" id="st_btn_padding_y" name="showtime_brand_tokens[btn_padding_y]"
                                   value="<?php echo esc_attr($brand['btn_padding_y'] ?? 8); ?>"
                                   min="0" max="40" step="1" class="st-range st-range-number">
                            <span class="st-range-value"><?php echo esc_html($brand['btn_padding_y'] ?? 8); ?>px</span>
                        </div>
                    </div>

                    <div class="st-field">
                        <label for="st_btn_padding_x">Padding links / rechts</label>
                        <p class="st-field-desc">Horizontal (Standard: 32 px)</p>
                        <div class="st-range-wrap">
                            <input type="range" id="st_btn_padding_x" name="showtime_brand_tokens[btn_padding_x]"
                                   value="<?php echo esc_attr($brand['btn_padding_x'] ?? 32); ?>"
                                   min="0" max="80" step="1" class="st-range st-range-number">
                            <span class="st-range-value"><?php echo esc_html($brand['btn_padding_x'] ?? 32); ?>px</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Generierte Tokens Tabelle ── -->
            <div class="st-section">
                <details class="st-tokens-details">
                    <summary class="st-tokens-summary">Berechnete CSS Custom Properties</summary>
                    <div id="st-tokens-table-wrap" class="st-tokens-table-wrap">
                        <?php showtime_render_tokens_table(showtime_compute_tokens($brand)); ?>
                    </div>
                </details>
            </div>

            <?php submit_button('Design speichern', 'primary', 'submit', true, ['id' => 'st-save-btn']); ?>
        </form>

        <!-- ── Sektion 3: Live Preview ────────────────────────── -->
        <div class="st-section">
            <h2 class="st-section-title">Live Vorschau</h2>
            <div class="st-preview-box" id="st-live-preview">
                <?php showtime_design_preview($brand); ?>
            </div>
        </div>

        <!-- ── Sektion 4: Export / Import ────────────────────── -->
        <div class="st-section">
            <h2 class="st-section-title">Export / Import</h2>
            <div class="st-export-import">
                <button type="button" id="st-export-btn" class="button">
                    Design exportieren (JSON)
                </button>
                <label class="button" for="st-import-file" style="cursor:pointer;">
                    Design importieren
                </label>
                <input type="file" id="st-import-file" accept=".json" style="display:none;">
            </div>
            <p class="description" style="margin-top:8px;">
                Exportiert alle Brand Tokens als JSON-Datei. Beim Import werden die Felder befüllt – bitte danach speichern.
            </p>
        </div>

        <!-- ── Sektion 5: Reset ───────────────────────────────── -->
        <div class="st-section">
            <h2 class="st-section-title">Zurücksetzen</h2>
            <form method="post" action=""
                  onsubmit="return confirm('Wirklich alle Design-Einstellungen auf Dark Metal zurücksetzen?');">
                <?php wp_nonce_field('showtime_brand_reset'); ?>
                <button type="submit" name="showtime_brand_reset" value="1" class="st-btn-reset">
                    Auf Standard zurücksetzen (Dark Metal)
                </button>
            </form>
        </div>
    </div>

    <?php
}

function showtime_render_tokens_table(array $tokens): void {
    echo '<table class="st-tokens-table"><tbody>';
    foreach ($tokens as $prop => $val) {
        echo '<tr><td class="prop">' . esc_html($prop) . '</td>'
           . '<td class="val">'  . esc_html($val)  . '</td></tr>';
    }
    echo '</tbody></table>';
}

function showtime_design_preview(array $brand): void {
    $tokens = showtime_compute_tokens($brand);
    $style  = '';
    foreach ($tokens as $prop => $val) {
        $style .= esc_attr($prop) . ':' . esc_attr($val) . ';';
    }

    $accent     = $brand['accent']     ?? '#d4550a';
    $text_col   = $brand['text']       ?? '#d6cec4';
    $btn_color  = '#ffffff';
    $font_d     = $brand['font_display'] ?? "'Bebas Neue', sans-serif";
    $font_ui    = $brand['font_ui']      ?? "'Space Mono', monospace";
    ?>
    <div style="<?php echo $style; ?>font-family:var(--showtime-font-ui);">

        <!-- Show Row: On Sale -->
        <div style="display:grid;grid-template-columns:140px 1fr auto;align-items:center;gap:20px;padding:20px 0;border-bottom:1px solid var(--showtime-border);">
            <div style="display:flex;flex-direction:column;line-height:1;">
                <span style="font-family:var(--showtime-font-display);font-size:var(--showtime-day-size);letter-spacing:.02em;color:var(--showtime-accent);line-height:.9;">14</span>
                <span style="font-family:var(--showtime-font-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--showtime-muted);margin-top:6px;">JUN 2026</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <span style="font-family:var(--showtime-font-display);font-size:var(--showtime-venue-size);letter-spacing:.06em;color:var(--showtime-text);line-height:1;text-transform:uppercase;">Gasometer Wien</span>
                <span style="font-family:var(--showtime-font-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--showtime-muted);">Wien, AT</span>
                <span style="display:inline-block;width:fit-content;margin-top:4px;padding:3px 10px;border:1px solid var(--showtime-accent-border);background:var(--showtime-accent-subtle);color:var(--showtime-accent);font-family:var(--showtime-font-ui);font-size:9px;letter-spacing:.14em;text-transform:uppercase;border-radius:var(--showtime-radius-sm);">Support Act</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <a href="#" style="display:inline-block;background-color:var(--showtime-btn-bg);color:var(--showtime-btn-color);border:1px solid var(--showtime-btn-bg);font-family:var(--showtime-font-ui);font-size:0.9rem;text-transform:uppercase;text-decoration:none;padding:0.5rem 2rem;border-radius:var(--showtime-radius);">Buy Tickets</a>
            </div>
        </div>

        <!-- Show Row: Sold Out -->
        <div style="display:grid;grid-template-columns:140px 1fr auto;align-items:center;gap:20px;padding:20px 0;border-bottom:1px solid var(--showtime-border);">
            <div style="display:flex;flex-direction:column;line-height:1;">
                <span style="font-family:var(--showtime-font-display);font-size:var(--showtime-day-size);letter-spacing:.02em;color:var(--showtime-accent);line-height:.9;">21</span>
                <span style="font-family:var(--showtime-font-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--showtime-muted);margin-top:6px;">JUN 2026</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <span style="font-family:var(--showtime-font-display);font-size:var(--showtime-venue-size);letter-spacing:.06em;color:var(--showtime-text);line-height:1;text-transform:uppercase;">Volkshaus Zürich</span>
                <span style="font-family:var(--showtime-font-ui);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--showtime-muted);">Zürich, CH</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <span style="display:inline-block;background:var(--showtime-btn-disabled-bg);border:1px solid var(--showtime-status-soldout);color:var(--showtime-btn-disabled-color);font-family:var(--showtime-font-ui);font-size:0.9rem;text-transform:uppercase;padding:0.5rem 2rem;border-radius:var(--showtime-radius);">Sold Out</span>
            </div>
        </div>

        <!-- Footer: Ghost Button -->
        <div style="margin-top:20px;">
            <button style="display:inline-block;background:transparent;border:1px solid var(--showtime-btn-ghost-color);color:var(--showtime-btn-ghost-color);font-family:var(--showtime-font-ui);font-size:0.9rem;text-transform:uppercase;padding:0.5rem 2rem;cursor:pointer;border-radius:var(--showtime-radius);">All Dates</button>
        </div>

    </div>
    <?php
}
