<?php
/**
 * Plugin Name: Tarvo — Estados de pedido y seguimiento
 * Description: Estados En trámite / En tránsito / Entregado + mail al cliente en cada cambio. (mu-plugin, sobrevive a cambios de tema)
 */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_status('wc-tramite', [
        'label' => 'En trámite', 'public' => true,
        'show_in_admin_status_list' => true, 'show_in_admin_all_list' => true,
        'label_count' => _n_noop('En trámite <span class="count">(%s)</span>',
                                 'En trámite <span class="count">(%s)</span>'),
    ]);
    register_post_status('wc-transito', [
        'label' => 'En tránsito', 'public' => true,
        'show_in_admin_status_list' => true, 'show_in_admin_all_list' => true,
        'label_count' => _n_noop('En tránsito <span class="count">(%s)</span>',
                                 'En tránsito <span class="count">(%s)</span>'),
    ]);
});

add_filter('wc_order_statuses', function ($s) {
    $out = [];
    foreach ($s as $k => $v) {
        $out[$k] = $v;
        if ($k === 'wc-processing') {
            $out['wc-tramite'] = 'En trámite';
            $out['wc-transito'] = 'En tránsito';
        }
    }
    if (isset($out['wc-completed'])) $out['wc-completed'] = 'Entregado';
    return $out;
});

// mail simple al cliente cuando el pedido avanza
add_action('woocommerce_order_status_changed', function ($id, $de, $a, $order) {
    $map = [
        'tramite'   => 'En trámite ✅ — estamos gestionando tu compra',
        'transito'  => 'En tránsito ✈️ — tu pedido viaja hacia vos',
        'completed' => 'Entregado 🎉 — ¡gracias por comprar en Tarvo!',
    ];
    if (!isset($map[$a])) return;
    $mail = $order->get_billing_email();
    if (!$mail) return;
    $nombre = $order->get_billing_first_name() ?: 'cliente';
    wp_mail(
        $mail,
        "Tu pedido #{$id} en Tarvo: " . strtok($map[$a], '—'),
        "Hola {$nombre}!\n\nTu pedido #{$id} está ahora: {$map[$a]}.\n\n"
        . "Seguilo en https://tarvo.com.py/mi-cuenta/pedidos/\n\n"
        . "¿Dudas? WhatsApp 0992 805 800\n— Tarvo · tarvo.com.py"
    );
}, 10, 4);
