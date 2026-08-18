<?php
/**
 * Fase 14 — Tarvo web: fuera el botón grande "Compartir en WhatsApp";
 * en su lugar, el icono redondo de WhatsApp de Flatsome (fila de share)
 * visible TAMBIÉN en desktop (Flatsome lo oculta con .show-for-medium).
 * Correr con: php ~/bin/wp eval-file ~/fase14.php (desde ~/public_html)
 */

// 1) reescribir el mu-plugin SOLO con el Open Graph (sin botón grande)
$dir = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$code = <<<'PHP'
<?php
/* Tarvo: Open Graph en fichas de producto (foto real en previews de
   WhatsApp/Facebook/Telegram). mu-plugin: sobrevive a cambios de tema. */
add_action('wp_head', function () {
    if (!function_exists('is_product') || !is_product()) return;
    global $post;
    $p = wc_get_product($post->ID);
    if (!$p) return;
    $title = wp_strip_all_tags($p->get_name());
    $desc  = wp_strip_all_tags($p->get_short_description() ?: $p->get_description());
    $desc  = mb_substr(trim(preg_replace('/\s+/u', ' ', $desc)), 0, 200);
    if ($desc === '') $desc = 'Compra gestionada por Tarvo — pedinos por WhatsApp.';
    $url = get_permalink($post->ID);
    $img = wp_get_attachment_image_url($p->get_image_id(), 'large');
    echo "\n" . '<meta property="og:type" content="product" />' . "\n";
    printf('<meta property="og:site_name" content="Tarvo" />' . "\n");
    printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
    printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($desc));
    printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
    if ($img) {
        printf('<meta property="og:image" content="%s" />' . "\n", esc_url($img));
        printf('<meta property="og:image:secure_url" content="%s" />' . "\n", esc_url($img));
        $meta = wp_get_attachment_metadata($p->get_image_id());
        if (!empty($meta['width'])) {
            printf('<meta property="og:image:width" content="%d" />' . "\n", $meta['width']);
            printf('<meta property="og:image:height" content="%d" />' . "\n", $meta['height']);
        }
    }
    if ($p->get_price()) {
        printf('<meta property="product:price:amount" content="%s" />' . "\n", esc_attr($p->get_price()));
        echo '<meta property="product:price:currency" content="PYG" />' . "\n";
    }
}, 4);
PHP;

file_put_contents($dir . '/tarvo-og.php', $code);
echo "OK tarvo-og.php md5=" . substr(md5($code), 0, 10) . " (sin boton grande)\n";

// 2) habilitar WhatsApp en la fila de iconos de compartir de Flatsome
$social = get_theme_mod('social_icons', ['facebook', 'twitter', 'email', 'pinterest', 'linkedin']);
if (!in_array('whatsapp', (array) $social)) {
    $social[] = 'whatsapp';
    set_theme_mod('social_icons', $social);
    echo "whatsapp agregado a social_icons\n";
} else {
    echo "whatsapp ya estaba en social_icons\n";
}

// 3) CSS: mostrar el icono de WhatsApp también en desktop
$css_add = "\n/* fase14: icono compartir WhatsApp visible en desktop */\n"
    . "a.show-for-medium[href*=\"whatsapp\"]{display:inline-block!important}\n";
$cur = wp_get_custom_css();
if (strpos($cur, 'fase14') === false) {
    wp_update_custom_css_post($cur . $css_add);
    echo "CSS agregado\n";
} else {
    echo "CSS ya estaba\n";
}
