<?php
// Fase 11 Tarvo — links de acción visibles + textos legales en español. wp eval-file fase11.php

// 1) CSS: links que se noten (cuenta, avisos, textos)
$css = wp_get_custom_css();
if (strpos($css, 'links visibles') === false) {
    $css .= <<<CSS

/* Tarvo — links visibles */
.woocommerce-MyAccount-content a:not(.button),
.woocommerce-notices-wrapper a:not(.button),
.woocommerce-info a:not(.button), .woocommerce-message a:not(.button),
.woocommerce-error a:not(.button),
.entry-content p a:not(.button), .woocommerce-privacy-policy-text a,
form.login a, form.register a, .woocommerce-LostPassword a {
  color: #2445ff; font-weight: 600;
  text-decoration: underline; text-underline-offset: 3px;
}
.woocommerce-MyAccount-content a:not(.button):hover,
.entry-content p a:not(.button):hover { color: #1230b8; }
.woocommerce-MyAccount-navigation ul li a { font-weight: 600; }
.woocommerce-MyAccount-navigation ul li.is-active a { color: #2445ff; }
CSS;
    wp_update_custom_css_post($css);
    echo "css links ok\n";
} else { echo "css links ya estaba\n"; }

// 2) textos legales en español
update_option('woocommerce_registration_privacy_policy_text',
    'Tus datos personales se usan únicamente para gestionar tu cuenta y tus pedidos, según nuestra [privacy_policy].');
update_option('woocommerce_checkout_privacy_policy_text',
    'Tus datos personales se usan únicamente para procesar tu pedido, según nuestra [privacy_policy].');
echo "textos legales es ok\n";

// 3) publicar política de privacidad simple si está en borrador
$pp_id = (int) get_option('wp_page_for_privacy_policy');
if ($pp_id && get_post_status($pp_id) !== 'publish') {
    wp_update_post(['ID' => $pp_id, 'post_status' => 'publish', 'post_content' =>
        '<h2>Política de privacidad</h2>
<p>En <strong>Tarvo</strong> (tarvo.com.py) usamos tus datos personales únicamente para: gestionar tu cuenta de cliente, procesar y entregar tus pedidos, y comunicarnos con vos sobre su estado.</p>
<p>No compartimos tus datos con terceros, salvo lo estrictamente necesario para la entrega de tu pedido (courier/delivery).</p>
<p>Podés pedir la corrección o eliminación de tus datos escribiéndonos por WhatsApp al <a href="https://wa.me/595992805800">0992 805 800</a>.</p>']);
    echo "politica publicada (id $pp_id)\n";
} else { echo "politica ya publicada o no configurada (id $pp_id)\n"; }
echo "FIN FASE 11 OK\n";
