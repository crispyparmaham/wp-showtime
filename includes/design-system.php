<?php
defined('ABSPATH') || exit;

// ── Brand Token Defaults ────────────────────────────────────────────────────

function showtime_brand_token_defaults(): array {
    return [
        'accent'           => '#d4550a',
        'background'       => '#070605',
        'text'             => '#d6cec4',
        'font_display'     => "'Bebas Neue', sans-serif",
        'font_ui'          => "'Space Mono', monospace",
        'border_radius'    => 0,
        // Button
        'btn_bg'           => '#d4550a',
        'btn_color'        => '#ffffff',
        'btn_hover_bg'     => '',       // leer = auto (65 % von btn_bg)
        'btn_hover_color'  => '#ffffff',
        'btn_padding_y'    => 8,        // px
        'btn_padding_x'    => 32,       // px
        'btn_border_width' => 1,        // px
        'btn_border_color' => '',       // leer = gleich wie btn_bg
        'preset'           => 'dark_metal',
    ];
}

// ── Farb-Hilfsfunktionen ────────────────────────────────────────────────────

function showtime_hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2)),
    ];
}

function showtime_adjust_brightness(string $hex, float $factor): string {
    $rgb = showtime_hex_to_rgb($hex);
    $r   = min(255, max(0, (int) round($rgb['r'] * $factor)));
    $g   = min(255, max(0, (int) round($rgb['g'] * $factor)));
    $b   = min(255, max(0, (int) round($rgb['b'] * $factor)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function showtime_hex_to_rgba(string $hex, float $alpha): string {
    $rgb = showtime_hex_to_rgb($hex);
    return 'rgba(' . $rgb['r'] . ', ' . $rgb['g'] . ', ' . $rgb['b'] . ', ' . $alpha . ')';
}

// ── Semantic Tokens berechnen ───────────────────────────────────────────────

function showtime_compute_tokens(array $brand): array {
    $accent = $brand['accent']     ?? '#d4550a';
    $bg     = $brand['background'] ?? '#070605';
    $text   = $brand['text']       ?? '#d6cec4';
    $radius = absint($brand['border_radius'] ?? 0);

    $btn_bg           = !empty($brand['btn_bg'])    ? $brand['btn_bg']    : $accent;
    $btn_color        = !empty($brand['btn_color'])  ? $brand['btn_color']  : '#ffffff';
    $btn_hover_bg     = !empty($brand['btn_hover_bg'])   ? $brand['btn_hover_bg']   : showtime_adjust_brightness($btn_bg, 0.65);
    $btn_hover_color  = !empty($brand['btn_hover_color']) ? $brand['btn_hover_color'] : '#ffffff';
    $btn_border_width = absint($brand['btn_border_width'] ?? 1);
    $btn_border_color = !empty($brand['btn_border_color']) ? $brand['btn_border_color'] : $btn_bg;
    $btn_padding_y    = isset($brand['btn_padding_y']) && $brand['btn_padding_y'] !== '' ? absint($brand['btn_padding_y']) : 8;
    $btn_padding_x    = isset($brand['btn_padding_x']) && $brand['btn_padding_x'] !== '' ? absint($brand['btn_padding_x']) : 32;

    return [
        '--showtime-accent'                   => $accent,
        '--showtime-accent-hover'             => showtime_adjust_brightness($accent, 0.65),
        '--showtime-accent-subtle'            => showtime_hex_to_rgba($accent, 0.08),
        '--showtime-accent-border'            => showtime_hex_to_rgba($accent, 0.35),
        '--showtime-accent-glow'              => showtime_hex_to_rgba($accent, 0.25),
        '--showtime-row-hover-bg'             => showtime_hex_to_rgba($accent, 0.04),
        '--showtime-bg'                       => $bg,
        '--showtime-surface'                  => showtime_adjust_brightness($bg, 1.6),
        '--showtime-border'                   => showtime_hex_to_rgba($text, 0.06),
        '--showtime-text'                     => $text,
        '--showtime-muted'                    => showtime_hex_to_rgba($text, 0.35),
        '--showtime-btn-bg'                   => $btn_bg,
        '--showtime-btn-color'                => $btn_color,
        '--showtime-btn-hover-bg'             => $btn_hover_bg,
        '--showtime-btn-hover-color'          => $btn_hover_color,
        '--showtime-btn-border-width'         => $btn_border_width . 'px',
        '--showtime-btn-border-color'         => $btn_border_color,
        '--showtime-btn-padding'              => $btn_padding_y . 'px ' . $btn_padding_x . 'px',
        '--showtime-btn-ghost-color'          => $accent,
        '--showtime-btn-ghost-hover-bg'       => $accent,
        '--showtime-btn-ghost-hover-color'    => '#ffffff',
        '--showtime-btn-disabled-bg'          => 'transparent',
        '--showtime-btn-disabled-color'       => showtime_hex_to_rgba($text, 0.2),
        '--showtime-btn-disabled-border'      => showtime_hex_to_rgba($text, 0.12),
        '--showtime-font-display'             => $brand['font_display'] ?? "'Bebas Neue', sans-serif",
        '--showtime-font-ui'                  => $brand['font_ui']      ?? "'Space Mono', monospace",
        '--showtime-day-size'                 => '72px',
        '--showtime-venue-size'               => '28px',
        '--showtime-price-size'               => '26px',
        '--showtime-radius'                   => $radius . 'px',
        '--showtime-radius-sm'                => max(0, $radius - 2) . 'px',
        '--showtime-status-onsale'            => $accent,
        '--showtime-status-soldout'           => showtime_hex_to_rgba($text, 0.25),
        '--showtime-status-cancelled'         => '#cc2200',
        '--showtime-status-postponed'         => '#888888',
        '--showtime-status-past'              => showtime_hex_to_rgba($text, 0.15),
        '--showtime-past-opacity'             => '0.4',
        '--showtime-transition'               => '0.2s ease',
    ];
}

// ── CSS in wp_head ausgeben ─────────────────────────────────────────────────

function showtime_output_design_css(): void {
    $brand  = get_option('showtime_brand_tokens', showtime_brand_token_defaults());
    $tokens = showtime_compute_tokens($brand);

    $css = ":root {\n";
    foreach ($tokens as $property => $value) {
        $css .= "    {$property}: {$value};\n";
    }
    $css .= "}\n";

    echo "<style id=\"showtime-design-system\">\n{$css}</style>\n";
}
// Priority 99: nach wp_print_styles (Priority 8), damit :root-Defaults aus main.css überschrieben werden
add_action('wp_head', 'showtime_output_design_css', 99);

// ── Presets ─────────────────────────────────────────────────────────────────

function showtime_design_presets(): array {
    return [
        'dark_metal' => [
            'label'         => 'Dark Metal',
            'accent'        => '#d4550a',
            'background'    => '#070605',
            'text'          => '#d6cec4',
            'font_display'  => "'Bebas Neue', sans-serif",
            'font_ui'       => "'Space Mono', monospace",
            'border_radius' => 0,
        ],
        'punk' => [
            'label'         => 'Punk',
            'accent'        => '#39ff14',
            'background'    => '#0a0a0a',
            'text'          => '#f0f0f0',
            'font_display'  => "'Oswald', sans-serif",
            'font_ui'       => "'Courier New', monospace",
            'border_radius' => 2,
        ],
        'electronic' => [
            'label'         => 'Electronic',
            'accent'        => '#00c8ff',
            'background'    => '#05080f',
            'text'          => '#c8dde8',
            'font_display'  => "'Rajdhani', sans-serif",
            'font_ui'       => "'Roboto Mono', monospace",
            'border_radius' => 4,
        ],
        'classic_rock' => [
            'label'         => 'Classic Rock',
            'accent'        => '#c8a84b',
            'background'    => '#0d0a06',
            'text'          => '#e8dfc8',
            'font_display'  => "'Playfair Display', serif",
            'font_ui'       => "'Georgia', serif",
            'border_radius' => 3,
        ],
        'custom' => [
            'label'         => 'Custom',
            'accent'        => '#ffffff',
            'background'    => '#000000',
            'text'          => '#ffffff',
            'font_display'  => "'Bebas Neue', sans-serif",
            'font_ui'       => "'Space Mono', monospace",
            'border_radius' => 0,
        ],
    ];
}

// ── AJAX: Token-Vorschau ────────────────────────────────────────────────────

add_action('wp_ajax_showtime_preview_tokens', function (): void {
    check_ajax_referer('showtime_design_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();

    $raw = $_POST['brand'] ?? [];
    $brand = [
        'accent'        => sanitize_hex_color($raw['accent']     ?? '#d4550a') ?: '#d4550a',
        'background'    => sanitize_hex_color($raw['background'] ?? '#070605') ?: '#070605',
        'text'          => sanitize_hex_color($raw['text']       ?? '#d6cec4') ?: '#d6cec4',
        'font_display'  => sanitize_text_field($raw['font_display'] ?? "'Bebas Neue', sans-serif"),
        'font_ui'       => sanitize_text_field($raw['font_ui']      ?? "'Space Mono', monospace"),
        'border_radius' => absint($raw['border_radius'] ?? 0),
    ];

    wp_send_json_success(showtime_compute_tokens($brand));
});
