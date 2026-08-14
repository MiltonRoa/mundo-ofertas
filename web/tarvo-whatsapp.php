<?php
/**
 * Plugin Name: Tarvo — Pedidos por WhatsApp
 * Description: Botón "Pedir por WhatsApp" en cada producto (mu-plugin, independiente del tema).
 */
if (!defined('ABSPATH')) exit;

const TARVO_WA = '595992805800';

function tarvo_wa_url($product) {
    $precio = number_format((float) $product->get_regular_price(), 0, ',', '.');
    $msg = 'Hola! Quiero pedir: ' . $product->get_name()
         . ' — Gs ' . $precio
         . ' (' . $product->get_sku() . ')';
    return 'https://wa.me/' . TARVO_WA . '?text=' . rawurlencode($msg);
}

add_action('woocommerce_single_product_summary', function () {
    global $product;
    if (!$product) return;
    echo '<p><a class="button tarvo-wa-btn" target="_blank" rel="noopener" href="'
        . esc_url(tarvo_wa_url($product)) . '">📲 Pedir por WhatsApp</a></p>';
}, 31);

add_action('wp_head', function () {
    echo '<style>.tarvo-wa-btn{background:#25D366!important;color:#fff!important;'
        . 'font-weight:600;width:100%;text-align:center}'
        . '.tarvo-wa-btn:hover{background:#1ebe5d!important}</style>';
});
